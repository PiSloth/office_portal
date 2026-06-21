<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $product = $this->record;

        // Load EAV attribute values into form state
        foreach ($product->attributeValues as $attr) {
            $data[$attr->field_name] = $attr->value;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $product = $this->record;
        $productTypeId = $product->product_type_id;

        // Get dynamic fields for this product type
        $fields = \App\Models\ProductTypeField::where('product_type_id', $productTypeId)
            ->pluck('field_name')
            ->toArray();

        foreach ($fields as $fName) {
            if (array_key_exists($fName, $this->data)) {
                $product->attributeValues()->updateOrCreate(
                    ['field_name' => $fName],
                    ['value' => $this->data[$fName]],
                );
            }
        }
    }
}
