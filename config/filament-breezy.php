<?php

// config for JeffGreco13/FilamentBreezy
return [
    /*
    |--------------------------------------------------------------------------
    | My Profile filament page
    |--------------------------------------------------------------------------
    | Whether or not to automatically register the My Profile page. Set this to false to extend and create your own page.
    */
    'enable_profile_page' => false,
    /*
    | Whether or not to automatically link the My Profile page in the user menu of Filament. NOTE: if enable_profile_page => false then this value is ignored and you'll need to register the item in your service provider manually.
    */
    'show_profile_page_in_user_menu' => true,
    /*
    | Whether or not to automatically display the My Profile page in the navigation of Filament. NOTE: if enable_profile_page => false then this value is ignored.
    */
    'show_profile_page_in_navbar' => true,
    /*
    | Customize the icon profile page icon in the navbar. Does not apply to the user menu.
    */
    'profile_page_icon' => 'heroicon-o-document-text',
    /*
    | Set an array that's compatible with the Filament Forms rules() method. Rules for required and confirmed are already set. These rules will apply to the My Profile, registration, and password reset forms. To use an instance of the \Illuminate\Validation\Rules\Password::class, see documentation.
    */
    'password_rules' => [\Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()->mixedCase()],

    /*
    |--------------------------------------------------------------------------
    | Auth / User configs
    |--------------------------------------------------------------------------
    | This is the Auth model.
    */
    'user_model' => config(
        'auth.providers.users.model',
        App\Models\User::class
    ),
    /*
    |--------------------------------------------------------------------------
    | The users table in your database.
    */
    'users_table' => 'users',
    /*
    |--------------------------------------------------------------------------
    | The reset broker to be used in your reset password requests
    */
    'reset_broker' => config('auth.defaults.passwords'),
    /*
    |--------------------------------------------------------------------------
    | The column to use for login/username authentication. NOTE: this may change to just 'login_field' in a later release.
    */
    'fallback_login_field' => 'email',
    /*
    |--------------------------------------------------------------------------
    | Set a route name prefix for all of Breezy's auth routes. Ex. set filament. to prefix all route names, filament.register. WARNING: if you use a custom route prefix, you'll need to override the default auth routes used throughout your application. This is outside of Breezy's scope and will be up to the dev to maintain. Use at your own risk. See example: https://laravel.com/docs/9.x/passwords#password-customization
    */
    'route_group_prefix' => '',
    /*
    |--------------------------------------------------------------------------
    | Enable Two-Factor Authentication (2FA).
    */
    'enable_2fa' => true,
    /*
    |--------------------------------------------------------------------------
    | Number of seconds before asking the user to confirm their password in PasswordButtonAction again. 300 = 5 minutes
    */
    'password_confirmation_seconds' => config('auth.password_timeout'),
    /*
    |--------------------------------------------------------------------------
    | The max-w-xx of the auth card used on all pages.
    */
    'auth_card_max_w' => '3xl',
    /*
    |--------------------------------------------------------------------------
    | Enable or disable registration.
    */
    'enable_registration' => true,
    /*
    |--------------------------------------------------------------------------
    | Path to registration Livewire component.
    */
    'registration_component_path' => \App\Http\Livewire\Register::class,
    /*
    |--------------------------------------------------------------------------
    | Path to password reset Livewire component.
    */
    'password_reset_component_path' => \JeffGreco13\FilamentBreezy\Http\Livewire\Auth\ResetPassword::class,
    /*
    |--------------------------------------------------------------------------
    | Path to email verification Livewire component.
    */
    'email_verification_component_path' => \JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Verify::class,
    /*
    |--------------------------------------------------------------------------
    | Path to email verification Controller component.
    */
    'email_verification_controller_path' => \JeffGreco13\FilamentBreezy\Http\Controllers\EmailVerificationController::class,
    /*
    |--------------------------------------------------------------------------
    | Path to Profile page component.
    */
    'profile_page_component_path' => \App\Filament\Pages\MyProfile::class,
    /*
    |--------------------------------------------------------------------------
    | Where to redirect the user after registration.
    */
    'registration_redirect_url' => config('filament.home_url', '/'),
    /*
    |--------------------------------------------------------------------------
    | Enable sanctum api token management.
    */
    'enable_sanctum' => true,
    /*
    |--------------------------------------------------------------------------
    | Sanctum permissions
    */
    'sanctum_permissions' => ['create', 'read', 'update', 'delete'],
];
