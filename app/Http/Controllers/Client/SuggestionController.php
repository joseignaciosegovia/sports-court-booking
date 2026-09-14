<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SuggestionIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SortHelper;
use App\Http\Requests\Client\StoreSuggestionRequest;
use App\QueryFilters\SuggestionFilter;

class SuggestionController extends Controller
{
    public function index(Request $request, SuggestionFilter $filters)
    {
        $types = SuggestionIncident::TYPES;

        $sortColumns = [
            'type' => 'suggestions_incidents.type',
            'date' => 'suggestions_incidents.created_at',
            'content' => 'suggestions_incidents.content',
        ];

        $suggestions = SuggestionIncident::with('user')
            ->where('suggestions_incidents.user_id', auth()->id())
            ->filter($filters)
            ->sort($sortColumns, 'suggestions_incidents.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('client.suggestion', [
            'types' => $types,
            'filters' => [
                'type' => $request->input('type', ''),
                'date' => $request->input('date', ''),
            ],
            'suggestions' => $suggestions,
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }

    public function store(StoreSuggestionRequest $request)
    {
        $user = Auth::user();

        $data = $request->validated();

        $suggestion = new SuggestionIncident();

        $suggestion->content = $data['content'];
        $suggestion->type = $data['type'];
        $suggestion->user_id = $user->id;

        $suggestion->save();

        return redirect()
            ->route('client.suggestions.index')
            ->with('success', 'Sugerencia/incidencia enviada correctamente.');
    }
}
