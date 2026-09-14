<?php

namespace App\Services\Admin;

use App\Models\User;

class ManagerCreateService
{
    public function create(array $data): User
    {
        $manager = new User();
        $manager->name = $data['name'];
        $manager->email = $data['email'];
        $manager->password = $data['password']; 
        $manager->dni = $data['dni'];
        $manager->phone = $data['phone'] ?? null;
        $manager->role = $data['role'];
        $manager->email_verified_at = now();

        if (isset($data['photo'])) {
            $manager->photo = $data['photo']->store('profiles', 'public');
        }

        $manager->save();

        return $manager;
    }
}