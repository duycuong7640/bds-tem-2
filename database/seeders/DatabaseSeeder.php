<?php

namespace Database\Seeders;

use App\Services\WebfxSampleSnapshot;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('local') && ! config('webfx.runtime')) {
            $this->call(LocalAdminSeeder::class);
        }
        if (config('webfx.sample') === 'furniture' && is_file(database_path(WebfxSampleSnapshot::SNAPSHOT))) {
            app(WebfxSampleSnapshot::class)->sync();

            return;
        }

        $this->call(SampleMenuSeeder::class);
        if (config('webfx.sample') !== 'empty') {
            $this->call(RealEstateSampleSeeder::class);
        }
    }
}
