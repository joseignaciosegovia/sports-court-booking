<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\SortHelper;
use App\Services\Admin\ManagerCreateService;
use App\Services\Admin\ManagerUpdateService;
use App\Http\Requests\Admin\StoreManagerRequest;
use App\Http\Requests\Admin\UpdateManagerRequest;
use App\QueryFilters\UserFilter;

class ManagerController extends Controller
{
    public function index(Request $request, UserFilter $filters)
    {
       /*
        * Columnas disponibles para ordenar.
        *
        * La clave es la que utilizará la URL.
        * El valor es la columna real de la BD.
        */
        $sortColumns = [
            'email' => 'users.email',
            'name' => 'users.name',
            'dni' => 'users.dni',
            'phone' => 'users.phone',
            'role' => 'users.role',
        ];

        $managers = User::query()
            ->whereIn('users.role', ['manager', 'admin'])
            ->filter($filters)
            ->sort($sortColumns, 'users.email')
            ->paginate(10)
            ->withQueryString();

        return view('admin.managers.index', [
            'managers' => $managers,
            'filters' => [
                'role' => $request->input('role', ''),
            ],
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }

    public function create()
    {
        return view('admin.managers.create');
    }

    public function store(StoreManagerRequest $request, ManagerCreateService $managerService)
    {
        $managerService->create($request->validated());

        return redirect()
            ->route('admin.managers.index')
            ->with('success', 'Gestor creado correctamente.');
    }

    public function edit(User $manager)
    {
        return view('admin.managers.edit', [
            'manager' => $manager,
        ]);
    }

    public function update(UpdateManagerRequest $request, User $manager, ManagerUpdateService $managerService)
    {
        $managerService->update($manager, $request->validated());

        return redirect()
            ->route('admin.managers.index')
            ->with('success', 'Gestor actualizado correctamente.');
    }

    public function destroy(User $manager)
    {
        $manager->delete();

        return redirect()
            ->route('admin.managers.index')
            ->with('success', 'Gestor eliminado correctamente.');
    }
}