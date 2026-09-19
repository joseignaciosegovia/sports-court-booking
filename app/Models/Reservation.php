<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\Sortable;
use App\Enums\PaymentStatus;
use App\Enums\CanceledBy;

class Reservation extends Model
{
    use HasFactory, Filterable, Sortable;
    protected $fillable = [
        'start_time',
        'end_time',
        'court_id',
        'user_id',
        'information',
        'payment_id',
        'payment_status',
        'expires_at',
        'stripe_session_id',
        'canceled_at',
        'canceled_by',
        'cancellation_reason',
        'refunded_at',
        'stripe_refund_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'expires_at' => 'datetime',
        'canceled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'payment_status' => PaymentStatus::class,
        'canceled_by' => CanceledBy::class,
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Joins necesarios según la columna por la que se ordene.
     */
    public static function sortJoins(): array
    {
        return [
            'court' => function ($query) {
                $query->join('courts', 'reservations.court_id', '=', 'courts.id')
                      ->select('reservations.*');
            },
            'price' => function ($query) {
                $query->join('courts', 'reservations.court_id', '=', 'courts.id')
                    ->select('reservations.*');
            },
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // --- Comprobación de solapamientos ---

    public static function hasOverlap(
        int $court_id,
        string $start_time,
        string $end_time,
        ?int $ignore_reservation_id = null
    ): bool {
        return static::query()
            ->blocking() // Función del modelo que devuelve los horarios ocupados
            ->where('court_id', $court_id)
            ->when(
                $ignore_reservation_id,
                fn ($q) => $q->where('id', '!=', $ignore_reservation_id)
            )
            ->where('start_time', '<', $end_time)
            ->where('end_time', '>', $start_time)
            ->exists();
    }

    /**
     * Reservas que "bloquean" la franja horaria:
     * pagadas, o pendientes dentro de su plazo de expiración.
     */
    public function scopeBlocking(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('payment_status', PaymentStatus::Paid)
                ->orWhere(function ($q2) { // La función es necesaria para agrupar las dos condiciones dentro del OR
                    $q2->where('payment_status', PaymentStatus::Pending)
                       ->where('expires_at', '>', now()); // Solo tenemos en cuenta reservas pagadas o no expiradas
                });
        });
    }
}