<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SampleMenuSeeder::class);
        if (app()->environment('local') && ! config('webfx.runtime')) {
            $this->call(LocalAdminSeeder::class);
        }
        if (config('webfx.sample') !== 'empty') {
            $this->call(RealEstateSampleSeeder::class);
        }
    }
}
