<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\ScanConfig;
use App\Models\ProductType;
use App\Models\ProductCheck;
use App\Models\User;

#[Fillable(['name', 'description', 'started_by', 'started_at', 'completed_at', 'status', 'product_type_id', 'scan_config_id', 'assigned_user_id'])]
class CheckSession extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function productChecks()
    {
        return $this->hasMany(ProductCheck::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function scanConfig()
    {
        return $this->belongsTo(ScanConfig::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'check_session_user');
    }

    public function importBatches()
    {
        return $this->hasMany(ProductImportBatch::class, 'check_session_id');
    }
}
