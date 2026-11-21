<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', fn (Blueprint $table) => $table->string('content_type', 20)->nullable()->index());
        foreach (['posts', 'products'] as $resource) {
            Schema::table($resource, fn (Blueprint $table) => $table->foreignId('menu_id')->nullable()->constrained('menus')->restrictOnDelete());
        }

        $samples = [
            'products' => ['Sản phẩm' => '/#san-pham', 'Tất cả sản phẩm' => '/#san-pham', 'Sản phẩm mới' => '/#san-pham-moi'],
            'posts' => ['Tin tức' => '/#tin-tuc', 'Blog' => '/#blog', 'Hoạt động' => '/#hoat-dong'],
        ];
        foreach ($samples as $type => $menus) {
            foreach ($menus as $name => $url) {
                DB::table('menus')->where('name', $name)->where('url', $url)->update(['content_type' => $type]);
            }
        }
        foreach (['blog' => '/#blog', 'activities' => '/#hoat-dong', 'home' => '/#tin-tuc'] as $kind => $url) {
            $menu = DB::table('menus')->where('content_type', 'posts')->where('url', $url)->orderBy('id')->value('id');
            if ($menu !== null) {
                DB::table('posts')->where('kind', $kind)->update(['menu_id' => $menu]);
            }
        }
    }

    public function down(): void
    {
        foreach (['posts', 'products'] as $resource) {
            Schema::table($resource, fn (Blueprint $table) => $table->dropConstrainedForeignId('menu_id'));
        }
        Schema::table('menus', function (Blueprint $table): void {
            $table->dropIndex(['content_type']);
            $table->dropColumn('content_type');
        });
    }
};
