<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\Sortable;

class Court extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'name',
        'location',
        'reservation_price',
    ];

    protected function casts(): array
    {
        return [
            'reservation_price' => 'decimal:2',
        ];
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'court_id');
    }
}