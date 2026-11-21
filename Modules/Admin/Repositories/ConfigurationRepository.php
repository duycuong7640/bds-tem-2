<?php

namespace Modules\Admin\Repositories;

use Illuminate\Support\Facades\DB;

class ConfigurationRepository
{
    public function settings(): array
    {
        $attributes = request()->attributes;
        if (! $attributes->has('cms.settings')) {
            $attributes->set('cms.settings', DB::table('site_settings')->pluck('value', 'key')->all());
        }

        return $attributes->get('cms.settings');
    }

    public function saveSettings(array $settings): void
    {
        $rows = collect($settings)->map(fn ($value, $key) => compact('key', 'value'))->values()->all();
        DB::table('site_settings')->upsert($rows, ['key'], ['value']);
    }

    public function permissions(string $role): array
    {
        $stored = DB::table('role_permissions')->where('role', $role)->value('permissions');

        return $stored === null ? config('cms.default_permissions.'.$role, []) : json_decode($stored, true, flags: JSON_THROW_ON_ERROR);
    }

    public function savePermissions(array $permissions): void
    {
        $rows = collect($permissions)->map(fn ($items, $role) => [
            'role' => $role,
            'permissions' => json_encode(array_values($items), JSON_THROW_ON_ERROR),
        ])->values()->all();
        DB::table('role_permissions')->upsert($rows, ['role'], ['permissions']);
    }
}
