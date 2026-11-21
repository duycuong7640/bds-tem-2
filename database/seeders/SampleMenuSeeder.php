<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Repositories\CmsRepository;

class SampleMenuSeeder extends Seeder
{
    public function run(CmsRepository $repository): void
    {
        $slug = fn (string $name) => \Illuminate\Support\Str::slug($name);
        $sale = ['Bán căn hộ chung cư', 'Bán nhà riêng', 'Bán nhà mặt phố', 'Bán biệt thự, liền kề', 'Bán đất nền dự án', 'Bán đất'];
        $rent = ['Cho thuê căn hộ chung cư', 'Cho thuê nhà riêng', 'Cho thuê nhà mặt phố', 'Cho thuê văn phòng', 'Cho thuê phòng trọ', 'Cho thuê mặt bằng kinh doanh'];
        $news = ['Thị trường', 'Kinh nghiệm mua bán', 'Pháp lý nhà đất', 'Phong thủy'];
        $types = array_fill_keys([...$sale, ...$rent], 'products') + array_fill_keys($news, 'posts');
        $tree = [
            ['Trang chủ', '/', []],
            ['Nhà đất bán', '/nha-dat-ban', array_map(fn ($name) => [$name, '/nha-dat-ban?danh-muc='.$slug($name), []], $sale)],
            ['Nhà đất cho thuê', '/nha-dat-cho-thue', array_map(fn ($name) => [$name, '/nha-dat-cho-thue?danh-muc='.$slug($name), []], $rent)],
            ['Báo giá tin VIP', '/bao-gia-tin-vip', []],
            ['Tin tức', '/tin-tuc', array_map(fn ($name) => [$name, '/tin-tuc?chuyen-muc='.$slug($name), []], $news)],
            ['Liên hệ', '/lien-he', []],
        ];

        DB::transaction(fn () => $this->addBranch($repository, $tree, null, $types));
    }

    private function addBranch(CmsRepository $repository, array $branch, ?int $parentId, array $types): void
    {
        foreach ($branch as $index => [$name, $url, $children]) {
            $id = $repository->table('menus')->where('parent_id', $parentId)->where('name', $name)->value('id');
            $id ??= $repository->table('menus')->insertGetId([
                'name' => $name,
                'url' => $url,
                'parent_id' => $parentId,
                'content_type' => $types[$name] ?? null,
                'position' => ($index + 1) * 10,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->addBranch($repository, $children, $id, $types);
        }
    }
}
