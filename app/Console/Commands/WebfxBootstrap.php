<?php

namespace App\Console\Commands;

use App\Services\WebfxSampleSnapshot;
use Database\Seeders\RealEstateSampleSeeder;
use Database\Seeders\SampleMenuSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Repositories\CmsRepository;
use Throwable;

class WebfxBootstrap extends Command
{
    protected $signature = 'webfx:bootstrap';

    protected $description = 'Khởi tạo dữ liệu mẫu và tài khoản website WebFX';

    public function handle(CmsRepository $repository, WebfxSampleSnapshot $snapshot): int
    {
        $demo = config('webfx.demo');
        $mode = $demo ? 'demo' : 'private';
        $email = strtolower(trim((string) getenv('WEBFX_OWNER_EMAIL')));

        try {
            return DB::transaction(function () use ($repository, $snapshot, $demo, $mode, $email): int {
                $installation = DB::table('webfx_installations');
                $installation->insertOrIgnore(['id' => 1]);
                $existingMode = $installation->where('id', 1)->lockForUpdate()->value('mode');
                if ($existingMode === $mode) {
                    $this->info('Website đã được khởi tạo.');

                    return self::SUCCESS;
                }
                if ($existingMode !== null) {
                    $this->error('Database đã khởi tạo với chế độ WebFX khác.');

                    return self::FAILURE;
                }

                if (! $demo) {
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                        $this->error('Cần WEBFX_OWNER_EMAIL hợp lệ.');

                        return self::FAILURE;
                    }
                    $owner = $repository->table('users')->where('email', $email)->first();
                    if ($owner && ($owner->role !== 'admin' || ! $owner->active)) {
                        $this->error('Email chủ website đã được gán cho tài khoản không có quyền Admin hoạt động.');

                        return self::FAILURE;
                    }
                    if (! $owner) {
                        $password = getenv('WEBFX_INITIAL_PASSWORD');
                        if (! is_string($password) || trim($password) === '' || strlen($password) > 72 || str_contains($password, "\0")) {
                            $this->error('Cần WEBFX_INITIAL_PASSWORD hợp lệ, tối đa 72 byte.');

                            return self::FAILURE;
                        }
                        $repository->save('users', null, [
                            'name' => 'Quản trị viên', 'email' => $email,
                            'password' => Hash::make($password), 'role' => 'admin', 'active' => true,
                        ]);
                    }
                }

                if (config('webfx.sample') === 'furniture') {
                    $snapshot->sync();
                } else {
                    app()->call([app(SampleMenuSeeder::class), 'run']);
                    if (config('webfx.sample') !== 'empty') {
                        app()->call([app(RealEstateSampleSeeder::class), 'run']);
                    }
                }
                $installation->where('id', 1)->update(['mode' => $mode]);
                $this->info('Đã khởi tạo website WebFX.');

                return self::SUCCESS;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Khởi tạo thất bại. Kiểm tra kết nối database, migration và quyền ghi volume.');

            return self::FAILURE;
        }
    }
}
