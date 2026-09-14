<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('feedback')->insert([
            'content' => 'Podríais añadir información de las pistas',
            'type' => 'suggestion',
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('feedback')->insert([
            'content' => 'La página va lenta',
            'type' => 'incident',
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
