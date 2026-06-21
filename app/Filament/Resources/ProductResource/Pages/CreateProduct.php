<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $product = $this->record;
        $productTypeId = $product->product_type_id;

        // Get active dynamic fields for this product type
        $fields = \App\Models\ProductTypeField::where('product_type_id', $productTypeId)
            ->pluck('field_name')
            ->toArray();

        foreach ($fields as $fName) {
            if (array_key_exists($fName, $this->data)) {
                $product->attributeValues()->create([
                    'field_name' => $fName,
                    'value' => $this->data[$fName],
                ]);
            }
        }
    }
}
