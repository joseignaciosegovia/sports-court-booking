<?php

namespace Database\Seeders;

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
                'email' => 'adminMer@gmail.com',
                'password' => env('SEED_ADMIN_PASSWORD'),
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
                'password' => env('SEED_MANAGER_PASSWORD'),
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
            $email = $user['email'];
            $password = $user['password'];

            if (empty($password)) {
                throw new \RuntimeException(
                    "No se ha configurado la contraseña para {$email}."
                );
            }

            unset($user['email'], $user['password']);

            $existingUser = DB::table('users')
                ->where('email', $email)
                ->first();

            if ($existingUser) {
                DB::table('users')
                    ->where('email', $email)
                    ->update([
                        ...$user,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('users')->insert([
                ...$user,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}