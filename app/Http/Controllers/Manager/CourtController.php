<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Court;
use App\Http\Requests\Manager\StoreCourtRequest;
use App\Http\Requests\Manager\UpdateCourtRequest;
use App\Helpers\SortHelper;
use App\QueryFilters\CourtFilter;

class CourtController extends Controller
{
    public function index(Request $request, CourtFilter $filters)
    {
        $sortColumns = [
            'name' => 'courts.name',
            'location' => 'courts.location',
        ];

        $locations = Court::all()->groupBy('location')->keys();

        $courts = Court::query()
            ->filter($filters)
            ->sort($sortColumns, 'courts.name', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('manager.courts.index', [
            'locations' => $locations,
            'courts' => $courts,
            'filters' => [
                'location' => $request->input('location', ''),
            ],
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }

    public function create()
    {
        $locations = Court::select('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');
        
        return view('manager.courts.create', [
            'locations' => $locations,
        ]);
    }

    public function store(StoreCourtRequest $request)
    {
        Court::create($request->validated());

        return redirect()
            ->route('manager.courts.index')
            ->with('success', 'Pista creada correctamente.');
    }

    public function edit(Court $court)
    {

        $locations = Court::select('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');
        
        return view('manager.courts.edit', [
            'court' => $court,
            'locations' => $locations,
        ]);
    }

    public function update(UpdateCourtRequest $request, Court $court)
    {
        $court->update($request->validated());

        return redirect()
            ->route('manager.courts.index')
            ->with('success', 'Pista actualizada correctamente.');
    }

    public function destroy(Court $court)
    {
        if ($court->reservations()->exists()) {
            return back()->withErrors(['court' => 'No se puede eliminar una pista con reservas asociadas.']);
        }

        $court->delete();

        return redirect()
            ->route('manager.courts.index')
            ->with('success', 'Pista eliminada correctamente.');
    }
}