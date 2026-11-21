<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admin\Repositories\CmsRepository;
use Modules\Admin\Services\MarketplaceService;
use RuntimeException;

/**
 * Dữ liệu mẫu sàn BĐS: gói tin, cấu hình liên hệ/bản đồ/ngân hàng, tin đăng, bài viết, banner.
 * Thành viên mẫu (có mật khẩu cố định) chỉ tạo ở môi trường local.
 */
class RealEstateSampleSeeder extends Seeder
{
    private const IMAGE_DIR = 'media/library/bds/';

    public function run(CmsRepository $repository): void
    {
        $this->copyImages();
        $this->settings();
        $packages = $this->packages();
        $members = $this->members();
        $this->listings($repository, $packages, $members);
        $this->posts($repository);
        $this->banners($repository);
    }

    private function img(string $file): string
    {
        return '/storage/'.self::IMAGE_DIR.$file;
    }

    private function copyImages(): void
    {
        $disk = Storage::disk('public');
        foreach (glob(database_path('demo-images/bds/*.jpg')) as $source) {
            $path = self::IMAGE_DIR.basename($source);
            if (! $disk->exists($path) && ! $disk->put($path, file_get_contents($source))) {
                throw new RuntimeException('Không thể ghi ảnh mẫu vào volume.');
            }
        }
    }

    private function settings(): void
    {
        $settings = [
            'site_name' => config('app.name'),
            'tagline' => 'Kênh đăng tin mua bán, cho thuê nhà đất uy tín toàn quốc',
            'company_name' => 'Công ty Cổ phần Bất động sản '.config('app.name'),
            'email' => 'hotro@lbtechland.vn',
            'phone' => '0901 234 567',
            'zalo' => '0901234567',
            'address' => '13 Đường N5, Phường Hiệp Thành, Quận 12, TP. Hồ Chí Minh',
            'working_hours' => 'Thứ 2 – Thứ 7: 8:00 – 17:30',
            'tax_code' => '0312345678',
            'footer_about' => 'Nền tảng đăng tin bất động sản: mua bán, cho thuê nhà đất, căn hộ, đất nền trên toàn quốc. Tin đăng được kiểm duyệt trước khi hiển thị.',
            'map_embed' => 'https://www.google.com/maps?q='.rawurlencode('13 Đường N5, Hiệp Thành, Quận 12, Hồ Chí Minh').'&output=embed',
            'facebook' => 'https://www.facebook.com/',
            'youtube' => 'https://www.youtube.com/',
            'tiktok' => 'https://www.tiktok.com/',
            'bank_name' => 'Vietcombank',
            'bank_account' => '0123456789',
            'bank_owner' => 'CONG TY CP BDS LBTECH LAND',
            'bank_branch' => 'CN Thành phố Hồ Chí Minh',
            'transfer_prefix' => 'LBTECH',
            'min_topup' => '50000',
            'signup_bonus' => '50000',
            'auto_approve' => '0',
            'posting_rules' => "1. Mỗi tin chỉ đăng một bất động sản, thông tin trung thực.\n2. Không đăng tin trùng lặp, không chèn số điện thoại vào tiêu đề.\n3. Hình ảnh phải là ảnh thực tế của bất động sản.\n4. Tin vi phạm sẽ bị từ chối và hoàn 100% phí vào ví.\n5. Thời gian hiển thị tính từ lúc tin được duyệt.",
            'seo_title' => config('app.name').' – Mua bán, cho thuê nhà đất',
            'seo_description' => 'Đăng tin mua bán, cho thuê nhà đất, căn hộ chung cư, đất nền trên toàn quốc. Tin VIP nổi bật, kiểm duyệt nhanh.',
        ];
        DB::table('site_settings')->insertOrIgnore(collect($settings)->map(fn ($value, $key) => compact('key', 'value'))->values()->all());
    }

