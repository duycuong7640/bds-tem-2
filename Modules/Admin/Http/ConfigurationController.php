<?php

namespace Modules\Admin\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Admin\Repositories\ConfigurationRepository;
use Modules\Admin\Services\ConfigurationService;

class ConfigurationController
{
    public function __construct(private ConfigurationRepository $repository, private ConfigurationService $service) {}

    public function settings(): View
    {
        return view('admin::settings');
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $this->service->saveSettings($request);

        return to_route('admin.settings')->with('status', 'Đã cập nhật cấu hình website.');
    }

    public function permissions(): View
    {
        $permissions = [];
        foreach (['manager', 'editor'] as $role) {
            $permissions[$role] = $this->repository->permissions($role);
        }

        return view('admin::permissions', compact('permissions'));
    }

    public function savePermissions(Request $request): RedirectResponse
    {
        $this->service->savePermissions($request);

        return to_route('admin.permissions')->with('status', 'Đã cập nhật quyền theo vai trò.');
    }
}
