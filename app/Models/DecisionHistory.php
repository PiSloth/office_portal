<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['decision_id', 'old_status', 'new_status', 'changed_by', 'remark'])]
class DecisionHistory extends Model
{
    public function decision()
    {
        return $this->belongsTo(Decision::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
