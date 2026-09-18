<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Court;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // Próxima reserva del cliente
        $nextReservation = $user->reservations()
            ->with('court') // eager loading para evitar N+1 queries
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->take(1)
            ->first();
        
        // Pista de la siguiente reserva
        $nextReservationCourt = $nextReservation?->court;

        // Reservas del mes actual
        $reservationsThisMonth = $user->reservations()
            ->whereYear('start_time', now()->year)
            ->whereMonth('start_time', now()->month)
            ->count();
        
        // Número de comentarios realizados por el cliente
        $feedbackCount  = $user->feedback()->count();
        
        // Pistas disponibles
        $courtsCount = Court::count();
        $locationsCount = count(DB::table("courts")->distinct()->pluck('location'));

        return view('client.dashboard', [
            'nextReservation' => $nextReservation,
            'nextReservationCourt' => $nextReservationCourt,
            'reservationsThisMonth' => $reservationsThisMonth,
            'feedbackCount' => $feedbackCount ,
            'courtsCount' => $courtsCount,
            'locationsCount' => $locationsCount,
            'openingTime' => config('schedules.opening_time'),
            'closingTime' => config('schedules.closing_time'),
        ]);
    }
}
