<?php

namespace App\Services\Common;

use App\Models\Reservation;

class CourtScheduleService
{

    /**
     * Devuelve los horarios ocupados de una pista
     */
    public function getOccupiedSlots(int $courtId, ?string $start = null, ?string $end = null)
{
        return Reservation::where('court_id', $courtId)
            ->blocking() // Función del modelo Reservation que devuelve los horarios ocupados
            ->when($start, fn($q) => $q->where('end_time', '>=', $start)) // Si la función recibe rango de fechas, filtra; si no, devuelve todas las reservas ocupada sin restricción temporal
            ->when($end, fn($q) => $q->where('start_time', '<=', $end)) // Juntas, son la forma correcta de comprobar solapamientos
            ->get() // Ejecuta la query y trae los modelos Reservation que cumplen todo lo anterior
            ->map(fn($reservation) => [ // Recorre la colección y transforma cada modelo en un array simple con 4 claves
                'id' => $reservation->id, // Para que eventClick/eventDrop sepan qué reserva es cada evento
                'title' => 'Ocupado',
                'start' => $reservation->start_time->format('Y-m-d\TH:i:s'), // $reservation es cada elemento de la colección de Reservations que cumplen las condiciones anteriores
                'end'   => $reservation->end_time->format('Y-m-d\TH:i:s'), // Al no incluir Z ni un offset (+02:00), FullCalendar interpreta la fecha como hora local del navegador
                'color' => '#dc3545',
                'editable' => !in_array($reservation->payment_status, ['canceled', 'refunded']), // No se pueden editar reservas canceladas o reembolsadas
            ]);
    }
}