<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdminCampaignFeed
{
    private const EMPTY_FEED = ['image' => null, 'links' => []];

    public function __construct(private LicenseGate $license) {}

    public function get(): array
    {
        $issuer = $this->license->issuer();
        if (! $this->validUrl($issuer, app()->environment('local', 'testing') ? ['http', 'https'] : ['https'])) {
            return self::EMPTY_FEED;
        }

        $origin = rtrim($issuer, '/');
        $localOrigin = app()->environment('local') ? config('license.campaign_local_origin') : null;
        $localOrigin = $this->validUrl($localOrigin, ['http', 'https']) ? $localOrigin : null;
        $cacheKey = 'webfx:admin-campaign:'.hash('sha256', $origin.'|'.$localOrigin);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $request = Http::acceptJson()->connectTimeout(1)->timeout(2)->withoutRedirecting();
        if ($localOrigin !== null) {
            $request->withHeaders(['Host' => parse_url($origin, PHP_URL_HOST)]);
        }

        try {
            $response = $request->get(rtrim($localOrigin ?: $origin, '/').'/api/v1/admin-campaign');
            $data = $response->ok() ? $response->json() : null;
        } catch (ConnectionException) {
            $data = null;
        }

        $feed = self::EMPTY_FEED;
        if (is_array($data)) {
            $image = $data['image'] ?? null;
            if (is_string($image) && str_starts_with($image, $origin.'/media/')
                && $this->validUrl($image, ['https', 'http'])) {
                $feed['image'] = $image;
            }
            $links = $data['links'] ?? [];
            if (is_array($links)) {
                foreach (array_slice($links, 0, 12) as $row) {
                    if (is_array($row) && is_string($row['text'] ?? null) && trim($row['text']) !== ''
                        && $this->validUrl($row['url'] ?? null, ['http', 'https'])) {
                        $feed['links'][] = ['text' => mb_substr(trim($row['text']), 0, 160), 'url' => $row['url']];
                    }
                }
            }
        }

        Cache::put($cacheKey, $feed, is_array($data) ? 300 : 60);

        return $feed;
    }

    private function validUrl(mixed $url, array $schemes): bool
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && in_array(parse_url($url, PHP_URL_SCHEME), $schemes, true);
    }
}
