<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table): void {
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('menus', fn (Blueprint $table) => $table->dropColumn(['seo_title', 'seo_description']));
    }
};
