<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'file_path',
    'file_name',
    'product_type_id',
    'status',
    'total_rows',
    'imported_rows',
    'failed_rows',
    'created_by',
    'check_session_id'
])]
class ProductImportBatch extends Model
{
    public function checkSession()
    {
        return $this->belongsTo(CheckSession::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function logs()
    {
        return $this->hasMany(ProductImportLog::class, 'batch_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'import_batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
