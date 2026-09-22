<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Court;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\Common\CourtScheduleService;
use App\Exceptions\SlotUnavailableException; 
use App\Exceptions\ReservationNotResumableException;
use App\Http\Requests\Client\StoreClientReservationRequest;
use App\Services\Client\ReservationCheckoutService;
use App\Services\Common\ReservationCancellationService;
use App\Helpers\SortHelper;
use App\QueryFilters\ReservationFilter;

class ReservationController extends Controller
{
    
    public function index(Request $request, ReservationFilter $filters)
    {
        $courts = Court::orderBy('name')->get();

        $sortColumns = [
            'court' => 'courts.name',
            'location' => 'courts.location',
            'start_time' => 'reservations.start_time',
            'price' => 'courts.reservation_price',
            'status' => 'reservations.payment_status',
        ];

        $locations = Court::all()->groupBy('location')->keys();

        $reservations = Reservation::with('court')
            ->where('reservations.user_id', auth()->id())
            ->filter($filters)
            ->sort($sortColumns, 'reservations.start_time', 'desc', Reservation::sortJoins())
            ->paginate(10)
            ->withQueryString();

        return view('client.reservations.index', [
            'courts' => $courts,
            'locations' => $locations,
            'filters' => [
                'court_id' => $request->input('court_id', ''),
                'location' => $request->input('location', ''),
                'status' => $request->input('status', ''),
                'date' => $request->input('date', ''),
                'date_range' => $request->input('date_range', ''),
            ],
            'reservations' => $reservations,
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }

    public function create()     // Formulario para elegir horario/pista
    {
        $courts = Court::all();

        $courtsByFacility = Court::all()->groupBy('location');
        $facilities = $courtsByFacility ->keys();
        $numberOfCourts = Court::count();
        $numberOfFacilities = count(DB::table("courts")->distinct()->pluck('location'));

        return view('client.reservations.create', [
            'courts' => $courts,
            'courtsByFacility' => $courtsByFacility,
            'facilities' => $facilities,
            'numberOfCourts' => $numberOfCourts,
            'numberOfFacilities' => $numberOfFacilities,
            'openingTime' => config('schedules.opening_time'),
            'closingTime' => config('schedules.closing_time'),
        ]);
    }

    public function schedule(Court $court, Request $request)
    {
        $data = app(CourtScheduleService::class)
            ->getOccupiedSlots($court->id, $request->query('start'), $request->query('end'));

        return response()->json($data);
    }

    public function store(StoreClientReservationRequest $request, ReservationCheckoutService $service) // Almacenamos la reserva
    {
        $data = $request->validated();
        
        $court = Court::findOrFail($data['court_id']);
        $startTime = Carbon::parse($data['start_time']);

        try {
            $checkoutUrl = $service->createReservationWithCheckout($court, Auth::id(), $startTime);
        } catch (SlotUnavailableException $e) {
            return back()->withErrors(['start_time' => 'Este horario ya no está disponible.']);
        }

        return redirect($checkoutUrl);
    }

    public function cancel(Reservation $reservation, ReservationCancellationService $service)
    {
        $this->authorize('cancel', $reservation);

        try {
            $result = $service->cancelByClient($reservation);
        } catch (\App\Exceptions\RefundException|\App\Exceptions\ReservationNotCancellableException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }

        $message = match ($result) {
            'refunded' => 'Reserva cancelada. Se le devolverá el dinero.',
            'canceled_late' => 'Reserva cancelada. Al ser con menos de 12 horas de antelación, no se devolverá el dinero.',
            'canceled_no_payment' => 'Reserva cancelada.',
        };

        return redirect()
            ->route('client.reservations.index')
            ->with('success', $message);
    }

    public function paymentSuccess(Reservation $reservation)
    {
        return view('client.reservations.payment-success', [
            'reservation' => $reservation,
        ]);
    }

    public function paymentCancel(Reservation $reservation)
    {
        return view('client.reservations.payment-cancel', compact('reservation'));
    }

    public function resumePayment(Reservation $reservation, ReservationCheckoutService $service)
    {
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $checkoutUrl = $service->resumeCheckout($reservation);
        } catch (ReservationNotResumableException $e) {
            return redirect()
                ->route('client.reservations.index')
                ->withErrors(['reservation' => $e->getMessage()]);
        }

        return redirect($checkoutUrl);
    }
}
