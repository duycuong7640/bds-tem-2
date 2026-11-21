<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SampleMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'active' => true]);
    }

    private function editorialFields(string $resource): array
    {
        $this->seed(SampleMenuSeeder::class);

        $listing = $resource === 'products' ? [
            'listing_type' => 'sale', 'status' => 'approved', 'province' => 'Hà Nội', 'address' => '1 Tràng Tiền',
            'area' => 50, 'contact_name' => 'Người bán', 'contact_phone' => '0912345678',
        ] : [];

        return $listing + [
            'menu_id' => DB::table('menus')->where('content_type', $resource)->value('id'),
            'image' => '/cover.png', 'description' => '<p>Tóm tắt</p>', 'body' => '<p>Nội dung</p>',
        ];
    }

    public function test_editorial_forms_require_all_five_main_fields(): void
    {
        $this->actingAs($this->staff('admin'));
        foreach (['products', 'posts'] as $resource) {
            $url = '/admin/content/'.$resource;
            $required = ['name', 'description', 'body', 'image', 'menu_id'];
            $this->from($url.'/create')->post($url, [
                'description' => '<p>&nbsp;</p>', 'body' => '<p><br></p><script>alert(1)</script>',
            ])->assertRedirect($url.'/create')->assertSessionHasErrors($required);
            $this->assertDatabaseCount($resource, 0);
        }
    }

    public function test_permissions_apply_to_direct_urls_and_revocation(): void
    {
        $editor = $this->staff('editor');
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->actingAs($editor)->get('/admin/permissions')->assertForbidden();
        $this->putJson('/admin/permissions', ['roles' => ['editor' => ['users.edit', 'roles.manage']]])->assertForbidden();
        $this->postJson('/admin/content/users', ['role' => 'admin'])->assertForbidden();
        $this->get('/admin/content/posts/create')->assertOk();
        $this->post('/admin/content/users', [])->assertForbidden();

        $this->actingAs($this->staff('admin'))->put('/admin/permissions', [
            'roles' => ['editor' => ['posts.view'], 'manager' => ['menus.edit']],
        ])->assertRedirect('/admin/permissions');
        $this->actingAs($editor)->get('/admin/content/posts')->assertOk();
        $this->post('/admin/content/posts', ['name' => 'Bài thử'])->assertForbidden();
        $this->actingAs($this->staff('manager'))->get('/admin/content/menus')->assertOk();
    }

    public function test_nested_menu_can_neither_be_cycled_nor_removed_with_children(): void
    {
        $this->actingAs($this->staff('admin'));
        $root = ['name' => 'Cha', 'url' => '/', 'parent_id' => null, 'position' => 1, 'active' => '1'];
        $this->post('/admin/content/menus', $root)->assertRedirect('/admin/content/menus');
        $parent = DB::table('menus')->value('id');
        $this->post('/admin/content/menus', ['name' => 'Con', 'parent_id' => $parent] + $root)->assertRedirect();
        $child = DB::table('menus')->where('name', 'Con')->value('id');

        $this->put('/admin/content/menus/'.$parent, ['parent_id' => $child] + $root)->assertSessionHasErrors('parent_id');
        $this->delete('/admin/content/menus/'.$parent)->assertSessionHasErrors('menu');
        $this->get('/admin/content/menus')->assertOk();
        $this->get('/')->assertOk()->assertSee('Con');
    }

    public function test_products_posts_and_settings_show_on_the_public_website(): void
    {
        $this->actingAs($this->staff('admin'));
        $this->post('/admin/content/posts', [
            'name' => 'Bài viết mẫu', 'active' => '1',
            'body' => '<p><strong>Nội dung</strong></p><script>alert(1)</script>',
        ] + $this->editorialFields('posts'))->assertRedirect('/admin/content/posts');
        $this->post('/admin/content/products', [
            'name' => 'Sản phẩm', 'active' => '1', 'price' => 200000, 'sku' => 'P200',
        ] + $this->editorialFields('products'))->assertRedirect('/admin/content/products');
        $this->put('/admin/settings', ['site_name' => 'Website mới'])->assertRedirect('/admin/settings');
        $this->get('/')->assertOk()->assertSee('Website mới')->assertSee('200.000 ₫');
        $this->get('/bai-viet/bai-viet-mau')->assertOk()->assertSee('<strong>Nội dung</strong>', false)->assertDontSee('<script>', false);
        $this->get('/bat-dong-san/san-pham')->assertOk()->assertSee('200.000 ₫');
        $this->get('/admin/settings')->assertOk();
    }

    public function test_menu_and_site_seo_fields_are_saved_and_rendered(): void
    {
        $this->actingAs($this->staff('admin'));
        $this->get('/admin/settings')->assertOk()->assertSee('name="seo_title"', false);
        $this->get('/admin/content/menus/create')->assertOk()->assertSee('name="seo_description"', false);
        $this->put('/admin/settings', [
            'site_name' => 'Website mẫu', 'seo_title' => 'Tiêu đề website', 'seo_description' => 'Mô tả website',
        ])->assertRedirect('/admin/settings');
        $this->get('/')->assertOk()->assertSee('<title>Tiêu đề website</title>', false)
            ->assertSee('content="Mô tả website"', false);
        $this->post('/admin/content/menus', [
            'name' => 'Trang chủ', 'url' => '/', 'position' => 0, 'active' => '1',
            'seo_title' => 'Tiêu đề menu', 'seo_description' => 'Mô tả menu',
        ])->assertRedirect('/admin/content/menus');
        $this->get('/')->assertOk()->assertSee('<title>Tiêu đề menu</title>', false)
            ->assertSee('content="Mô tả menu"', false);
    }

    public function test_admin_pagination_has_page_numbers_ellipsis_and_navigation(): void
    {
        $this->actingAs($this->staff('admin'));
        $now = now();
        DB::table('products')->insert(array_map(fn ($number) => [
            'name' => 'Mẫu '.$number, 'slug' => 'mau-'.$number, 'created_at' => $now, 'updated_at' => $now,
        ], range(1, 180)));
        $this->get('/admin/content/products?q=M&page=8')->assertOk()
            ->assertSee('pager-ellipsis')->assertSee('rel="prev"', false)->assertSee('rel="next"', false)
            ->assertSee('q=M&amp;page=7', false)->assertSee('aria-current="page">8', false);

        $this->seed(SampleMenuSeeder::class);
        DB::table('menus')->insert(array_map(fn ($number) => [
            'name' => 'Menu '.$number, 'created_at' => $now, 'updated_at' => $now,
        ], range(1, 14)));
        $this->get('/admin/content/menus?page=2')->assertOk()
            ->assertSee('rel="prev"', false)->assertSee('aria-current="page">2', false);
    }

    public function test_all_admin_lists_and_mutations_reject_unauthorized_visitors(): void
    {
        foreach (array_keys(config('cms.resources')) as $resource) {
            $url = '/admin/content/'.$resource;
            $this->getJson($url)->assertUnauthorized();
            $this->postJson($url)->assertUnauthorized();
            $this->putJson($url.'/1')->assertUnauthorized();
            $this->deleteJson($url.'/1')->assertUnauthorized();
        }

        $readOnly = $this->staff('editor');
        $this->actingAs($readOnly)->putJson('/admin/settings')->assertForbidden();
        $this->putJson('/admin/permissions')->assertForbidden();
        $this->deleteJson('/admin/content/posts/1')->assertForbidden();
    }

    public function test_sample_menu_tree_is_idempotent(): void
    {
        $this->seed(SampleMenuSeeder::class);
        $this->seed(SampleMenuSeeder::class);
        $this->assertDatabaseCount('menus', 22);
        $parent = DB::table('menus')->where('name', 'Nhà đất bán')->value('id');
        $this->assertDatabaseHas('menus', ['name' => 'Bán căn hộ chung cư', 'parent_id' => $parent, 'content_type' => 'products']);
        $this->get('/')->assertOk()->assertSee('Báo giá tin VIP')->assertSee('Cho thuê văn phòng');
    }

    public function test_slugs_can_be_edited_but_duplicates_cannot_be_saved(): void
    {
        $this->actingAs($this->staff('admin'));

        foreach (['products', 'posts'] as $resource) {
            $url = '/admin/content/'.$resource;
            $fields = ['name' => 'Điện thoại mới', 'active' => '1', 'price' => 10] + $this->editorialFields($resource);
            $this->post($url, $fields)->assertRedirect($url);
            $this->post($url, $fields)->assertRedirect($url);
            $this->assertDatabaseHas($resource, ['slug' => 'dien-thoai-moi-2']);
            $id = DB::table($resource)->where('slug', 'dien-thoai-moi')->value('id');
            $this->put($url.'/'.$id, ['slug' => 'dien-thoai-moi'] + $fields)->assertRedirect($url);
            $this->from($url.'/'.$id.'/edit')->put($url.'/'.$id, ['slug' => 'dien-thoai-moi-2'] + $fields)
                ->assertRedirect($url.'/'.$id.'/edit')->assertSessionHasErrors('slug');
            $this->withViewErrors(['slug' => 'Slug này đã được sử dụng.'])->view('admin::field-error', ['field' => 'slug'])->assertSee('id="error-slug"', false);
            $this->put($url.'/'.$id, ['slug' => 'dien-thoai-cap-nhat'] + $fields)->assertRedirect($url);
            $this->assertDatabaseHas($resource, ['id' => $id, 'slug' => 'dien-thoai-cap-nhat']);
            $this->post($url, ['slug' => 'dien-thoai-cap-nhat'] + $fields)->assertSessionHasErrors('slug');
            $this->post($url, ['slug' => 'Không hợp lệ'] + $fields)->assertSessionHasErrors('slug');
        }
    }

    public function test_content_categories_are_filtered_by_menu_type(): void
    {
        $this->seed(SampleMenuSeeder::class);
        $this->actingAs($this->staff('admin'));
        $blog = DB::table('menus')->where('name', 'Thị trường')->value('id');
        $productMenu = DB::table('menus')->where('name', 'Bán căn hộ chung cư')->value('id');
        foreach (['posts' => [$blog, $productMenu], 'products' => [$productMenu, $blog]] as $resource => [$allowed, $denied]) {
            $fields = ['name' => 'Nội dung mẫu', 'active' => 1, 'price' => 100] + $this->editorialFields($resource);
            $this->post('/admin/content/'.$resource, ['menu_id' => $denied] + $fields)->assertSessionHasErrors('menu_id');
            $this->post('/admin/content/'.$resource, ['menu_id' => $allowed] + $fields)->assertRedirect('/admin/content/'.$resource);
            $this->assertDatabaseHas($resource, ['menu_id' => $allowed]);
        }
        $this->delete('/admin/content/menus/'.$blog)->assertSessionHasErrors('menu');
    }

    public function test_a_scheduled_post_is_public_on_its_publication_date(): void
    {
        $this->actingAs($this->staff('admin'));
        $this->seed(SampleMenuSeeder::class);
        $menu = DB::table('menus')->where('name', 'Phong thủy')->value('id');
        $fields = [
            'name' => 'Lịch xuất bản', 'menu_id' => $menu, 'active' => '1',
            'description' => '<p>Tóm tắt</p>', 'seo_title' => 'Tiêu đề SEO',
            'seo_description' => 'Mô tả SEO', 'published_at' => today()->addDay()->toDateString(),
        ];
        $this->post('/admin/content/posts', $fields + $this->editorialFields('posts'))->assertRedirect('/admin/content/posts');
        $this->get('/admin/content/posts')->assertOk()->assertSee('Đã lên lịch')->assertSee('Phong thủy');
        $this->get('/bai-viet/lich-xuat-ban')->assertNotFound();
        $this->travel(1)->day();
        $this->get('/bai-viet/lich-xuat-ban')->assertOk()->assertSee('Tiêu đề SEO')->assertSee('Mô tả SEO')->assertSee('Tóm tắt');
    }

    public function test_quick_publication_uses_the_selected_hour_and_defaults_to_now(): void
    {
        $this->travelTo(Carbon::parse('2026-11-14 08:59:59'));
        $this->actingAs($this->staff('admin'));
        $this->get('/admin/content/posts/create')->assertOk()->assertSee('name="publication_time" value="now"', false);
        $this->post('/admin/content/posts', [
            'name' => 'Hẹn giờ', 'active' => '1', 'publication_time' => 'custom', 'publication_at' => '2026-11-14T09:00',
        ] + $this->editorialFields('posts'))->assertRedirect('/admin/content/posts');
        $this->get('/bai-viet/hen-gio')->assertNotFound();
        $this->post('/admin/content/posts', [
            'name' => 'Tin mới', 'active' => '1', 'publication_time' => 'now',
        ] + $this->editorialFields('posts'))->assertRedirect('/admin/content/posts');
        $this->get('/bai-viet/tin-moi')->assertOk();
        $this->travel(1)->second();
        $this->get('/bai-viet/hen-gio')->assertOk();
        $this->assertDatabaseHas('posts', ['slug' => 'hen-gio', 'published_at' => '2026-11-14 09:00:00']);
    }

    public function test_editor_formatting_survives_sanitization(): void
    {
        $this->actingAs($this->staff('admin'));
        $this->post('/admin/content/posts', [
            'name' => 'Nội dung định dạng', 'active' => '1',
            'body' => '<p style="text-align:center;position:fixed"><span style="color:#ff0000;font-size:24px">Màu chữ</span></p><figure class="image image_resized" style="width:50%"><img src="/cover.png" onerror="alert(1)"></figure><mark class="marker-yellow">Đánh dấu</mark>',
        ] + $this->editorialFields('posts'))->assertRedirect('/admin/content/posts');
        $this->get('/bai-viet/noi-dung-dinh-dang')->assertOk()
            ->assertSee('text-align:center', false)->assertSee('font-size:24px', false)
            ->assertSee('color:#ff0000', false)->assertSee('width:50%', false)
            ->assertSee('marker-yellow', false)->assertDontSee('position:fixed', false)->assertDontSee('onerror=', false);
    }

    public function test_public_forms_deliver_data_to_the_admin(): void
    {
        $this->post('/lien-he', ['name' => 'Khách', 'email' => 'guest@example.com', 'phone' => '0912345678', 'message' => 'Chào anh, cần tư vấn'])
            ->assertRedirect('/lien-he');
        $this->from('/lien-he')->post('/lien-he', ['name' => '', 'phone' => '123', 'message' => 'x'])
            ->assertRedirect('/lien-he')->assertSessionHasErrorsIn('contact', ['name', 'email', 'phone', 'message']);
        $this->post('/dang-ky-email', ['email' => 'GUEST@example.com'])->assertRedirect();
        $this->post('/dang-ky-email', ['email' => 'guest@example.com'])->assertRedirect();
        $this->assertDatabaseCount('subscribers', 1);
        $id = DB::table('contacts')->value('id');
        $this->actingAs($this->staff('admin'))->get('/admin/dashboard')->assertOk()->assertSee('Khách');
        $this->get('/admin/content/contacts/'.$id.'/edit')->assertOk()->assertSee('Chào anh, cần tư vấn');
        $this->put('/admin/content/contacts/'.$id, ['status' => 'resolved'])->assertRedirect();
        $this->assertDatabaseHas('contacts', ['id' => $id, 'status' => 'resolved']);
    }

    public function test_admin_can_manage_accounts_and_protect_their_own_access(): void
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin);
        $fields = ['name' => 'New Manager', 'email' => 'manager@example.com', 'role' => 'manager', 'password' => 'a-strong-password', 'active' => '1'];
        $this->post('/admin/content/users', $fields)->assertRedirect('/admin/content/users');
        $account = User::where('email', 'manager@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('a-strong-password', $account->password));
        $this->put('/admin/content/users/'.$admin->id, [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'editor', 'active' => '1',
        ])->assertSessionHasErrors('role');
        $this->delete('/admin/content/users/'.$admin->id)->assertSessionHasErrors('user');
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id' => 'old-manager-session', 'user_id' => $account->id, 'payload' => '', 'last_activity' => now()->timestamp,
        ]);
        $token = $account->remember_token;
        $this->put('/admin/content/users/'.$account->id, ['active' => '0', 'password' => null] + $fields)->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'old-manager-session']);
        $this->assertNotSame($token, $account->fresh()->remember_token);
        $this->flushSession();
        $this->actingAs($account->fresh())->get('/admin/dashboard')->assertForbidden();
    }

    public function test_editor_assets_and_media_upload_require_the_correct_permissions(): void
    {
        $this->get('/admin/editor/posts.js')->assertRedirect('/admin/login');
        $this->get('/admin/media')->assertRedirect('/admin/login');
        $this->postJson('/admin/media/upload')->assertUnauthorized();
        $this->get('/studio/editor.js')->assertNotFound();
        $this->actingAs($this->staff('editor'))->get('/admin/editor/products.js')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->staff('admin'))->put('/admin/permissions', [
            'roles' => ['editor' => ['products.view', 'media.view']],
        ])->assertRedirect();
        $this->actingAs($this->staff('editor'))->get('/admin/editor/products.js')->assertForbidden();
        $this->postJson('/admin/media/upload')->assertForbidden();
        $this->actingAs($this->staff('admin'));
        Storage::fake('public');
        $this->post('/admin/media/upload', [
            'upload' => [UploadedFile::fake()->createWithContent('test.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>')],
        ])->assertOk()->assertJsonCount(1);
        $this->assertSame([], Storage::disk('public')->files('media/library'));

        $php = '<?php echo "upload-marker"; ?>';
        $image = UploadedFile::fake()->image('banner.png');
        $bytes = file_get_contents($image->getPathname());
        foreach ([
            'script.php' => $php,
            'fake.png' => $php,
            'document.pdf' => '%PDF-1.7',
            'banner.php.png' => $bytes.$php,
            'mismatch.jpg' => $bytes,
        ] as $name => $content) {
            $file = UploadedFile::fake()->createWithContent($name, $content);
            $this->post('/admin/media/upload', ['upload' => [$file]])->assertOk()->assertJsonCount(1);
        }
        $this->assertSame([], Storage::disk('public')->files('media/library'));

        $polyglot = UploadedFile::fake()->createWithContent('banner.png', $bytes.$php);
        $this->post('/admin/media/upload', ['upload' => [$polyglot]])->assertOk()->assertContent('"OK"');
        $stored = Storage::disk('public')->files('media/library');
        $this->assertCount(1, $stored);
        $this->assertMatchesRegularExpression('~^media/library/[0-9a-f-]{36}\.png$~', $stored[0]);
        $this->assertStringNotContainsString($php, Storage::disk('public')->get($stored[0]));

        $header = 'IHDR'.pack('NNCCCCC', 4001, 3000, 8, 2, 0, 0, 0);
        $largeImage = UploadedFile::fake()->createWithContent(
            'large.png', "\x89PNG\r\n\x1a\n".pack('N', 13).$header.pack('N', crc32($header)),
        );
        $this->post('/admin/media/upload', ['upload' => [$largeImage]])->assertOk()->assertSee('12 megapixel');
        $this->assertSame($stored, Storage::disk('public')->files('media/library'));

        $this->getJson('/admin/media/jsonitems?type%5B%5D=image')->assertBadRequest();
        $this->withSession(['password_hash_web' => 'stale-session-hash'])
            ->get('/admin/editor/posts.js')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_product_uses_the_editorial_form_and_publication_schedule(): void
    {
        $this->travelTo(Carbon::parse('2026-11-14 08:59:59'));
        $this->actingAs($this->staff('admin'))->get('/admin/content/products/create')->assertOk()
            ->assertSee('ckeditor-mini')->assertSee('ckeditor-full')->assertSee('field-price')->assertSee('field-sku');
        $this->post('/admin/content/products', [
            'name' => 'Sản phẩm mới', 'active' => '1', 'price' => 10000, 'sku' => 'NEW10',
            'publication_time' => 'custom', 'publication_at' => '2026-11-14T09:00',
            'description' => '<p>Tóm tắt sản phẩm</p>', 'seo_title' => 'SEO sản phẩm',
        ] + $this->editorialFields('products'))->assertRedirect('/admin/content/products');
        $this->get('/bat-dong-san/san-pham-moi')->assertNotFound();
        $this->travel(1)->second();
        $this->get('/bat-dong-san/san-pham-moi')->assertOk()->assertSee('10.000 ₫')
            ->assertSee('Nội dung')->assertSee('SEO sản phẩm');
    }

    public function test_admin_can_open_media_and_reject_directory_traversal(): void
    {
        $this->actingAs($this->staff('admin'))->get('/admin/media?type=image')->assertOk();
        $this->get('/admin/media/jsonitems?type=image&working_dir=%2Flibrary%2F..%2F..')->assertBadRequest();
        $this->post('/admin/media/upload', [])->assertOk()->assertJsonCount(1);
        Storage::fake('public');
        $this->post('/admin/media/newfolder', ['name' => 'slides'])->assertOk()->assertSee('OK');
        $this->post('/admin/media/upload', [
            'working_dir' => '/library/slides', 'upload' => [UploadedFile::fake()->image('banner.png')],
        ])->assertOk()->assertContent('"OK"');
        $images = Storage::disk('public')->files('media/library/slides');
        $this->assertCount(1, $images);
        $this->post('/admin/media/delete', [
            'working_dir' => '/library/slides', 'items' => [basename($images[0])],
        ])->assertOk()->assertSee('OK');
        Storage::disk('public')->assertMissing($images[0]);
        Storage::disk('public')->put('media/library/slides/nested/banner.png', 'sample');
        $this->postJson('/admin/media/delete-folder', ['folder' => '..'])->assertUnprocessable();
        $this->postJson('/admin/media/delete-folder', ['folder' => 'slides', 'working_dir' => '/library/..'])->assertBadRequest();
        $this->post('/admin/media/delete-folder', ['folder' => 'slides'])->assertOk();
        Storage::disk('public')->assertMissing('media/library/slides/nested/banner.png');
        $this->actingAs($this->staff('editor'))->post('/admin/media/delete-folder', ['folder' => 'slides'])->assertForbidden();
        $this->actingAs($this->staff('editor'))->post('/admin/media/delete', [])->assertForbidden();
    }
}
