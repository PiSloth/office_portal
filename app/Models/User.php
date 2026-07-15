<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'status', 'last_login_at', 'branch_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function passwordResetLogs()
    {
        return $this->hasMany(PasswordResetLog::class);
    }

    public function productChecks()
    {
        return $this->hasMany(ProductCheck::class, 'checked_by');
    }

    public function assignedSessions()
    {
        return $this->hasMany(CheckSession::class, 'assigned_user_id');
    }

    public function decisionsAssigned()
    {
        return $this->hasMany(Decision::class, 'assigned_to');
    }

    public function decisionsCreated()
    {
        return $this->hasMany(Decision::class, 'decision_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->can('admin.access')) {
            return true;
        }

        return $this->hasAnyRole($this->adminAccessRoles());
    }

    /**
     * @return array<int, string>
     */
    public static function adminAccessRoles(): array
    {
        return [
            'super-admin',
            'Super Admin',
            'admin',
            'Admin',
            'manager',
            'Manager',
            'supervisor',
            'Supervisor',
            'checker',
            'Checker',
            'purchaser',
            'Purchaser',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['super-admin', 'Super Admin']);
    }
}