    private function packages(): array
    {
        $rows = [
            ['Tin thường', null, '#6b7280', 2000, 0, 7, 'Hiển thị trong danh sách theo thời gian đăng.', 10],
            ['VIP 3', 'VIP 3', '#f08a24', 10000, 10, 7, 'Nhãn VIP, ưu tiên trên tin thường.', 20],
            ['VIP 2', 'VIP 2', '#ea580c', 20000, 20, 7, 'Nhãn VIP cam, hiển thị trên trang chủ.', 30],
            ['VIP 1', 'VIP 1', '#e03c31', 35000, 30, 7, 'Nhãn đỏ, tiêu đề in hoa, ưu tiên cao.', 40],
            ['VIP Đặc biệt', 'VIP 5', '#b91c1c', 50000, 50, 10, 'Luôn đứng đầu trang chủ và kết quả tìm kiếm.', 50],
        ];
        foreach ($rows as [$name, $badge, $color, $price, $priority, $minDays, $description, $position]) {
            DB::table('listing_packages')->updateOrInsert(['name' => $name], [
                'badge' => $badge, 'color' => $color, 'price_per_day' => $price, 'priority' => $priority,
                'min_days' => $minDays, 'description' => $description, 'position' => $position, 'active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return DB::table('listing_packages')->pluck('id', 'name')->all();
    }

    private function members(): array
    {
        if (! app()->environment('local') || config('webfx.runtime')) {
            return [];
        }
        $marketplace = app(MarketplaceService::class);
        $ids = [];
        foreach ([['Nguyễn Văn An', 'thanhvien@gmail.com', '0912345678', 5000000], ['Trần Thị Bình', 'binh.tran@gmail.com', '0987654321', 3000000]] as [$name, $email, $phone, $topup]) {
            $id = DB::table('users')->where('email', $email)->value('id');
            if (! $id) {
                $id = DB::table('users')->insertGetId([
                    'name' => $name, 'email' => $email, 'phone' => $phone, 'password' => Hash::make('123123!@A'),
                    'role' => config('cms.member_role'), 'active' => true, 'balance' => 0,
                    'created_at' => now()->subDays(20), 'updated_at' => now(),
                ]);
                $marketplace->credit($id, 50000, 'bonus', 'Quà tặng thành viên mới', ['created_by' => $id]);
                $topupId = DB::table('topup_requests')->insertGetId([
                    'user_id' => $id, 'amount' => $topup, 'method' => 'bank', 'transfer_note' => 'LBTECH '.$phone,
                    'status' => 'pending', 'created_at' => now()->subDays(15), 'updated_at' => now()->subDays(15),
                ]);
                DB::table('topup_requests')->where('id', $topupId)->update(['transfer_note' => 'LBTECH NAP'.$topupId.' '.$phone]);
                $marketplace->approveTopup($topupId, 'Đã nhận chuyển khoản');
            }
            $ids[] = $id;
        }
        // Một yêu cầu nạp đang chờ duyệt để admin thử nghiệm.
        if (! DB::table('topup_requests')->where('user_id', $ids[1])->where('status', 'pending')->exists()) {
            $pendingId = DB::table('topup_requests')->insertGetId([
                'user_id' => $ids[1], 'amount' => 500000, 'method' => 'bank', 'transfer_note' => '',
                'status' => 'pending', 'created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2),
            ]);
            DB::table('topup_requests')->where('id', $pendingId)->update(['transfer_note' => 'LBTECH NAP'.$pendingId.' 0987654321']);
        }

        return $ids;
    }

    private function listings(CmsRepository $repository, array $packages, array $members): void
    {
        $category = fn (string $name) => $repository->table('menus')->where('content_type', 'products')->where('name', $name)->value('id');
        // [tiêu đề, danh mục, loại, giá, diện tích, tỉnh, quận, địa chỉ, PN, WC, tầng, hướng, gói, ảnh[], thành viên?, trạng thái]
        $rows = [
            ['Bán căn hộ 2PN view sông Sài Gòn, full nội thất, sổ hồng lâu dài', 'Bán căn hộ chung cư', 'sale', 3450000000, 72, 'Hồ Chí Minh', 'Quận Bình Thạnh', '208 Nguyễn Hữu Cảnh, Phường 22', 2, 2, null, 'Đông Nam', 'VIP Đặc biệt', ['can-ho-1.jpg', 'can-ho-2.jpg', 'can-ho-3.jpg'], 0, 'approved'],
            ['Bán nhà mặt phố Trần Duy Hưng 80m², 5 tầng, kinh doanh sầm uất', 'Bán nhà mặt phố', 'sale', 28500000000, 80, 'Hà Nội', 'Quận Cầu Giấy', '112 Trần Duy Hưng, Trung Hòa', 5, 6, 5, 'Tây Nam', 'VIP Đặc biệt', ['nha-pho-1.jpg', 'nha-pho-2.jpg', 'nha-pho-3.jpg'], null, 'approved'],
            ['Biệt thự song lập khu compound an ninh, hồ bơi riêng, sân vườn rộng', 'Bán biệt thự, liền kề', 'sale', 18900000000, 240, 'Hồ Chí Minh', 'Thành phố Thủ Đức', 'Đường Nguyễn Văn Hưởng, Thảo Điền', 4, 5, 3, 'Đông', 'VIP 1', ['biet-thu-1.jpg', 'du-an-6.jpg'], null, 'approved'],
            ['Đất nền sổ đỏ thổ cư 100%, sát KCN, đường nhựa 12m, giá đầu tư', 'Bán đất nền dự án', 'sale', 1650000000, 125, 'Bình Dương', 'Thị xã Bến Cát', 'Đường DT741, Phường Hòa Lợi', null, null, null, 'Nam', 'VIP 1', ['dat-nen-1.jpg'], 1, 'approved'],
            ['Nhà riêng hẻm xe hơi 4x16m, 3 tầng mới đẹp, gần chợ Hạnh Thông Tây', 'Bán nhà riêng', 'sale', 6200000000, 64, 'Hồ Chí Minh', 'Quận Gò Vấp', '45/12 Quang Trung, Phường 11', 4, 3, 3, 'Bắc', 'VIP 2', ['nha-pho-4.jpg', 'can-ho-4.jpg'], 0, 'approved'],
            ['Căn hộ 3PN Vinhomes Ocean Park, ban công Đông Nam, nhận nhà ngay', 'Bán căn hộ chung cư', 'sale', 4100000000, 95, 'Hà Nội', 'Huyện Gia Lâm', 'Vinhomes Ocean Park, Đa Tốn', 3, 2, null, 'Đông Nam', 'VIP 2', ['can-ho-5.jpg', 'can-ho-6.jpg'], null, 'approved'],
            ['Bán lô đất view biển Nha Trang, pháp lý chuẩn, thích hợp xây homestay', 'Bán đất', 'sale', 5300000000, 150, 'Khánh Hòa', 'Thành phố Nha Trang', 'Đường Phạm Văn Đồng, Vĩnh Hòa', null, null, null, 'Đông', 'VIP 3', ['du-an-9.jpg'], null, 'approved'],
            ['Nhà phố liền kề khu đô thị mới, 1 trệt 2 lầu, tặng nội thất cơ bản', 'Bán biệt thự, liền kề', 'sale', 3900000000, 90, 'Đồng Nai', 'Thành phố Biên Hòa', 'KĐT Swan Bay, Đại Phước', 3, 3, 3, 'Tây Bắc', 'Tin thường', ['du-an-8.jpg'], null, 'approved'],
            ['Căn hộ studio trung tâm Đà Nẵng, view sông Hàn, đang cho thuê 9tr/tháng', 'Bán căn hộ chung cư', 'sale', 1980000000, 42, 'Đà Nẵng', 'Quận Hải Châu', 'Đường Bạch Đằng, Phước Ninh', 1, 1, null, 'Đông', 'VIP 3', ['can-ho-2.jpg', 'du-an-5.jpg'], 1, 'approved'],
            ['Bán nhà cấp 4 mặt tiền đường 7m, sổ riêng, giá chính chủ', 'Bán nhà riêng', 'sale', 2350000000, 100, 'Long An', 'Huyện Đức Hòa', 'Đường Hồ Chí Minh, TT Hậu Nghĩa', 2, 1, 1, 'Nam', 'Tin thường', ['nha-pho-2.jpg'], 0, 'pending'],
            ['Cho thuê căn hộ 2PN full nội thất gần Lotte Mart, vào ở ngay', 'Cho thuê căn hộ chung cư', 'rent', 14000000, 70, 'Hồ Chí Minh', 'Quận 7', 'Đường Nguyễn Hữu Thọ, Tân Hưng', 2, 2, null, 'Đông Bắc', 'VIP 1', ['can-ho-3.jpg', 'can-ho-1.jpg'], 0, 'approved'],
            ['Cho thuê văn phòng hạng B 150m² mặt tiền, có hầm xe, miễn phí 1 tháng', 'Cho thuê văn phòng', 'rent', 52000000, 150, 'Hồ Chí Minh', 'Quận 3', 'Đường Võ Văn Tần, Phường 6', null, 2, null, 'Tây', 'VIP Đặc biệt', ['du-an-2.jpg', 'du-an-4.jpg'], null, 'approved'],
            ['Cho thuê nhà nguyên căn 4x18m, 1 trệt 2 lầu, phù hợp ở + làm văn phòng', 'Cho thuê nhà riêng', 'rent', 18000000, 72, 'Hồ Chí Minh', 'Quận Tân Bình', 'Đường Cộng Hòa, Phường 13', 4, 3, 3, 'Nam', 'VIP 2', ['nha-pho-3.jpg'], 1, 'approved'],
            ['Cho thuê mặt bằng kinh doanh góc 2 mặt tiền phố cổ, lượng khách đông', 'Cho thuê mặt bằng kinh doanh', 'rent', 65000000, 90, 'Hà Nội', 'Quận Hoàn Kiếm', 'Phố Hàng Bạc, Hàng Buồm', null, 1, 2, 'Đông Nam', 'VIP 1', ['nha-pho-1.jpg'], null, 'approved'],
            ['Phòng trọ mới xây có gác, giờ giấc tự do, gần ĐH Công nghiệp', 'Cho thuê phòng trọ', 'rent', 3200000, 25, 'Hồ Chí Minh', 'Quận Gò Vấp', 'Đường Nguyễn Văn Bảo, Phường 4', 1, 1, null, null, 'Tin thường', ['can-ho-4.jpg'], 0, 'approved'],
            ['Cho thuê căn hộ cao cấp 3PN Masteri, view hồ bơi, hợp đồng dài hạn', 'Cho thuê căn hộ chung cư', 'rent', 25000000, 110, 'Hà Nội', 'Quận Nam Từ Liêm', 'Masteri West Heights, Tây Mỗ', 3, 2, null, 'Tây Nam', 'VIP 3', ['can-ho-6.jpg', 'can-ho-5.jpg'], null, 'approved'],
            ['Cho thuê nhà mặt phố Lê Duẩn 5x20m, phù hợp showroom, ngân hàng', 'Cho thuê nhà mặt phố', 'rent', 0, 100, 'Đà Nẵng', 'Quận Thanh Khê', 'Đường Lê Duẩn, Tân Chính', 3, 3, 3, 'Bắc', 'Tin thường', ['nha-pho-4.jpg'], null, 'approved'],
            ['Cho thuê căn hộ mini giá rẻ, đầy đủ tiện nghi cơ bản', 'Cho thuê căn hộ chung cư', 'rent', 4500000, 30, 'Hồ Chí Minh', 'Quận 12', 'Đường Hà Huy Giáp, Thạnh Lộc', 1, 1, null, null, 'Tin thường', ['can-ho-2.jpg'], 1, 'rejected'],
        ];
        $base = now()->subDays(12);
        foreach ($rows as $index => [$name, $categoryName, $type, $price, $area, $province, $district, $address, $beds, $baths, $floors, $direction, $packageName, $images, $memberIndex, $status]) {
            $slug = Str::slug($name);
            if ($repository->table('products')->where('slug', $slug)->exists()) {
                continue;
            }
            $userId = $memberIndex === null ? null : ($members[$memberIndex] ?? null);
            $member = $userId ? DB::table('users')->find($userId) : null;
            $package = DB::table('listing_packages')->where('name', $packageName)->first();
            $days = max(30, $package->min_days);
            $fee = $userId ? (int) $package->price_per_day * $days : 0;
            $published = $base->copy()->addHours($index * 14);
            $gallery = array_map(fn ($file) => $this->img($file), $images);
            $text = $this->description($type, $categoryName, $area, $beds, $baths, $district, $province, $direction);
            $id = $repository->table('products')->insertGetId([
                'name' => $name, 'slug' => $slug, 'menu_id' => $category($categoryName), 'listing_type' => $type,
                'price' => $price, 'area' => $area, 'province' => $province, 'district' => $district, 'address' => $address,
                'bedrooms' => $beds, 'toilets' => $baths, 'floors' => $floors, 'direction' => $direction,
                'legal' => $type === 'sale' ? 'Sổ đỏ/Sổ hồng' : null,
                'contact_name' => $member->name ?? 'Phòng kinh doanh '.config('app.name'),
                'contact_phone' => $member->phone ?? '0901234567', 'contact_email' => $member->email ?? 'hotro@lbtechland.vn',
                'image' => $gallery[0], 'gallery' => json_encode($gallery, JSON_UNESCAPED_SLASHES),
                'description' => '<p>'.e(Str::limit($text[0], 200)).'</p>',
                'body' => '<p>'.implode('</p><p>', array_map('e', $text)).'</p>',
                'package_id' => $package->id, 'priority' => $package->priority, 'days' => $days, 'fee' => $fee,
                'fee_settled' => $status !== 'pending', 'user_id' => $userId, 'status' => $status,
                'reject_reason' => $status === 'rejected' ? 'Ảnh chưa phải ảnh thực tế của bất động sản. Vui lòng cập nhật ảnh và đăng lại.' : null,
                'active' => $status !== 'rejected', 'published_at' => $published,
                'expires_at' => $status === 'approved' ? $published->copy()->addDays($days) : null,
                'views' => 40 + ($index * 37) % 400, 'created_at' => $published, 'updated_at' => $published,
            ]);
            if ($userId && $fee > 0) {
                $marketplace = app(MarketplaceService::class);
                $marketplace->debit($userId, $fee, 'listing_fee', 'Phí đăng tin #'.$id.' – '.$package->name.' × '.$days.' ngày', ['product_id' => $id, 'created_by' => $userId]);
                if ($status === 'rejected') {
                    $marketplace->credit($userId, $fee, 'refund', 'Hoàn phí tin #'.$id.' bị từ chối', ['product_id' => $id]);
                }
            }
        }
    }

    private function description(string $type, string $category, float $area, ?int $beds, ?int $baths, string $district, string $province, ?string $direction): array
    {
        $what = mb_strtolower(Str::after($category, $type === 'rent' ? 'Cho thuê ' : 'Bán '));

        return array_filter([
            ($type === 'rent' ? 'Cần cho thuê ' : 'Chính chủ cần bán ').$what.' diện tích '.rtrim(rtrim(number_format($area, 1, ',', '.'), '0'), ',').'m² tại '.$district.', '.$province.'. Vị trí thuận tiện di chuyển, khu dân cư hiện hữu, an ninh tốt.',
            $beds ? 'Thiết kế gồm '.$beds.' phòng ngủ, '.($baths ?: 1).' phòng vệ sinh, phòng khách và bếp thông thoáng, đón ánh sáng tự nhiên'.($direction ? ', hướng '.$direction : '').'.' : null,
            'Tiện ích xung quanh: trường học, chợ, siêu thị, bệnh viện trong bán kính 2km. Đường trước nhà rộng, xe hơi ra vào dễ dàng.',
            $type === 'rent' ? 'Hợp đồng tối thiểu 6 tháng, đặt cọc 1 tháng, thanh toán hàng tháng. Có thể xem nhà mọi lúc.' : 'Pháp lý rõ ràng, hỗ trợ vay ngân hàng đến 70% giá trị. Giá có thương lượng cho khách thiện chí.',
            'Liên hệ ngay để được tư vấn và dẫn đi xem thực tế.',
        ]);
    }

    private function posts(CmsRepository $repository): void
    {
        $menu = fn (string $name) => $repository->table('menus')->where('content_type', 'posts')->where('name', $name)->value('id');
        $posts = [
            ['Thị trường bất động sản cuối năm: nhu cầu căn hộ vừa túi tiền tăng mạnh', 'Thị trường', 'tin-1.jpg'],
            ['5 bước kiểm tra pháp lý trước khi đặt cọc mua nhà', 'Pháp lý nhà đất', 'tin-2.jpg'],
            ['Kinh nghiệm thuê căn hộ lần đầu: những điều khoản cần đọc kỹ', 'Kinh nghiệm mua bán', 'tin-3.jpg'],
            ['Nên mua nhà khi nào? Cách xác định "thời điểm chín muồi" tài chính', 'Kinh nghiệm mua bán', 'tin-4.jpg'],
            ['Chọn hướng nhà hợp tuổi: hiểu đúng để không mất cơ hội tốt', 'Phong thủy', 'tin-5.jpg'],
            ['Dòng tiền đổ về bất động sản khu vực vệ tinh: cơ hội và rủi ro', 'Thị trường', 'gioi-thieu.jpg'],
        ];
        foreach ($posts as $index => [$name, $menuName, $image]) {
            $slug = Str::slug($name);
            $intro = 'Bài viết tổng hợp những điểm quan trọng giúp bạn ra quyết định chính xác hơn khi giao dịch bất động sản.';
            $repository->table('posts')->insertOrIgnore([
                'name' => $name, 'slug' => $slug, 'menu_id' => $menu($menuName), 'kind' => 'blog',
                'image' => $this->img($image), 'description' => '<p>'.$intro.'</p>',
                'body' => '<h2>Tổng quan</h2><p>'.$intro.' Thị trường đang có nhiều chuyển biến, người mua ngày càng chú trọng pháp lý, vị trí và tiện ích.</p>'
                    .'<figure class="image"><img src="'.$this->img($image).'" alt="'.e($name).'"></figure>'
                    .'<h2>Những lưu ý chính</h2><ul><li>Xác định rõ nhu cầu và ngân sách.</li><li>Kiểm tra quy hoạch, sổ đỏ/sổ hồng tại cơ quan chức năng.</li><li>So sánh giá khu vực trên nhiều kênh.</li><li>Chỉ đặt cọc khi đã xem thực tế và có hợp đồng rõ ràng.</li></ul>'
                    .'<h2>Kết luận</h2><p>Hãy liên hệ đội ngũ tư vấn của chúng tôi để được hỗ trợ miễn phí.</p>',
                'active' => true, 'published_at' => now()->subDays(10 - $index), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function banners(CmsRepository $repository): void
    {
        if (! $repository->table('banners')->exists()) {
            $repository->save('banners', null, ['name' => 'Kênh mua bán, cho thuê nhà đất uy tín – Gửi niềm tin, trao giá trị', 'placement' => 'slide', 'image' => $this->img('du-an-10.jpg'), 'url' => '/nha-dat-ban', 'position' => 10, 'active' => true]);
            $repository->save('banners', null, ['name' => 'Đăng tin VIP – tiếp cận khách hàng nhanh gấp 5 lần', 'placement' => 'advertisement', 'image' => $this->img('du-an-3.jpg'), 'url' => '/bao-gia-tin-vip', 'position' => 10, 'active' => true]);
        }
    }
}
