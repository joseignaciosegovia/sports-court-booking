<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Court;
use App\Models\User;
use App\Services\Common\CourtScheduleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Client\StoreUserRequest;

class HomeController extends Controller
{
    public function index()
    {
        $courtsByFacility = Court::all()->groupBy('location');
        $facilities = $courtsByFacility ->keys();
        $numberOfCourts = Court::count();
        $numberOfFacilities = count(DB::table("courts")->distinct()->pluck('location'));

        return view('public.index')
            ->with('courtsByFacility', $courtsByFacility )
            ->with('numberOfCourts', $numberOfCourts)
            ->with('facilities', $facilities)
            ->with('numberOfFacilities', $numberOfFacilities)
            ->with('openingTime', config('schedules.opening_time'))
            ->with('closingTime', config('schedules.closing_time'));
    }

    public function register(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = new User();

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->dni = $data['dni'];
        $user->password = Hash::make($data['password']);

        if (!empty($data['phone'])) {
            $user->phone = $data['phone'];
        }

        if ($request->hasFile('photo')) {
            $user->photo = $request->file('photo')->store('profiles', 'public');
        }

        $user->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('client.dashboard');
    }

    // Devuelve los horarios ocupados de la pista cuyo ID ha recibido
    public function schedule(Request $request, int $courtId)
    {
        $data = app(CourtScheduleService::class)
            ->getOccupiedSlots($courtId, $request->query('start'), $request->query('end'));

        return response()->json($data);
    }
}