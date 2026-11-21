<?php

namespace Modules\Admin\Services;

use App\Models\User;
use Modules\Admin\Repositories\ConfigurationRepository;

class AccessService
{
    public function __construct(private ConfigurationRepository $repository) {}

    public function permissions(User $user): array
    {
        $attributes = request()->attributes;
        $key = 'cms.permissions.'.$user->role;
        if (! $attributes->has($key)) {
            $attributes->set($key, $this->repository->permissions($user->role));
        }

        return $attributes->get($key);
    }

    public function allows(?User $user, string $permission): bool
    {
        return $user && $user->active && (
            $user->role === 'admin' || in_array($permission, $this->permissions($user), true)
        );
    }
}
