<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Reservation;
use App\Services\Common\CourtScheduleService;
use Illuminate\Http\Request;
use App\Http\Requests\Manager\QuickStoreReservationRequest;
use App\Http\Requests\Manager\UpdateReservationRequest;
use App\Http\Requests\Manager\RescheduleReservationRequest;
use App\Http\Requests\Manager\StoreManagerReservationRequest;
use App\Http\Requests\Manager\CancelReservationRequest;
use App\Services\Common\ReservationCancellationService;
use App\Services\Manager\QuickReservationService;
use App\Services\Manager\ReservationUpdateService;
use App\Services\Manager\ReservationRescheduleService;
use App\Services\Manager\ReservationCreateService;
use App\Helpers\SortHelper;
use App\QueryFilters\ReservationFilter;
use App\Exceptions\SlotUnavailableException;
use App\Exceptions\PastDateException;
use App\Exceptions\ReservationNotCancellableException;
use App\Exceptions\RefundException;

class ReservationController extends Controller
{

    public function index(Request $request, ReservationFilter $filters)
    {
        $courts = Court::orderBy('name')->get();

        $sortColumns = [
            'court' => 'courts.name',
            'date' => 'reservations.start_time',
            'user' => 'reservations.user_id',
            'information' => 'reservations.information',
            'payment_status' => 'reservations.payment_status',
        ];

        $reservations = Reservation::with('court')
            ->filter($filters)
            ->sort($sortColumns, 'reservations.start_time', 'desc', Reservation::sortJoins())
            ->paginate(10)
            ->withQueryString();

        return view('manager.reservations.index', [
            'reservations' => $reservations,
            'courts' => $courts,
            'filters' => [
                'court_id' => $request->input('court_id', ''),
                'status' => $request->input('status', ''),
                'date' => $request->input('date', ''),
                'date_range' => $request->input('date_range', ''),
            ],
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }

    // Imagen 3: vista calendario de una pista
    public function calendar(Court $court)
    {
        return view('manager.courts.calendar', [
            'court' => $court,
            'openingTime' => config('schedules.opening_time'),
            'closingTime' => config('schedules.closing_time'),
        ]);
    }

    // Endpoint JSON que alimenta el calendario (reutiliza el Service ya existente)
    public function events(Court $court, Request $request, CourtScheduleService $service)
    {
        $data = $service->getOccupiedSlots(
            $court->id,
            $request->query('start'),
            $request->query('end')
        );

        return response()->json($data);
    }

    public function edit(Reservation $reservation)
    {

        if (in_array($reservation->payment_status, ['canceled', 'refunded'])) {
            return redirect()
                ->route('manager.reservations.index')
                ->withErrors(['reservation' => 'No se puede editar una reserva cancelada o reembolsada.']);
        }

        if ($reservation->start_time->isPast()) {
            return redirect()
                ->route('manager.reservations.index')
                ->withErrors(['reservation' => 'No se puede editar una reserva que ya ha tenido lugar.']);
        }


        return view('manager.reservations.edit', [
            'reservation' => $reservation->load('court', 'user'),
            'openingTime' => config('schedules.opening_time'),
            'closingTime' => config('schedules.closing_time'),
        ]);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation, ReservationUpdateService $service)
    {
        $result = $service->update($reservation, $request->validated());

        if (!$result->success) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $result->message], 422);
            }

            if ($result->field === 'reservation') {
                return redirect()
                    ->route('manager.reservations.index')
                    ->withErrors(['reservation' => $result->message]);
            }

