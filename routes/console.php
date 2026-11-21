<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Services\AccountService;

Artisan::command('cms:admin {email} {--name=Quản trị viên}', function (AccountService $accounts) {
    $password = $this->secret('Mật khẩu (tối thiểu 12 ký tự)');
    if (! is_string($password) || $password !== $this->secret('Nhập lại mật khẩu')) {
        $this->error('Mật khẩu xác nhận không khớp.');

        return 1;
    }
    try {
        $accounts->createAdmin($this->option('name'), $this->argument('email'), $password);
    } catch (ValidationException $exception) {
        $this->error($exception->getMessage());

        return 1;
    }
    $this->info('Đã tạo tài khoản quản trị.');

    return 0;
})->purpose('Tạo tài khoản Admin');
