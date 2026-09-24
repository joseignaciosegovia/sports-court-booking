<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courts = [
            ['name' => 'Campo fútbol 7', 'location' => 'Ciudad Deportiva', 'reservation_price' => 8.25],
            ['name' => 'Campo fútbol 11', 'location' => 'Ciudad Deportiva', 'reservation_price' => 9],
            ['name' => 'Pádel', 'location' => 'Ciudad Deportiva', 'reservation_price' => 7.30],
            ['name' => 'Atletismo', 'location' => 'Ciudad Deportiva', 'reservation_price' => 8],
            ['name' => 'Multiusos', 'location' => 'Ciudad Deportiva', 'reservation_price' => 5],
            ['name' => 'Pista interna', 'location' => 'Polideportivo', 'reservation_price' => 4],
            ['name' => 'Pista externa', 'location' => 'Polideportivo', 'reservation_price' => 4],
        ];

        foreach ($courts as $court) {
            DB::table('courts')->updateOrInsert(
                [
                    // Criterio para identificar si esta pista ya existe
                    'name' => $court['name'],
                    'location' => $court['location'],
                ],
                [
                    // Se inserta/actualiza el precio y la fecha de modificación
                    'reservation_price' => $court['reservation_price'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}