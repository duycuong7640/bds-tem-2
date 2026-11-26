<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WebfxSampleSnapshot
{
    public const SNAPSHOT = 'demo-data/furniture.json';

    public const IMAGES = 'demo-images/storage/';

    // Explicit allowlists keep account, marketplace transaction and private contact data out of the snapshot.
    public const COLUMNS = [
        'menus' => 'id parent_id name url position active content_type seo_title seo_description',
        'listing_packages' => 'id name badge color price_per_day priority min_days description position active',
        'products' => 'id name slug sku price image body active published_at description seo_title seo_description menu_id listing_type package_id priority province district address area bedrooms toilets floors direction legal gallery',
        'posts' => 'id name slug image body active kind published_at description seo_title seo_description menu_id',
        'banners' => 'id name placement image url position active',
        'site_settings' => 'key value',
    ];

    public const SETTINGS = [
        'site_name', 'tagline', 'email', 'phone', 'address', 'logo', 'seo_title', 'seo_description',
        'company_name', 'zalo', 'working_hours', 'tax_code', 'footer_about', 'map_embed',
        'facebook', 'youtube', 'tiktok', 'posting_rules',
    ];

    public function locked(Closure $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            DB::table('webfx_installations')->insertOrIgnore(['id' => 1]);
            DB::table('webfx_installations')->where('id', 1)->lockForUpdate()->first();

            return $callback();
        });
    }

    public function export(): array
    {
        return $this->locked(function (): array {
            $data = [];
            foreach (self::COLUMNS as $table => $columns) {
                $query = DB::table($table);
                if ($table === 'site_settings') {
                    $query->whereIn('key', self::SETTINGS)->orderBy('key');
                } else {
                    $query->orderBy('id');
                }
                if ($table === 'products') {
                    $query->where('status', 'approved');
                }
                $data[$table] = $query->get(explode(' ', $columns))
                    ->map(fn ($row) => array_map(fn ($value) => $this->portableValue($value), (array) $row))
                    ->all();
            }

            $paths = $this->imagePaths($data);
            $disk = Storage::disk('public');
            $missing = array_values(array_filter($paths, fn ($path) => ! $disk->exists($path)));
            if ($missing) {
                return [[], $paths, $missing];
            }

            $imageRoot = database_path(self::IMAGES);
            foreach ($paths as $path) {
                $target = $imageRoot.$path;
                File::ensureDirectoryExists(dirname($target));
                File::replace($target, $disk->get($path), 0644);
            }

            $file = database_path(self::SNAPSHOT);
            File::ensureDirectoryExists(dirname($file));
            File::replace($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", 0644);

            if (is_dir($imageRoot)) {
                foreach (File::allFiles($imageRoot, true) as $image) {
                    if (! in_array($image->getRelativePathname(), $paths, true)) {
                        File::delete($image->getPathname());
                    }
                }
            }

            foreach ($data as $table => $rows) {
                if ($table !== 'site_settings') {
                    foreach ($rows as $row) {
                        $this->remember($table, $row['id'], $row['id']);
                    }
                }
            }

            return [array_map('count', $data), $paths, []];
        });
    }

    private function portableValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        // File manager links may contain the local origin. Store those as site-relative URLs.
        $origin = preg_replace('~^(https?://[^/]+).*~i', '$1', config('app.url'));
        $value = str_replace('\\/', '/', $value);

        return preg_replace('~'.preg_quote($origin, '~').'(?=/(?!/))~i', '', $value);
    }

    private function imagePaths(array $data): array
    {
        $paths = [];
        array_walk_recursive($data, function ($value) use (&$paths): void {
            if (! is_string($value)) {
                return;
            }
            preg_match_all('~(?<![\w:/.-])/storage/([^"\'\s<>()?#,]+)~', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches);
            foreach ($matches[1] as $encoded) {
                $path = rawurldecode($encoded);
                if (preg_match('~(?:^|/)\.{1,2}(?:/|$)|[\\\\\x00-\x1f]|^/~', $path)) {
                    throw new RuntimeException("Đường dẫn ảnh không hợp lệ: {$encoded}");
                }
                $paths[$path] = $path;
            }
        });

        return array_values($paths);
    }

    public function sync(): array
    {
        return $this->locked(function (): array {
            $file = database_path(self::SNAPSHOT);
            if (! is_file($file)) {
                throw new RuntimeException('Thiếu bản chụp. Hãy chạy php artisan webfx:export-sample trước khi build.');
            }
            $data = json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);

            $paths = $this->imagePaths($data);
            foreach ($paths as $path) {
                if (! is_file(database_path(self::IMAGES.$path))) {
                    throw new RuntimeException("Thiếu ảnh trong bản chụp: {$path}");
                }
            }
            $disk = Storage::disk('public');
            $counts = [];
            foreach ($paths as $path) {
                if (! $disk->exists($path)) {
                    if (! $disk->put($path, File::get(database_path(self::IMAGES.$path)))) {
                        throw new RuntimeException("Không thể ghi ảnh mẫu: {$path}");
                    }
                    $counts['ảnh'] = ($counts['ảnh'] ?? 0) + 1;
                }
            }

            $ids = [];
            foreach (self::COLUMNS as $table => $columns) {
                $rows = $data[$table];
                if ($table === 'site_settings') {
                    foreach ($rows as $row) {
                        if (in_array($row['key'], self::SETTINGS, true)) {
                            $added = DB::table($table)->insertOrIgnore(['key' => $row['key'], 'value' => $row['value']]);
                            if ($added) {
                                $counts[$table] = ($counts[$table] ?? 0) + $added;
                            }
                        }
                    }
                    continue;
                }

                // Resolve parents before children even when the admin moved a menu under a newer one.
                if ($table === 'menus') {
                    $rows = $this->orderedMenus($rows);
                }
                foreach ($rows as $row) {
                    $row = array_intersect_key($row, array_flip(explode(' ', $columns)));
                    $sourceId = $row['id'];
                    unset($row['id']);
                    foreach (['parent_id' => 'menus', 'menu_id' => 'menus', 'package_id' => 'listing_packages'] as $key => $resource) {
                        if (isset($row[$key])) {
                            $row[$key] = $ids[$resource][$row[$key]] ?? null;
                        }
                    }

                    $targetId = $this->findExisting($table, $sourceId, $row);
                    if ($targetId === null) {
                        $targetId = DB::table($table)->insertGetId($row + [
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $counts[$table] = ($counts[$table] ?? 0) + 1;
                    }
                    $ids[$table][$sourceId] = $targetId;
                    $this->remember($table, $sourceId, $targetId);
                }
            }

            return $counts;
        });
    }

    private function orderedMenus(array $rows): array
    {
        $ordered = [];
        while ($rows) {
            $remaining = [];
            foreach ($rows as $row) {
                if ($row['parent_id'] === null || isset($ordered[$row['parent_id']])) {
                    $ordered[$row['id']] = $row;
                } else {
                    $remaining[] = $row;
                }
            }
            if (count($remaining) === count($rows)) {
                throw new RuntimeException('Bản chụp có menu cha bị thiếu hoặc tạo vòng lặp.');
            }
            $rows = $remaining;
        }

        return array_values($ordered);
    }

    private function findExisting(string $table, int $sourceId, array $row): ?int
    {
        $mappings = DB::table('webfx_sample_items')->where('resource', $table);
        $mapped = (clone $mappings)->where('source_id', $sourceId)->value('target_id');
        if ($mapped !== null && DB::table($table)->where('id', $mapped)->exists()) {
            return (int) $mapped;
        }

        // Adopt pre-snapshot content by its public identifiers without modifying the content.
        $query = DB::table($table)->whereNotIn('id', $mappings->where('source_id', '!=', $sourceId)->select('target_id'));
        $query = match ($table) {
            'menus' => $query->where('parent_id', $row['parent_id'])
                ->where(fn ($q) => $q->where('name', $row['name'])->when($row['url'], fn ($q) => $q->orWhere('url', $row['url']))),
            'banners' => $query->where('placement', $row['placement'])
                ->where(fn ($q) => $q->where('name', $row['name'])->orWhere('image', $row['image'])),
            'products' => $query->where(fn ($q) => $q->where('slug', $row['slug'])
                ->when($row['sku'], fn ($q) => $q->orWhere('sku', $row['sku']))),
            'posts' => $query->where('slug', $row['slug']),
            'listing_packages' => $query->where('name', $row['name']),
        };

        $id = $query->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    private function remember(string $table, int $sourceId, int $targetId): void
    {
        DB::table('webfx_sample_items')->upsert([
            'resource' => $table, 'source_id' => $sourceId, 'target_id' => $targetId,
        ], ['resource', 'source_id'], ['target_id']);
    }
}
