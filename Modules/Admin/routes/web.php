<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\AdminSecurityHeaders;
use Modules\Admin\Http\BlockDemoAdmin;
use Modules\Admin\Http\ConfigurationController;
use Modules\Admin\Http\EnsureStaff;
use Modules\Admin\Http\LoginController;
use Modules\Admin\Http\MarketplaceController;
use Modules\Admin\Http\MediaController;
use Modules\Admin\Http\ResourceController;
use Modules\Admin\Http\ValidateMediaDirectory;
use Modules\Admin\Services\DashboardService;
use UniSharp\LaravelFilemanager\Controllers\FolderController;
use UniSharp\LaravelFilemanager\Controllers\LfmController;
use UniSharp\LaravelFilemanager\Middlewares\CreateDefaultFolder;

Route::prefix('admin')->name('admin.')->middleware([BlockDemoAdmin::class, AdminSecurityHeaders::class])->group(function (): void {
    Route::get('/login', fn () => auth()->check() ? to_route('admin.home') : view('admin::login'))->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:8,1')->name('login.store');

    Route::middleware(['auth', 'auth.session', EnsureStaff::class])->group(function (): void {
        Route::controller(MarketplaceController::class)->group(function (): void {
            Route::get('/members', 'membersIndex')->middleware('permission:members.view')->name('members');
            Route::get('/members/{id}', 'memberEdit')->whereNumber('id')->middleware('permission:members.view')->name('members.edit');
            Route::put('/members/{id}', 'memberUpdate')->whereNumber('id')->middleware('permission:members.edit')->name('members.update');
            Route::post('/members/{id}/balance', 'memberAdjust')->whereNumber('id')->middleware('permission:members.edit')->name('members.adjust');
            Route::get('/topups', 'topups')->middleware('permission:topups.view')->name('topups');
            Route::post('/topups/{id}/approve', 'approveTopup')->whereNumber('id')->middleware('permission:topups.edit')->name('topups.approve');
            Route::post('/topups/{id}/reject', 'rejectTopup')->whereNumber('id')->middleware('permission:topups.edit')->name('topups.reject');
            Route::get('/transactions', 'transactions')->middleware('permission:topups.view')->name('transactions');
            Route::post('/listings/{id}/approve', 'approveListing')->whereNumber('id')->middleware('permission:products.edit')->name('listings.approve');
        });

        Route::get('/editor/{resource}.js', fn () => response()->file(
            resource_path('editor/build/editor.js'), ['Content-Type' => 'application/javascript'],
        ))->whereIn('resource', ['posts', 'products'])->middleware('permission:{resource}.edit')->name('editor');

        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', function () {
            if (admin_can('dashboard.view')) {
                return to_route('admin.dashboard');
            }
            foreach (config('cms.resources') as $resource => $metadata) {
                if (admin_can($resource.'.view')) {
                    return to_route('admin.index', $resource);
                }
            }
            if (admin_can('settings.view')) {
                return to_route('admin.settings');
            }
            if (admin_can('media.view')) {
                return to_route('admin.media', ['type' => 'image']);
            }

            return view('admin::no-access');
        })->name('home');
        Route::get('/dashboard', fn (DashboardService $service) => view('admin::dashboard', $service->summary()))
            ->middleware('permission:dashboard.view')->name('dashboard');
        Route::get('/settings', [ConfigurationController::class, 'settings'])->middleware('permission:settings.view')->name('settings');
        Route::put('/settings', [ConfigurationController::class, 'saveSettings'])->middleware('permission:settings.edit')->name('settings.save');
        Route::get('/permissions', [ConfigurationController::class, 'permissions'])->middleware('permission:roles.manage')->name('permissions');
        Route::put('/permissions', [ConfigurationController::class, 'savePermissions'])->middleware('permission:roles.manage')->name('permissions.save');

        foreach (['index' => 'view', 'form' => 'view', 'create' => 'edit', 'store' => 'edit', 'update' => 'edit', 'delete' => 'delete'] as $action => $operation) {
            $middleware = 'permission:{resource}.'.$operation;
            $route = match ($action) {
                'index' => Route::get('/content/{resource}', [ResourceController::class, 'index']),
                'form' => Route::get('/content/{resource}/{id}/edit', [ResourceController::class, 'form']),
                'create' => Route::get('/content/{resource}/create', [ResourceController::class, 'form']),
                'store' => Route::post('/content/{resource}', [ResourceController::class, 'save']),
                'update' => Route::put('/content/{resource}/{id}', [ResourceController::class, 'save']),
                'delete' => Route::delete('/content/{resource}/{id}', [ResourceController::class, 'delete']),
            };
            $route->middleware($middleware)->whereIn('resource', array_keys(config('cms.resources')))
                ->whereNumber('id')->name($action);
        }

        Route::prefix('media')->middleware([ValidateMediaDirectory::class, CreateDefaultFolder::class])->group(function (): void {
            Route::middleware('permission:media.view')->group(function (): void {
                Route::get('/', [MediaController::class, 'index'])->name('media');
                Route::get('/folders', [FolderController::class, 'getFolders']);
                Route::get('/jsonitems', [MediaController::class, 'items']);
                Route::get('/errors', [LfmController::class, 'getErrors']);
            });
            Route::post('/upload', [MediaController::class, 'upload'])->middleware(['permission:media.edit', 'throttle:30,1'])->name('media.upload');
            Route::post('/newfolder', [MediaController::class, 'folder'])->middleware('permission:media.edit')->name('media.folder');
            Route::post('/delete-folder', [MediaController::class, 'deleteFolder'])->middleware('permission:media.delete')->name('media.delete-folder');
            Route::post('/delete', [MediaController::class, 'delete'])->middleware('permission:media.delete')->name('media.delete');
        });
    });
});
