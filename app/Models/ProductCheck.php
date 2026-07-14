<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Collection;
use App\Models\DecisionRule;
use App\Models\Comment;

#[Fillable(['check_session_id', 'scan_config_id', 'product_id', 'barcode', 'quantity', 'location_id', 'checked_by', 'checked_at', 'result_status', 'remark'])]
class ProductCheck extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleted(function (ProductCheck $check): void {
            $check->decisions()->delete();
        });
    }
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    public function checkSession()
    {
        return $this->belongsTo(CheckSession::class);
    }

    public function scanConfig()
    {
        return $this->belongsTo(ScanConfig::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getClosingStockAttribute(): int
    {
        return (int) ($this->product?->quantity ?? 0);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function checkValues()
    {
        return $this->hasMany(ProductCheckValue::class);
    }

    public function decisions()
    {
        return $this->hasMany(Decision::class);
    }

    public function comments()
    {
        return $this->hasManyThrough(Comment::class, Decision::class, 'product_check_id', 'decision_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function failedCheckValues(): Collection
    {
        return $this->checkValues()
            ->where('status', '!=', 'PASS')
            ->orderBy('field_name')
            ->get();
    }

    public function matchedDecisionRules(): Collection
    {
        $failedFieldNames = $this->failedCheckValues()
            ->pluck('field_name')
            ->filter()
            ->map(fn ($value) => strtolower((string) $value))
            ->values();

        if ($this->result_status === 'UNMATCHED') {
            $failedFieldNames->push('result_status');
        }

        if ($failedFieldNames->isEmpty()) {
            return collect();
        }

        return DecisionRule::with('decisionType')
            ->where('is_active', true)
            ->get()
            ->filter(function (DecisionRule $rule) use ($failedFieldNames): bool {
                return $failedFieldNames->contains(strtolower((string) $rule->criteria_field));
            })
            ->values();
    }

    public function latestDecisionStatus(): ?string
    {
        return $this->decisions()
            ->latest('updated_at')
            ->value('action_status');
    }

    public function solutionStatus(): string
    {
        return match ($this->latestDecisionStatus()) {
            'DONE' => 'Resolved',
            'REJECTED' => 'Dismissed',
            'IN_PROGRESS' => 'In Progress',
            'OPEN' => 'Open',
            default => $this->result_status === 'PASS' ? 'No Action Needed' : 'Pending Review',
        };
    }

    public function reviewDecisionComments(): Collection
    {
        return $this->comments()
            ->with(['user', 'decision.decisionType'])
            ->latest()
            ->get()
            ->map(function (Comment $comment): array {
                return [
                    'decision' => $comment->decision?->decisionType?->name ?? 'Decision',
                    'user' => $comment->user?->name ?? 'Unknown',
                    'type' => $comment->comment_type,
                    'comment' => $comment->comment,
                    'created_at' => optional($comment->created_at)->toDateTimeString(),
                ];
            });
    }
}
