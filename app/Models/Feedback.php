<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\Sortable;
use \App\Enums\FeedbackType;

class Feedback extends Model
{
    use HasFactory, Filterable, Sortable;

    protected $table = 'feedback';

    protected $fillable = [
        'content',
        'type',
        'user_id',
    ];

    public const TYPES = [
        'suggestion',
        'incident',
    ];

    protected $casts = [
        'type' => FeedbackType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}