            return back()
                ->withErrors([$result->field => $result->message])
                ->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reserva actualizada correctamente.',
            ]);
        }

        // Respuesta normal
        return redirect()
            ->route('manager.reservations.index', $reservation->court_id)
            ->with('success', 'Reserva actualizada correctamente.');
    }

    public function create(Request $request)
    {
        $courts = Court::orderBy('name')->get();

        return view('manager.reservations.create', [
            'courts' => $courts,
            'selectedCourtId' => $request->query('court_id'),
        ]);
    }

    public function store(StoreManagerReservationRequest $request, ReservationCreateService $service)
    {
        $result = $service->create($request->validated());

        if (!$result->success) {
            return back()
                ->withErrors([$result->field => $result->message])
                ->withInput();
        }

        return redirect()
            ->route('manager.reservations.index', ['court_id' => $request->court_id])
            ->with('success', 'Reserva creada correctamente.');
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation, ReservationCancellationService $service)
    {
        $data = $request->validated();

        try {
            $hadPayment = $service->cancelByManager($reservation, $data['reason']);

            $message = $hadPayment
                ? 'Reserva cancelada y reembolsada correctamente.'
                : 'Reserva cancelada correctamente. No había ningún pago que reembolsar.';
        } catch (ReservationNotCancellableException|RefundException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }

        return redirect()
            ->route('manager.reservations.index')
            ->with('success', $message);
    }

    public function cancellations(Request $request, ReservationFilter $filters)
    {
        $courts = Court::orderBy('name')->get();

        $sortColumns = [
            'court' => 'courts.name',
            'reservation_date' => 'reservations.start_time',
            'user' => 'reservations.user_id',
            'information' => 'reservations.information',
            'canceled_by' => 'reservations.canceled_by',
            'cancelation_date' => 'reservations.canceled_at',
            'payment_status' => 'reservations.payment_status',
        ];

        $cancellations = Reservation::with('court')
            ->whereIn('reservations.payment_status', ['canceled', 'refunded'])
            ->filter($filters)
            ->sort($sortColumns, 'reservations.start_time', 'desc', Reservation::sortJoins())
            ->paginate(10)
            ->withQueryString();

            $a = [
                'court_id' => $request->input('court_id', ''),
                'status' => $request->input('status', ''),
                'date' => $request->input('date', ''),
                'canceled_by' => $request->input('canceled_by', ''),
            ];

        return view('manager.reservations.cancellations', [
            'courts' => $courts,
            'cancellations' => $cancellations,
            'filters' => [
                'court_id' => $request->input('court_id', ''),
                'status' => $request->input('status', ''),
                'date' => $request->input('date', ''),
                'canceled_by' => $request->input('canceled_by', ''),
            ],
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }

    // Devuelve la info de una reserva para mostrar en el modal
    public function show(Reservation $reservation)
    {
        $reservation->load('court', 'user');

        return response()->json([
            'id' => $reservation->id,
            'court_name' => $reservation->court->name,
            'date' => $reservation->start_time->format('Y-m-d'),
            'start_time' => $reservation->start_time->format('H:i'),
            'end_time' => $reservation->end_time->format('H:i'),
            'information' => $reservation->information,
            'client_email' => $reservation->user?->email,
            'payment_status' => $reservation->payment_status,
            'is_internal' => is_null($reservation->user_id),
        ]);
    }

    // Reprograma una reserva arrastrándola en el calendario
    public function reschedule(RescheduleReservationRequest $request, Reservation $reservation, ReservationRescheduleService $service)
    {
        $result = $service->reschedule($reservation, $request->validated());

        if (!$result->success) {
            return response()->json(['message' => $result->message], 422);
        }

        return response()->json(['message' => 'Reserva actualizada correctamente.']);
    }

    // Crea una reserva rápida al pinchar en un hueco vacío del calendario
    public function quickStore(QuickStoreReservationRequest $request, Court $court, QuickReservationService $service)
    {
        try {
            $reservation = $service->create($court, $request->validated());
        } catch (PastDateException $e) {
            return response()->json(['message' => 'No se puede crear una reserva en una fecha pasada.'], 422);
        } catch (SlotUnavailableException $e) {
            return response()->json(['message' => 'Ya existe otra reserva que se solapa con este horario.'], 422);
        }

        return response()->json(['message' => 'Reserva creada correctamente.', 'id' => $reservation->id]);
    }
}