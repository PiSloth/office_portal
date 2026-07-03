<?php

namespace App\Modules\Core\Validation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ValidationHistory extends Model
{
    protected $fillable = [
        'validatable_type',
        'validatable_id',
        'rule_id', 
        'status', 
        'input_value', 
        'expected_value', 
        'remarks',
        'user_id'
    ];

    public function validatable()
    {
        return $this->morphTo();
    }

    public function rule()
    {
        return $this->belongsTo(ValidationRule::class, 'rule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
