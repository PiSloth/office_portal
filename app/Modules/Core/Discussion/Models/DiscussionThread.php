<?php

namespace App\Modules\Core\Discussion\Models;

use Illuminate\Database\Eloquent\Model;

class DiscussionThread extends Model
{
    protected $fillable = ['title', 'status'];

    public function threadable()
    {
        return $this->morphTo();
    }

    public function messages()
    {
        return $this->hasMany(DiscussionMessage::class, 'thread_id');
    }
}
