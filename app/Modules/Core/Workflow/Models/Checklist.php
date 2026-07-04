<?php

namespace App\Modules\Core\Workflow\Models;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    protected $fillable = ['product_type_id', 'name', 'description'];

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function items()
    {
        return $this->hasMany(ChecklistItem::class);
    }
}
