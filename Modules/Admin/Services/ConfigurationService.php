<?php

namespace Modules\Admin\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Admin\Repositories\ConfigurationRepository;

class ConfigurationService
{
    public function __construct(private ConfigurationRepository $repository) {}

    public function saveSettings(Request $request): void
    {
        $rules = [
            'site_name' => 'required|string|max:120',
            'tagline' => 'nullable|string|max:200',
            'email' => 'nullable|email:rfc|max:254',
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 .+()-]{8,40}$/'],
            'address' => 'nullable|string|max:255',
            'logo' => CmsService::imageRules(),
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:300',
        ];
        $labels = config('cms.settings');
        foreach (config('cms.setting_groups') as $fields) {
            foreach ($fields as $key => [$label, $type, $rule]) {
                $rules[$key] = $rule;
                $labels[$key] = $label;
            }
        }
        $values = $request->validate($rules, [
            'required' => 'Vui lòng nhập :attribute.',
            'regex' => ':attribute không đúng định dạng.',
            'url' => ':attribute phải là đường dẫn http(s) hợp lệ.',
            'email' => ':attribute không hợp lệ.',
            'integer' => ':attribute phải là số nguyên.',
            'min' => ':attribute tối thiểu :min.',
            'max' => ':attribute vượt quá giới hạn cho phép (:max).',
            'map_embed.starts_with' => 'Link bản đồ phải bắt đầu bằng https://www.google.com/maps (lấy từ Chia sẻ → Nhúng bản đồ).',
        ], $labels);
        $this->repository->saveSettings($values);
    }

    public function savePermissions(Request $request): void
    {
        $permissions = [];
        foreach (config('cms.permission_groups') as $key => $label) {
            $permissions[] = $key.'.view';
            if ($key !== 'dashboard') {
                $permissions[] = $key.'.edit';
                if ($key !== 'settings') {
                    $permissions[] = $key.'.delete';
                }
            }
        }
        $roles = $request->validate([
            'roles' => 'sometimes|array:manager,editor',
            'roles.*' => 'array',
            'roles.*.*' => ['string', Rule::in($permissions)],
        ])['roles'] ?? [];
        $settings = [];
        foreach (['manager', 'editor'] as $role) {
            $items = $roles[$role] ?? [];
            foreach ($items as $item) {
                if (! str_ends_with($item, '.view')) {
                    $items[] = str($item)->before('.')->append('.view')->toString();
                }
            }
            $settings[$role] = array_unique($items);
        }
        $this->repository->savePermissions($settings);
    }
}
