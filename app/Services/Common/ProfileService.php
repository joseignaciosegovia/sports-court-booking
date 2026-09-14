<?php

namespace App\Services\Common;

use App\Models\User;
use Illuminate\Http\UploadedFile;

class ProfileService
{
    public function update(User $user, array $data, ?UploadedFile $photo = null): User
    {
        $user->name = $data['name'];
        $user->phone = $data['phone'];
        $user->dni = $data['dni'];

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        if ($data['delete_photo'] ?? false) {
            $user->deleteProfilePhoto();
        } elseif ($photo) {
            $user->deleteProfilePhoto();
            $user->photo = $photo->store('profiles', 'public');
        }

        $user->save();

        return $user;
    }
}