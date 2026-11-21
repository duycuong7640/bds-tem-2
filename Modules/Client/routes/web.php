<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Client\Http\AccountController;
use Modules\Client\Http\AuthController;
use Modules\Client\Http\EnsureMember;
use Modules\Client\Services\WebsiteService;

Route::get('/', fn (WebsiteService $service) => view('client::home', $service->home()))->name('home');

// Tìm kiếm & danh sách tin
Route::get('/nha-dat-ban', fn (Request $request, WebsiteService $service) => view('client::listings', $service->search($request, 'sale')))->name('client.sale');
Route::get('/nha-dat-cho-thue', fn (Request $request, WebsiteService $service) => view('client::listings', $service->search($request, 'rent')))->name('client.rent');
Route::get('/tim-kiem', fn (Request $request, WebsiteService $service) => view('client::listings', $service->search($request)))->name('client.search');
Route::get('/bat-dong-san/{slug}', fn (WebsiteService $service, string $slug) => view('client::listing', $service->listing($slug)))->name('client.product');

// Tin tức
Route::get('/tin-tuc', fn (Request $request, WebsiteService $service) => view('client::posts', $service->posts($request)))->name('client.posts');
Route::get('/bai-viet/{slug}', fn (WebsiteService $service, string $slug) => view('client::post', $service->post($slug)))->name('client.post');

// Trang thông tin
Route::get('/bao-gia-tin-vip', fn (WebsiteService $service) => view('client::pricing', ['packages' => $service->packages()]))->name('client.pricing');
Route::get('/lien-he', fn () => view('client::contact'))->name('client.contact.page');
Route::post('/lien-he', function (Request $request, WebsiteService $service) {
    $service->contact($request);

    return to_route('client.contact.page')->with('status', 'Đã gửi liên hệ. Chúng tôi sẽ phản hồi sớm nhất!');
})->middleware('throttle:6,1')->name('client.contact');
Route::post('/dang-ky-email', function (Request $request, WebsiteService $service) {
    $service->subscribe($request);

    return back()->with('status', 'Đã đăng ký nhận tin bất động sản mới.');
})->middleware('throttle:6,1')->name('client.subscribe');

// Tra cứu trạng thái tin đăng (đặt ở thanh tiện ích & chân trang, không nằm trong menu chính)
Route::get('/tra-cuu-tin-dang', fn () => view('client::lookup', ['result' => null]))->name('client.lookup');
Route::post('/tra-cuu-tin-dang', fn (Request $request, WebsiteService $service) => view('client::lookup', [
    'result' => $service->lookup($request),
]))->middleware('throttle:10,1')->name('client.lookup.search');

// Đăng nhập / đăng ký thành viên
Route::middleware('guest')->group(function (): void {
    Route::view('/dang-nhap', 'client::auth.login')->name('client.login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:8,1')->name('client.login.store');
    Route::view('/dang-ky', 'client::auth.register')->name('client.register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('client.register.store');
});
Route::post('/dang-xuat', [AuthController::class, 'logout'])->middleware('auth')->name('client.logout');

// Khu vực thành viên
Route::prefix('tai-khoan')->middleware(['auth', EnsureMember::class])->controller(AccountController::class)->group(function (): void {
    Route::get('/', 'dashboard')->name('client.account');
    Route::get('/dang-tin', 'create')->name('client.listings.create');
    Route::post('/dang-tin', 'store')->middleware('throttle:10,1')->name('client.listings.store');
    Route::get('/tin/{id}/sua', 'edit')->whereNumber('id')->name('client.listings.edit');
    Route::put('/tin/{id}', 'update')->whereNumber('id')->middleware('throttle:20,1')->name('client.listings.update');
    Route::delete('/tin/{id}', 'destroy')->whereNumber('id')->name('client.listings.destroy');
    Route::get('/vi', 'wallet')->name('client.wallet');
    Route::post('/nap-tien', 'topup')->middleware('throttle:6,1')->name('client.topup');
    Route::get('/thong-tin', 'profile')->name('client.profile');
    Route::put('/thong-tin', 'updateProfile')->name('client.profile.update');
    Route::put('/mat-khau', 'updatePassword')->middleware('throttle:6,1')->name('client.password.update');
});
