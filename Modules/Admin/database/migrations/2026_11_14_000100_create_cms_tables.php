<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('editor');
            $table->boolean('active')->default(true);
        });
        Schema::create('menus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->restrictOnDelete();
            $table->string('name');
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        foreach (['products', 'posts'] as $resource) {
            Schema::create($resource, function (Blueprint $table) use ($resource): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                if ($resource === 'products') {
                    $table->string('sku', 80)->nullable()->unique();
                    $table->unsignedBigInteger('price')->default(0);
                }
                $table->string('image', 500)->nullable();
                $table->longText('body')->nullable();
                $table->boolean('active')->default(false)->index();
                $table->timestamps();
            });
        }
        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('placement', 20)->index();
            $table->string('image', 500);
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->timestamps();
        });
        Schema::create('subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->string('role')->primary();
            $table->json('permissions');
        });
    }

    public function down(): void
    {
        foreach (['role_permissions', 'site_settings', 'subscribers', 'contacts', 'banners', 'posts', 'products', 'menus'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'active']));
    }
};
