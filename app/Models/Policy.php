<?php

namespace App\Models;

use App\PolicyContentFormat;
use App\PolicyStatus;
use Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'policy_category_id', 'title', 'summary', 'content', 'status', 'effective_date', 'published_at',
    'content_format', 'created_by', 'updated_by',
])]
class Policy extends Model
{
    /** @use HasFactory<PolicyFactory> */
    use HasFactory;

    protected $attributes = [
        'content_format' => PolicyContentFormat::PlainText->value,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PolicyCategory::class, 'policy_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'content_format' => PolicyContentFormat::class,
            'status' => PolicyStatus::class,
            'effective_date' => 'date',
            'published_at' => 'datetime',
        ];
    }
}
