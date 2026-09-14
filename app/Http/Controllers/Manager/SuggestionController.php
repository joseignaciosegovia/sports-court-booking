<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SuggestionIncident;
use Illuminate\Http\Request;
use App\Helpers\SortHelper;
use App\QueryFilters\SuggestionFilter;

class SuggestionController extends Controller
{
    public function index(Request $request, SuggestionFilter $filters)
    {
        $sortColumns = [
            'type' => 'suggestions_incidents.type',
            'date' => 'suggestions_incidents.created_at',
            'user' => 'suggestions_incidents.user_id',
            'content' => 'suggestions_incidents.content',
        ];

        $suggestions = SuggestionIncident::query()
            ->filter($filters)
            ->sort($sortColumns, 'suggestions_incidents.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('manager.suggestions', [
            'suggestions' => $suggestions,
            'filters' => [
                'type' => $request->input('type', ''),
                'date' => $request->input('date', ''),
            ],
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }
}