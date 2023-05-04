<?php

use Wallo\FilamentCompanies\Features;
use Wallo\FilamentCompanies\Http\Middleware\AuthenticateSession;
use Wallo\FilamentCompanies\Providers;

return [

    /*
    |--------------------------------------------------------------------------
    | Company Stack
    |--------------------------------------------------------------------------
    |
    | This configuration value informs Company which "stack" you will be
    | using for your application. In general, this value is set for you
    | during installation and will not need to be changed after that.
    |
    */

    'stack' => 'filament',

    /*
     |--------------------------------------------------------------------------
     | Company Route Middleware
     |--------------------------------------------------------------------------
     |
     | Here you may specify which middleware Company will assign to the routes
     | that it registers with the application. When necessary, you may modify
     | these middleware; however, this default value is usually sufficient.
     |
     */

    'middleware' => config('filament.middleware.base'),

    'auth_session' => AuthenticateSession::class,

    /*
    |--------------------------------------------------------------------------
    | Company Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify the authentication guard Company will use while
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'sanctum',

    /*
    |--------------------------------------------------------------------------
    | Socialite Providers
    |--------------------------------------------------------------------------
    |
    | Here you may specify the providers your application supports for OAuth.
    | Out of the box, FilamentCompanies provides support for all the OAuth
    | providers that are supported by Laravel Socialite.
    |
    */

    'providers' => [
        Providers::github(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Some of Company's features are optional. You may disable the features
    | by removing them from this array. You're free to only remove some of
    | these features, or you can even remove all of these if you need to.
    |
    */

    'features' => [
        Features::termsAndPrivacyPolicy(),
        Features::profilePhotos(),
        Features::api(),
        Features::companies(['invitations' => true]),
        Features::accountDeletion(),
        Features::socialite(['rememberSession' => true, 'providerAvatars' => true]),
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the general layout of the package.
    |
    | Supported:
    | "sm", "md", "lg", "xl", "2xl",
    | "3xl", "4xl", "5xl", "6xl", "7xl", "full"
    |
    */

    'layout' => [
        'modals' => [
            'dialog_modal_width' => '2xl',
            'api_tokens' => [
                'create_modal_width' => '2xl',
                'edit_modal_width' => '2xl',
                'revoke_modal_width' => 'md',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile Photo Disk
    |--------------------------------------------------------------------------
    |
    | This configuration value determines the default disk that will be used
    | when storing profile photos for your application's users. Typically,
    | this will be the "public" disk, but you may adjust this if needed.
    |
    */

    'profile_photo_disk' => 'public',

];
