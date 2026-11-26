<?php

namespace App\Console\Commands;

use App\Services\WebfxSampleSnapshot;
use Illuminate\Console\Command;

class WebfxExportSample extends Command
{
    protected $signature = 'webfx:export-sample';

    protected $description = 'Xuất dữ liệu demo bất động sản và ảnh từ database local';

    public function handle(WebfxSampleSnapshot $snapshot): int
    {
        if (config('webfx.runtime')) {
            $this->error('Chỉ chạy lệnh xuất trong môi trường phát triển local.');

            return self::FAILURE;
        }

        [$counts, $images, $missing] = $snapshot->export();
        if ($missing) {
            foreach ($missing as $path) {
                $this->warn("Thiếu ảnh: storage/app/public/{$path}");
            }
            $this->error('Bản chụp chưa được cập nhật. Khôi phục ảnh hoặc sửa đường dẫn trong admin rồi xuất lại.');

            return self::FAILURE;
        }

        $this->info('Đã xuất '.collect($counts)->map(fn ($count, $table) => "$count $table")->implode(', ').'.');
        $this->info('Đã đóng gói '.count($images).' ảnh từ storage.');

        return self::SUCCESS;
    }
}
