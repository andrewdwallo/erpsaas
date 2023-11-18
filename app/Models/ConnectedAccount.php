<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Wallo\FilamentCompanies\ConnectedAccount as SocialiteConnectedAccount;
use Wallo\FilamentCompanies\Events\ConnectedAccountCreated;
use Wallo\FilamentCompanies\Events\ConnectedAccountDeleted;
use Wallo\FilamentCompanies\Events\ConnectedAccountUpdated;

class ConnectedAccount extends SocialiteConnectedAccount
{
    use HasTimestamps;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'provider',
        'provider_id',
        'name',
        'nickname',
        'email',
        'avatar_path',
        'token',
        'refresh_token',
        'expires_at',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => ConnectedAccountCreated::class,
        'updated' => ConnectedAccountUpdated::class,
        'deleted' => ConnectedAccountDeleted::class,
    ];
}
