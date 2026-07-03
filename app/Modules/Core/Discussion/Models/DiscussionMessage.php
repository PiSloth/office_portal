<?php

namespace App\Modules\Core\Discussion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionMessage extends Model
{
    use SoftDeletes;

    protected $fillable = ['thread_id', 'user_id', 'body'];

    public function thread()
    {
        return $this->belongsTo(DiscussionThread::class, 'thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
