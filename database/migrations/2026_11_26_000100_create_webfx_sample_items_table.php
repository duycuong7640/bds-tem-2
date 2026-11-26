<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webfx_sample_items', function (Blueprint $table): void {
            $table->string('resource', 32);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('target_id');
            $table->primary(['resource', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webfx_sample_items');
    }
};
