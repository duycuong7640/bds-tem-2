<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sàn rao vặt bất động sản: thành viên có ví, gói tin (thường/VIP) tính phí theo ngày,
 * yêu cầu nạp tiền chờ admin duyệt và sổ giao dịch ví.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->index();
            $table->unsignedBigInteger('balance')->default(0);
        });

        Schema::create('listing_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('badge', 20)->nullable();
            $table->string('color', 7)->default('#e03c31');
            $table->unsignedInteger('price_per_day')->default(0);
            $table->unsignedSmallInteger('priority')->default(0)->index();
            $table->unsignedSmallInteger('min_days')->default(1);
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('listing_type', 10)->default('sale')->index();
            $table->string('status', 20)->default('approved')->index();
            $table->string('reject_reason', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('listing_packages')->nullOnDelete();
            $table->unsignedSmallInteger('priority')->default(0)->index();
            $table->unsignedSmallInteger('days')->nullable();
            $table->unsignedBigInteger('fee')->default(0);
            $table->boolean('fee_settled')->default(true);
            $table->dateTime('expires_at')->nullable()->index();
            $table->string('province', 60)->nullable()->index();
            $table->string('district', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->decimal('area', 10, 2)->default(0);
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('toilets')->nullable();
            $table->unsignedTinyInteger('floors')->nullable();
            $table->string('direction', 20)->nullable();
            $table->string('legal', 80)->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 20)->nullable()->index();
            $table->string('contact_email', 254)->nullable();
            $table->json('gallery')->nullable();
            $table->unsignedInteger('views')->default(0);
        });

        Schema::create('topup_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 20)->default('bank');
            $table->string('transfer_note', 255)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('admin_note', 255)->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->index();
            $table->bigInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->string('description', 255);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('topup_id')->nullable()->constrained('topup_requests')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('topup_requests');
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn([
                'listing_type', 'status', 'reject_reason', 'priority', 'days', 'fee', 'fee_settled', 'expires_at',
                'province', 'district', 'address', 'area', 'bedrooms', 'toilets', 'floors', 'direction', 'legal',
                'contact_name', 'contact_phone', 'contact_email', 'gallery', 'views',
            ]);
        });
        Schema::dropIfExists('listing_packages');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['phone', 'balance']));
    }
};
