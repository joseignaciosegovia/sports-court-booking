<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SortHelper;
use App\Http\Requests\Client\StoreFeedbackRequest;
use App\QueryFilters\FeedbackFilter;

class FeedbackController extends Controller
{
    public function index(Request $request, FeedbackFilter $filters)
    {
        $types = Feedback::TYPES;

        $sortColumns = [
            'type' => 'feedback.type',
            'date' => 'feedback.created_at',
            'content' => 'feedback.content',
        ];

        $feedback = Feedback::with('user')
            ->where('feedback.user_id', auth()->id())
            ->filter($filters)
            ->sort($sortColumns, 'feedback.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('client.feedback.index', [
            'types' => $types,
            'filters' => [
                'type' => $request->input('type', ''),
                'date' => $request->input('date', ''),
            ],
            'feedback' => $feedback,
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }

    public function create()
    {
        $user = Auth::user();

        return view('client.feedback.create', [
            'authUser' => $user,
            'todayLong' => now()->translatedFormat('l, j \d\e F \d\e Y'),
        ]);
    }

    public function store(StoreFeedbackRequest $request)
    {
        $user = Auth::user();

        $data = $request->validated();

        $feedback = new Feedback();

        $feedback->content = $data['content'];
        $feedback->type = $data['type'];
        $feedback->user_id = $user->id;

        $feedback->save();

        return redirect()
            ->route('client.feedback.index')
            ->with('success', 'Sugerencia/incidencia enviada correctamente.');
    }
}