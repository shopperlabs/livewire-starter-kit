<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Shopper\Sandbox\Seeders\SandboxSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SandboxSeeder::class);
    }
}
