<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'marLop@gmail.com',
                'password' => Hash::make('marL1234'),
                'name' => 'María López',
                'dni' => '87182344I',
                'phone' => '655871025',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'ferSan@gmail.com',
                'password' => Hash::make('ferS1234'),
                'name' => 'Fernando Sanz',
                'dni' => '11298419O',
                'phone' => '664788795',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'antGar@gmail.com',
                'password' => Hash::make('antG1234'),
                'name' => 'Antonio García',
                'dni' => '74439120U',
                'phone' => '697874169',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'ferLui@gmail.com',
                'password' => Hash::make('ferL1234'),
                'name' => 'Fernanda Luisa',
                'dni' => '11298437H',
                'phone' => '604975301',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'adminMer@gmail.com',
                'password' => Hash::make('admMer12'),
                'name' => 'Mercedes Puertas',
                'dni' => '77319284T',
                'phone' => '661281938',
                'role' => 'admin',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'adminAnton@gmail.com',
                'password' => Hash::make('admAn123'),
                'name' => 'Antonio Castillo',
                'dni' => '71822198U',
                'phone' => '617291009',
                'role' => 'manager',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            // Separamos el email (clave para buscar) del resto de campos a insertar/actualizar.
            $email = $user['email'];
            unset($user['email']);

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                $user
            );
        }
    }
}