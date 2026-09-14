<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use App\Helpers\SortHelper;
use App\QueryFilters\FeedbackFilter;

class FeedbackController extends Controller
{
    public function index(Request $request, FeedbackFilter $filters)
    {
        $sortColumns = [
            'type' => 'feedback.type',
            'date' => 'feedback.created_at',
            'user' => 'feedback.user_id',
            'content' => 'feedback.content',
        ];

        $feedback = Feedback::query()
            ->filter($filters)
            ->sort($sortColumns, 'feedback.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('manager.feedback', [
            'feedback' => $feedback,
            'filters' => [
                'type' => $request->input('type', ''),
                'date' => $request->input('date', ''),
            ],
            'sorts' => SortHelper::getSorts($sortColumns),
            'sortColumns' => $sortColumns,
        ]);
    }
}