<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['batch_id', 'row_number', 'data_json', 'errors_json', 'status'])]
class ProductImportLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'errors_json' => 'array',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(ProductImportBatch::class, 'batch_id');
    }
}
