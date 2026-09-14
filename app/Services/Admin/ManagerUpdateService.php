<?php

namespace App\Services\Admin;

use App\Models\User;

class ManagerUpdateService
{

    public function update(User $manager, array $data): User
    {
        $manager->name = $data['name'];
        $manager->email = $data['email'];
        $manager->dni = $data['dni'];
        $manager->phone = $data['phone'] ?? null;
        $manager->role = $data['role'];

        if (!empty($data['password'])) {
            $manager->password = $data['password'];
        }

        if (isset($data['photo'])) {
            $manager->deleteProfilePhoto();
            $manager->photo = $data['photo']->store('profiles', 'public');
        }

        $manager->save();

        return $manager;
    }
}