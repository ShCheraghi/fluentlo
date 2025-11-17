<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['code' => 'A1', 'name_fa' => '🌱 مبتدی (A1)', 'name_en' => 'Beginner (A1)', 'order' => 1, 'icon' => '🌱'],
            ['code' => 'A2', 'name_fa' => '🌿 ابتدایی (A2)', 'name_en' => 'Elementary (A2)', 'order' => 2, 'icon' => '🌿'],
            ['code' => 'B1', 'name_fa' => '🌳 متوسط (B1)', 'name_en' => 'Intermediate (B1)', 'order' => 3, 'icon' => '🌳'],
            ['code' => 'B2', 'name_fa' => '🌲 بالا (B2)', 'name_en' => 'Upper-Intermediate (B2)', 'order' => 4, 'icon' => '🌲'],
            ['code' => 'C1', 'name_fa' => '🎯 پیشرفته (C1)', 'name_en' => 'Advanced (C1)', 'order' => 5, 'icon' => '🎯'],
            ['code' => 'C2', 'name_fa' => '👑 مستر (C2)', 'name_en' => 'Mastery (C2)', 'order' => 6, 'icon' => '👑'],
        ];

        foreach ($levels as $level) {
            DB::table('levels')->insert(array_merge($level, [
                'description_fa' => 'سطح ' . $level['name_fa'],
                'description_en' => 'Level ' . $level['name_en'],
                'created_at' => now()
            ]));
        }
    }
}
