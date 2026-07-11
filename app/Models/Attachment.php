<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['attachable_type', 'attachable_id', 'file_path', 'file_name', 'file_type', 'file_size', 'uploaded_by'])]
class Attachment extends Model
{
    protected static function booted()
    {
        static::creating(function (Attachment $attachment) {
            if (empty($attachment->uploaded_by)) {
                $attachment->uploaded_by = auth()->id();
            }
            if (empty($attachment->file_name) && !empty($attachment->file_path)) {
                $attachment->file_name = basename($attachment->file_path);
            }
            if (empty($attachment->file_type) && !empty($attachment->file_path)) {
                $ext = strtolower(pathinfo($attachment->file_path, PATHINFO_EXTENSION));
                $attachment->file_type = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image/' . $ext : 'application/octet-stream';
            }
            if (empty($attachment->file_size)) {
                $attachment->file_size = 0;
            }
        });
    }

    public function attachable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
