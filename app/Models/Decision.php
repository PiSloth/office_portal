<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_check_id', 'decision_type_id', 'decision_rule_id', 'action_status', 'assigned_to', 'decision_by', 'remark'])]
class Decision extends Model
{
    protected static function booted(): void
    {
        static::updated(function (Decision $decision): void {
            if (! $decision->wasChanged('action_status')) {
                return;
            }

            DecisionHistory::create([
                'decision_id' => $decision->id,
                'old_status' => (string) ($decision->getOriginal('action_status') ?? 'NONE'),
                'new_status' => (string) $decision->action_status,
                'changed_by' => auth()->id() ?? $decision->decision_by,
                'remark' => 'Status updated from Filament.',
            ]);
        });
    }

    public function productCheck()
    {
        return $this->belongsTo(ProductCheck::class);
    }

    public function decisionType()
    {
        return $this->belongsTo(DecisionType::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function decisionBy()
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function histories()
    {
        return $this->hasMany(DecisionHistory::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function decisionRule()
    {
        return $this->belongsTo(DecisionRule::class, 'decision_rule_id');
    }
}
