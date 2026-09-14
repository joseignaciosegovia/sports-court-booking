<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Common\UpdateProfileRequest;
use App\Services\Common\ProfileService;

class ProfileController extends Controller
{
    public function edit()
    {
        return view($this->viewFolder() . '.profile', [
            'updateRoute' => route($this->routePrefix() . '.profile.update'),
        ]);
    }

    public function update(UpdateProfileRequest $request, ProfileService $profileService)
    {
        /** @var User $user */
        $user = Auth::user();

        $profileService->update($user, $request->validated(), $request->file('photo'));

        return redirect()
            ->route($this->routePrefix() . '.profile.edit')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    private function routePrefix(): string
    {
        // Extrae "client", "manager" o "admin" del nombre de la ruta actual
        return explode('.', request()->route()->getName())[0];
    }

    private function viewFolder(): string
    {
        // admin y manager comparten la misma carpeta de vistas (manager/)
        return $this->routePrefix() === 'admin' ? 'manager' : $this->routePrefix();
    }
}
