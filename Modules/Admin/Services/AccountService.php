<?php

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\Admin\Repositories\CmsRepository;

class AccountService
{
    public function __construct(private CmsRepository $repository) {}

    public function createAdmin(string $name, string $email, string $password): void
    {
        $fields = Validator::make([
            'name' => $name, 'email' => mb_strtolower(trim($email)), 'password' => $password,
        ], [
            'name' => 'required|string|max:180', 'email' => 'required|email:rfc|max:254|unique:users',
            'password' => 'required|string|min:12|max:72',
        ])->validate();
        $fields['password'] = Hash::make($fields['password']);
        $this->repository->save('users', null, $fields + ['role' => 'admin', 'active' => true]);
    }
}
