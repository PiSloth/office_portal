<?php

namespace App\Events;

use App\Models\ProductCheck;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductChecked
{
    use Dispatchable, SerializesModels;

    public ProductCheck $productCheck;

    /**
     * Create a new event instance.
     */
    public function __construct(ProductCheck $productCheck)
    {
        $this->productCheck = $productCheck;
    }
}
