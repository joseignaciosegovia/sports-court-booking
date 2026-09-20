<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
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
        
        // Número de sugerencias e incidencias enviadas por el cliente
        $suggestionsCount = Feedback::where('type', 'suggestion')
            ->where('user_id', $user->id)
            ->count();
        $incidentsCount = Feedback::where('type', 'incident')
            ->where('user_id', $user->id)
            ->count();
        
        // Pistas disponibles
        $courtsCount = Court::count();
        $locationsCount = count(DB::table("courts")->distinct()->pluck('location'));

        return view('client.dashboard', [
            'nextReservation' => $nextReservation,
            'nextReservationCourt' => $nextReservationCourt,
            'reservationsThisMonth' => $reservationsThisMonth,
            'suggestionsCount' => $suggestionsCount,
            'incidentsCount' => $incidentsCount,
            'courtsCount' => $courtsCount,
            'locationsCount' => $locationsCount,
            'openingTime' => config('schedules.opening_time'),
            'closingTime' => config('schedules.closing_time'),
        ]);
    }
}
