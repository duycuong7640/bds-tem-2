<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Repositories\CmsRepository;

class LocalAdminSeeder extends Seeder
{
    public function run(CmsRepository $repository): void
    {
        if (! app()->environment('local') || config('webfx.runtime')) {
            return;
        }

        $email = mb_strtolower(trim((string) config('webfx.local_admin_email')));
        $password = config('webfx.local_admin_password');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($password) || $password === '') {
            return;
        }
        if ($repository->table('users')->where('email', $email)->exists()) {
            return;
        }

        $repository->save('users', null, [
            'name' => 'Quản trị viên',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'active' => true,
        ]);
    }
}
