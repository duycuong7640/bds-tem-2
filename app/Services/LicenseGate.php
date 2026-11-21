<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LicenseGate
{
    public function issuer(): mixed
    {
        return config('license.issuer');
    }

    public function allows(string $host): bool
    {
        $issuer = $this->issuer();
        $template = config('license.template');
        $key = config('license.key');
        $encodedPublicKey = config('license.public_key');
        $publicKey = is_string($encodedPublicKey) ? base64_decode($encodedPublicKey, true) : false;
        $domain = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if ($domain === '' || strtolower($host) !== $domain
            || ! is_string($issuer) || ! filter_var($issuer, FILTER_VALIDATE_URL)
            || ! in_array(parse_url($issuer, PHP_URL_SCHEME), app()->environment('local', 'testing') ? ['https', 'http'] : ['https'], true)
            || ! is_string($template) || $template === ''
            || ! is_string($key) || strlen($key) !== 64 || ! ctype_xdigit($key)
            || ! is_string($publicKey) || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        $hash = hash('sha256', $key);
        $cacheKey = 'webfx:license:'.hash('sha256', implode('|', [$issuer, $encodedPublicKey, $domain, $template, $hash]));
        $valid = fn (): bool => $this->validLease(Cache::get($cacheKey), $publicKey, $hash, $domain, $template);
        if (Cache::has($cacheKey.':poll')) {
            return $valid();
        }

        $lock = Cache::lock($cacheKey.':lock', 10);
        if (! $lock->get()) {
            return $valid();
        }
        try {
            if (Cache::has($cacheKey.':poll')) {
                return $valid();
            }
            Cache::put($cacheKey.':poll', true, 60);
            try {
                $response = Http::acceptJson()->withToken($key)->connectTimeout(2)->timeout(4)->withoutRedirecting()
                    ->post(rtrim($issuer, '/').'/api/v1/licenses/check', ['domain' => $domain, 'template' => $template]);
            } catch (ConnectionException) {
                return $valid();
            }
            if (in_array($response->status(), [401, 403], true)) {
                Cache::forget($cacheKey);

                return false;
            }
            if (! $response->ok()) {
                return $valid();
            }

            $lease = $response->json();
            if (! $this->validLease($lease, $publicKey, $hash, $domain, $template)) {
                return $valid();
            }

            Cache::put($cacheKey, $lease, 86400);
            Cache::put($cacheKey.':poll', true, 300);

            return true;
        } finally {
            $lock->release();
        }
    }

    private function validLease(mixed $lease, string $publicKey, string $hash, string $domain, string $template): bool
    {
        if (! is_array($lease) || ! is_string($lease['payload'] ?? null) || ! is_string($lease['signature'] ?? null)) {
            return false;
        }
        $signature = base64_decode($lease['signature'], true);
        $payload = $lease['payload'];
        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached($signature, $payload, $publicKey)) {
            return false;
        }

        $decoded = base64_decode($payload, true);
        $claims = $decoded !== false ? json_decode($decoded, true) : null;

        return is_array($claims)
            && ($claims['type'] ?? null) === 'webfx-license-v1'
            && ($claims['domain'] ?? null) === $domain
            && ($claims['template'] ?? null) === $template
            && ($claims['key_hash'] ?? null) === $hash
            && is_int($claims['expires_at'] ?? null)
            && $claims['expires_at'] > now()->timestamp;
    }
}
