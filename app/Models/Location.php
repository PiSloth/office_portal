<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['code', 'name', 'description'])]
class Location extends Model
{
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
