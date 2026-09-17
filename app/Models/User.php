<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\Sortable;
use App\Enums\UserRole;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'email',
        'password',
        'name',
        'dni',
        'phone',
        'photo',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // --- Relaciones ---

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }

    // --- Helpers de rol ---

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    public function isManagerOrAdmin(): bool
    {
        return in_array($this->role, [UserRole::Manager, UserRole::Admin]);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function deleteProfilePhoto(): void
    {
        if ($this->photo && Storage::disk('public')->exists($this->photo)) {
            Storage::disk('public')->delete($this->photo);
        }

        $this->photo = null;
        $this->save();
    }

    public function getPhotoUrlAttribute(): string
    {
        // Si photo es null, devolvemos la ruta a la foto por defecto
        return $this->photo
            ? asset('storage/' . $this->photo)
            : asset('storage/profiles/default.png');
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}