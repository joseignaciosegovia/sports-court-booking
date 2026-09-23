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
        DB::table('feedback')->updateOrInsert(
            [
                // Criterio para identificar si esta fila "ya existe"
                'content' => 'Podríais añadir información de las pistas',
                'user_id' => 1,
            ],
            [
                // Campos que se insertan/actualizan si coincide (o no) el criterio anterior
                'type' => 'suggestion',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('feedback')->updateOrInsert(
            [
                'content' => 'La página va lenta',
                'user_id' => 1,
            ],
            [
                'type' => 'incident',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}