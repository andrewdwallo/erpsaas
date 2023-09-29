<?php

namespace App\Models;

use App\Models\Core\Department;
use Filament\Models\Contracts\{FilamentUser, HasAvatar, HasDefaultTenant, HasTenants};
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Wallo\FilamentCompanies\{HasCompanies, HasConnectedAccounts, HasProfilePhoto, SetsProfilePhotoFromUrl};

/** @property Company $currentCompany */
class User extends Authenticatable implements FilamentUser, HasAvatar, HasDefaultTenant, HasTenants
{
    use HasApiTokens;
    use HasCompanies;
    use HasConnectedAccounts;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use SetsProfilePhotoFromUrl;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getTenants(Panel $panel): array | Collection
    {
        return $this->allCompanies();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->belongsToCompany($tenant);
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->currentCompany;
    }

    public function getFilamentAvatarUrl(): string
    {
        return $this->profile_photo_url;
    }

    public function managers(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }
}
