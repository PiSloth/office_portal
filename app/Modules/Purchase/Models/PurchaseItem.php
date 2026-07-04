<?php

namespace App\Modules\Purchase\Models;

use App\Models\ProductType;
use App\Modules\Core\Calculation\Models\CalculationHistory;
use App\Modules\Core\Validation\Models\ValidationHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_request_id', 
        'product_type_id', 
        'dynamic_fields_json', 
        'calculated_price'
    ];

    protected $casts = [
        'dynamic_fields_json' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($item) {
            if ($item->purchaseRequest) {
                $item->purchaseRequest->updateTotalAmount();
            }
        });

        static::deleted(function ($item) {
            if ($item->purchaseRequest) {
                $item->purchaseRequest->updateTotalAmount();
            }
        });
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function calculationHistories()
    {
        return $this->morphMany(CalculationHistory::class, 'calculatable');
    }

    public function validationHistories()
    {
        return $this->morphMany(ValidationHistory::class, 'validatable');
    }
}
