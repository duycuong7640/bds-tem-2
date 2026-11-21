<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webfx_installations', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('mode')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webfx_installations');
    }
};
