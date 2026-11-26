<?php

namespace App\Console\Commands;

use App\Services\WebfxSampleSnapshot;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WebfxSampleSync extends Command
{
    use ConfirmableTrait;

    protected $signature = 'webfx:sample-sync {--force : Chạy không hỏi xác nhận trên production}';

    protected $description = 'Bổ sung nội dung và ảnh còn thiếu từ bản chụp demo bất động sản';

    public function handle(WebfxSampleSnapshot $snapshot): int
    {
        if (! $this->confirmToProceed('Bổ sung nội dung mẫu còn thiếu vào database này?')) {
            return self::FAILURE;
        }
        if (config('webfx.sample') !== 'furniture') {
            $this->error('Cần WEBFX_TEMPLATE_SAMPLE=furniture để đồng bộ bản chụp.');

            return self::FAILURE;
        }
        if (config('webfx.runtime') && DB::table('webfx_installations')->where('id', 1)->value('mode') === null) {
            $this->error('Website chưa khởi tạo. Chạy webfx-laravel-init trước.');

            return self::FAILURE;
        }

        try {
            $counts = $snapshot->sync();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($counts
            ? 'Đã bổ sung '.collect($counts)->map(fn ($count, $resource) => "$count $resource")->implode(', ').'.'
            : 'Dữ liệu và ảnh mẫu đã đầy đủ.');

        return self::SUCCESS;
    }
}
