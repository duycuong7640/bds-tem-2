<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SampleMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private int $vip;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(SampleMenuSeeder::class);
        $this->vip = DB::table('listing_packages')->insertGetId([
            'name' => 'VIP 1', 'badge' => 'VIP 1', 'color' => '#e03c31', 'price_per_day' => 10000,
            'priority' => 30, 'min_days' => 7, 'position' => 1, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function member(int $balance = 0): User
    {
        return User::factory()->create(['role' => 'member', 'active' => true, 'balance' => $balance, 'phone' => '0912345678']);
    }

    private function listingPayload(array $overrides = []): array
    {
        return $overrides + [
            'category_id' => DB::table('menus')->where('name', 'Bán nhà riêng')->value('id'),
            'name' => 'Bán nhà riêng ngõ rộng 45m2 gần trung tâm', 'body' => str_repeat('Nhà đẹp, sổ đỏ chính chủ. ', 4),
            'province' => 'Hà Nội', 'district' => 'Đống Đa', 'address' => '12 Trường Chinh',
            'price_unit' => 'ty', 'price_value' => '5.5', 'area' => '45',
            'images' => [UploadedFile::fake()->image('nha.jpg', 800, 600)],
            'contact_name' => 'Chủ nhà', 'contact_phone' => '0912345678',
            'package_id' => $this->vip, 'days' => 15, 'agree' => '1',
        ];
    }

    public function test_member_registers_and_cannot_enter_admin(): void
    {
        DB::table('site_settings')->insert(['key' => 'signup_bonus', 'value' => '50000']);
        $this->post('/dang-ky', ['name' => 'A', 'phone' => '123', 'email' => 'x', 'password' => 'short'])
            ->assertSessionHasErrors(['name', 'phone', 'email', 'password', 'agree']);
        $this->post('/dang-ky', [
            'name' => 'Người dùng', 'phone' => '0987654321', 'email' => 'User@Example.com',
            'password' => 'matkhau123', 'password_confirmation' => 'matkhau123', 'agree' => '1',
        ])->assertRedirect('/tai-khoan');
        $user = DB::table('users')->where('email', 'user@example.com')->first();
        $this->assertSame('member', $user->role);
        $this->assertSame(50000, (int) $user->balance);
        $this->get('/admin/dashboard')->assertRedirect('/tai-khoan');

        auth()->logout();
        $this->post('/admin/login', ['email' => 'user@example.com', 'password' => 'matkhau123'])->assertSessionHasErrors('email');
        $this->post('/dang-nhap', ['login' => '0987654321', 'password' => 'matkhau123'])->assertRedirect('/tai-khoan');
    }

    public function test_posting_charges_the_wallet_and_rejection_refunds_it(): void
    {
        $member = $this->member(200000);
        $balance = fn () => (int) DB::table('users')->where('id', $member->id)->value('balance');

        // 30 ngày × 10.000 = 300.000 > số dư → báo lỗi, không tạo tin
        $this->actingAs($member)->from('/tai-khoan/dang-tin')->post('/tai-khoan/dang-tin', $this->listingPayload(['days' => 30]))
            ->assertRedirect('/tai-khoan/dang-tin')->assertSessionHasErrors('package_id');
        $this->assertDatabaseCount('products', 0);

        $this->post('/tai-khoan/dang-tin', $this->listingPayload())->assertRedirect('/tai-khoan');
        $listing = DB::table('products')->first();
        $this->assertSame('pending', $listing->status);
        $this->assertSame(5500000000, (int) $listing->price);
        $this->assertSame(50000, $balance());
        $this->get('/bat-dong-san/'.$listing->slug)->assertNotFound();

        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin)->post('/admin/listings/'.$listing->id.'/approve')->assertRedirect();
        $approved = DB::table('products')->find($listing->id);
        $this->assertSame('approved', $approved->status);
        $this->assertNotNull($approved->expires_at);
        $this->get('/bat-dong-san/'.$listing->slug)->assertOk()->assertSee('5,5 tỷ');

        // Tin thứ hai: 7 ngày = 70.000 > 50.000 → không đủ tiền
        $second = $this->listingPayload(['name' => 'Bán căn hộ thứ hai rất đẹp và rộng', 'days' => 7]);
        $this->actingAs($member)->post('/tai-khoan/dang-tin', $second)->assertSessionHasErrors('package_id');
        $this->assertSame(1, DB::table('products')->count());

        DB::table('users')->where('id', $member->id)->update(['balance' => 70000]);
        $second['images'] = [UploadedFile::fake()->image('nha2.jpg', 800, 600)];
        $this->post('/tai-khoan/dang-tin', $second)->assertRedirect('/tai-khoan');
        $this->assertSame(0, $balance());
        $record = DB::table('products')->latest('id')->first();

        $adminPayload = [
            'name' => $record->name, 'menu_id' => $record->menu_id, 'image' => $record->image,
            'description' => '<p>Tóm tắt</p>', 'body' => '<p>Nội dung</p>', 'active' => '1', 'price' => $record->price,
            'listing_type' => 'sale', 'province' => 'Hà Nội', 'address' => $record->address, 'area' => 45,
            'contact_name' => 'Chủ nhà', 'contact_phone' => '0912345678', 'package_id' => $this->vip, 'days' => 7,
        ];
        $this->actingAs($admin)->put('/admin/content/products/'.$record->id, $adminPayload + ['status' => 'rejected'])
            ->assertSessionHasErrors('reject_reason');
        $this->assertSame(0, $balance());
        $this->put('/admin/content/products/'.$record->id, $adminPayload + ['status' => 'rejected', 'reject_reason' => 'Ảnh không rõ'])
            ->assertRedirect('/admin/content/products');
        $this->assertSame(70000, $balance());
        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $member->id, 'type' => 'refund', 'amount' => 70000]);
        $this->assertDatabaseHas('products', ['id' => $record->id, 'status' => 'rejected', 'reject_reason' => 'Ảnh không rõ']);
    }

    public function test_members_only_edit_their_own_listings(): void
    {
        $owner = $this->member(500000);
        $this->actingAs($owner)->post('/tai-khoan/dang-tin', $this->listingPayload())->assertRedirect('/tai-khoan');
        $id = DB::table('products')->value('id');
        $other = User::factory()->create(['role' => 'member', 'active' => true, 'phone' => '0911111111']);
        $this->actingAs($other)->get('/tai-khoan/tin/'.$id.'/sua')->assertNotFound();
        $this->delete('/tai-khoan/tin/'.$id)->assertNotFound();

        // Chủ tin xóa khi đang chờ duyệt → hoàn phí
        $this->actingAs($owner)->delete('/tai-khoan/tin/'.$id)->assertRedirect('/tai-khoan');
        $this->assertSame(500000, (int) DB::table('users')->where('id', $owner->id)->value('balance'));
    }

    public function test_topup_request_is_credited_after_admin_approval(): void
    {
        DB::table('site_settings')->insert(['key' => 'min_topup', 'value' => '50000']);
        $member = $this->member();
        $this->actingAs($member)->post('/tai-khoan/nap-tien', ['amount' => 1000])->assertSessionHasErrorsIn('topup', 'amount');
        $this->post('/tai-khoan/nap-tien', ['amount' => 200000])->assertRedirect('/tai-khoan/vi');
        $topup = DB::table('topup_requests')->first();
        $this->assertStringContainsString('NAP'.$topup->id, $topup->transfer_note);

        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin)->get('/admin/topups')->assertOk()->assertSee($topup->transfer_note);
        $this->post('/admin/topups/'.$topup->id.'/approve')->assertRedirect();
        $this->post('/admin/topups/'.$topup->id.'/approve')->assertSessionHasErrors('topup');
        $this->assertSame(200000, (int) DB::table('users')->where('id', $member->id)->value('balance'));
        $this->get('/admin/members/'.$member->id)->assertOk()->assertSee('200.000 ₫');
    }

    public function test_lookup_requires_matching_code_and_phone(): void
    {
        $member = $this->member(500000);
        $this->actingAs($member)->post('/tai-khoan/dang-tin', $this->listingPayload());
        $id = DB::table('products')->value('id');
        auth()->logout();
        $this->post('/tra-cuu-tin-dang', ['code' => $id, 'phone' => '0900000000'])->assertSessionHasErrorsIn('lookup', 'code');
        $this->post('/tra-cuu-tin-dang', ['code' => '#'.$id, 'phone' => '0912345678'])->assertOk()->assertSee('Đang chờ duyệt');
    }
}
