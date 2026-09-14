<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('courts')->insert([
            'name' => 'Campo fútbol 7',
            'location' => 'Ciudad Deportiva',
            'reservation_price' => 8.25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('courts')->insert([
            'name' => 'Campo fútbol 11',
            'location' => 'Ciudad Deportiva',
            'reservation_price' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('courts')->insert([
            'name' => 'Pádel',
            'location' => 'Ciudad Deportiva',
            'reservation_price' => 7.30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('courts')->insert([
            'name' => 'Atletismo',
            'location' => 'Ciudad Deportiva',
            'reservation_price' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('courts')->insert([
            'name' => 'Multiusos',
            'location' => 'Ciudad Deportiva',
            'reservation_price' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('courts')->insert([
            'name' => 'Pista interna',
            'location' => 'Polideportivo',
            'reservation_price' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('courts')->insert([
            'name' => 'Pista externa',
            'location' => 'Polideportivo',
            'reservation_price' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
