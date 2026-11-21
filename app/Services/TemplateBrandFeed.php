<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Lấy logo và dòng copyright từ WebFX: GET {LICENSE_ISSUER}/api/v1/template-brand.
 * Kết quả được cache 5 phút (1 phút nếu lỗi) để không gọi API ở mỗi lượt xem trang.
 */
class TemplateBrandFeed
{
    private const EMPTY = ['logo' => null, 'copyright' => null];

    public function __construct(private LicenseGate $license) {}

    public function get(): array
    {
        $origin = $this->license->issuer();
        $allowHttp = app()->environment('local', 'testing');
        if (! $this->validUrl($origin, $allowHttp)) {
            return self::EMPTY;
        }

        // Trong Docker local, "localhost" của WebFX phải gọi qua host.docker.internal.
        $override = app()->environment('local') ? config('license.campaign_local_origin') : null;
        $endpoint = rtrim($this->validUrl($override, true) ? $override : $origin, '/').'/api/v1/template-brand';
        $key = 'webfx:template-brand:'.hash('sha256', $origin.'|'.$endpoint);

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $host = parse_url($origin, PHP_URL_HOST);
        $port = parse_url($origin, PHP_URL_PORT);
        try {
            $response = Http::acceptJson()->withHeaders(['Host' => $host.($port ? ':'.$port : '')])
                ->connectTimeout(1)->timeout(2)->withoutRedirecting()->get($endpoint);
            $data = $response->ok() ? $response->json() : null;
        } catch (ConnectionException) {
            $data = null;
        }

        $brand = is_array($data) ? [
            'logo' => $this->validUrl($data['logo'] ?? null, $allowHttp) ? $data['logo'] : null,
            'copyright' => $this->copyright($data['copyright'] ?? null),
        ] : self::EMPTY;
        Cache::put($key, $brand, is_array($data) ? 300 : 60);

        return $brand;
    }

    private function validUrl(mixed $url, bool $allowHttp): bool
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && in_array(parse_url($url, PHP_URL_SCHEME), $allowHttp ? ['http', 'https'] : ['https'], true);
    }

    private function copyright(mixed $html): ?string
    {
        if (! is_string($html) || mb_strlen($html) > 4000) {
            return null;
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'a[href|title],p,br,strong,b,em,i,span');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);

        return trim((new \HTMLPurifier($config))->purify($html)) ?: null;
    }
}
