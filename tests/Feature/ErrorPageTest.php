<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_missing_pages_use_the_correct_layout_and_keep_json_responses(): void
    {
        $this->get('/khong-co-trang-nay')->assertNotFound()
            ->assertSee('error-page-client')->assertSee('Về trang chủ');
        $this->get('/admin/khong-co-trang-nay')->assertNotFound()
            ->assertSee('error-page-admin')->assertSee('Về quản trị');
        $this->getJson('/admin/khong-co-trang-nay')->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')->assertJsonStructure(['message']);
    }

    public function test_error_pages_preserve_status_and_headers_without_rendering_exception_details(): void
    {
        config(['app.debug' => false]);
        foreach (['', 'admin/'] as $prefix) {
            Route::get('/'.$prefix.'error-test/server', fn () => throw new RuntimeException('private-error-details'));
            Route::get('/'.$prefix.'error-test/rate', fn () => abort(429, '', ['Retry-After' => '17']));
            Route::get('/'.$prefix.'error-test/expired', fn () => abort(419));

            $this->get('/'.$prefix.'error-test/server')->assertStatus(500)
                ->assertSee($prefix ? 'error-page-admin' : 'error-page-client')
                ->assertSee('Hệ thống gặp sự cố')->assertDontSee('private-error-details');
            $this->get('/'.$prefix.'error-test/rate')->assertTooManyRequests()
                ->assertHeader('Retry-After', '17')->assertSee('Bạn đã gửi quá nhiều yêu cầu');
        }
        Route::get('/admin/error-test/forbidden', fn () => abort(403));
        Route::get('/error-test/unavailable', fn () => abort(503));
        $this->get('/admin/error-test/forbidden')->assertForbidden()->assertSee('Bạn không có quyền truy cập');
        $this->get('/error-test/unavailable')->assertStatus(503)->assertSee('Hệ thống tạm thời gián đoạn');
        $this->get('/admin/error-test/expired')->assertStatus(419)->assertSee('Đăng nhập quản trị');
        $this->post('/admin/error-test/expired')->assertMethodNotAllowed()->assertSee('Thao tác không được hỗ trợ');
    }
}
