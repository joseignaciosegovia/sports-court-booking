<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\Feedback;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $todayReservationsCount = Reservation::whereDate('start_time', now()->toDateString())
            ->blocking() // Función del modelo Reservation que devuelve los horarios ocupados
            ->count();
        
        $facilitiesCount = Court::distinct('location')->count('location');

        $courtsCount = Court::count();

        $canceledReservationsCount  = Reservation::with('court', 'user')
            ->whereIn('payment_status', ['canceled', 'refunded'])
            ->count();
        
        $feedbackCount = Feedback::count();
        $feedbackThisMonth = Feedback::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $data = [
            'todayReservationsCount' => $todayReservationsCount,
            'courtsCount' => $courtsCount,
            'canceledReservationsCount' => $canceledReservationsCount ,
            'facilitiesCount' => $facilitiesCount,
            'openingTime' => config('schedules.opening_time'),
            'closingTime' => config('schedules.closing_time'),
            'feedbackCount' => $feedbackCount,
            'feedbackThisMonth' => $feedbackThisMonth,
        ];

        // Solo el admin necesita las estadísticas de usuarios por rol
        if ($user->role === 'admin') {
            $data['clientsCount'] = User::where('role', 'client')->count();
            $data['managersCount'] = User::where('role', 'manager')->count();
            $data['adminsCount'] = User::where('role', 'admin')->count();
        }

        return view('manager.dashboard', $data);
    }
}