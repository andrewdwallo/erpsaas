<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use JeffGreco13\FilamentBreezy\FilamentBreezy;
use JeffGreco13\FilamentBreezy\Traits\HasBreezyTwoFactor;

class MyProfile extends Page
{
    use HasBreezyTwoFactor;

    protected static string $view = 'filament.pages.my-profile';

    public $user;

    public $userData;

    // Password
    public $new_password;

    public $new_password_confirmation;

    // Sanctum tokens
    public $token_name;

    public $abilities = [];

    public $plain_text_token;

    protected $loginColumn;

    public function boot()
    {
        // user column
        $this->loginColumn = config('filament-breezy.fallback_login_field');
    }

    public function mount()
    {
        $this->user = Filament::auth()->user();
        $this->updateProfileForm->fill($this->user->toArray());
    }

    protected function getForms(): array
    {
        return array_merge(parent::getForms(), [
            'updateProfileForm' => $this->makeForm()
                ->model(config('filament-breezy.user_model'))
                ->schema($this->getUpdateProfileFormSchema())
                ->statePath('userData'),

            // Start New Section for MyProfile Account Page
            'updateCompanyProfileForm' => $this->makeForm()
            ->model(config('filament-breezy.user_model'))
            ->schema($this->getUpdateCompanyProfileFormSchema())
            ->statePath('userData'),
            // End New Section for MyProfile Account Page

            'updatePasswordForm' => $this->makeForm()->schema(
                $this->getUpdatePasswordFormSchema()
            ),
            'createApiTokenForm' => $this->makeForm()->schema(
                $this->getCreateApiTokenFormSchema()
            ),
            'confirmTwoFactorForm' => $this->makeForm()->schema(
                $this->getConfirmTwoFactorFormSchema()
            ),
        ]);
    }

    protected function getUpdateProfileFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->label(__('filament-breezy::default.fields.name')),
            Forms\Components\TextInput::make($this->loginColumn)
                ->required()
                ->email(fn () => $this->loginColumn === 'email')
                ->unique(config('filament-breezy.user_model'), ignorable: $this->user)
                ->label(__('filament-breezy::default.fields.email')),
        ];
    }

    public function updateProfile()
    {
        $this->user->update($this->updateProfileForm->getState());
        $this->notify('success', __('filament-breezy::default.profile.personal_info.notify'));
    }

    // Start New Section for "Updating" MyProfile Account Page
    protected function getUpdateCompanyProfileFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('company_name')->label('Company Name')->required()->maxLength(100)->autofocus(),
            Forms\Components\TextInput::make('website')->prefix('https://')->maxLength(250)->required()->label('Website'),
            Forms\Components\TextInput::make('address')->maxLength(100)->label('Address'),
            Forms\Components\FileUpload::make('logo')->image()->directory('logos')->label('Company Logo'),
        ];
    }

    public function updateCompanyProfile()
    {
        $this->user->update($this->updateCompanyProfileForm->getState());
        $this->notify('success', __('Company Information Updated'));
    }
    // End New Section for "Updating" MyProfile Account Page

    protected function getUpdatePasswordFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('new_password')
                ->label(__('filament-breezy::default.fields.new_password'))
                ->password()
                ->rules(app(FilamentBreezy::class)->getPasswordRules())
                ->required(),
            Forms\Components\TextInput::make('new_password_confirmation')
                ->label(__('filament-breezy::default.fields.new_password_confirmation'))
                ->password()
                ->same('new_password')
                ->required(),
        ];
    }

    public function updatePassword()
    {
        $state = $this->updatePasswordForm->getState();
        $this->user->update([
            'password' => Hash::make($state['new_password']),
        ]);
        session()->forget('password_hash_web');
        Filament::auth()->login($this->user);
        $this->notify('success', __('filament-breezy::default.profile.password.notify'));
        $this->reset(['new_password', 'new_password_confirmation']);
    }

    protected function getCreateApiTokenFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('token_name')->label(__('filament-breezy::default.fields.token_name'))->required(),
            Forms\Components\CheckboxList::make('abilities')
            ->label(__('filament-breezy::default.fields.abilities'))
            ->options(config('filament-breezy.sanctum_permissions'))
            ->columns(2)
            ->required(),
        ];
    }

    public function createApiToken()
    {
        $state = $this->createApiTokenForm->getState();
        $indexes = $state['abilities'];
        $abilities = config('filament-breezy.sanctum_permissions');
        $selected = collect($abilities)->filter(function ($item, $key) use (
            $indexes
        ) {
            return in_array($key, $indexes);
        })->toArray();
        $this->plain_text_token = Filament::auth()->user()->createToken($state['token_name'], array_values($selected))->plainTextToken;
        $this->notify('success', __('filament-breezy::default.profile.sanctum.create.notify'));
        $this->emit('tokenCreated');
        $this->reset(['token_name']);
    }

    protected function getBreadcrumbs(): array
    {
        return [
            url()->current() => __('filament-breezy::default.profile.profile'),
        ];
    }

    protected static function getNavigationIcon(): string
    {
        return config('filament-breezy.profile_page_icon', 'heroicon-o-document-text');
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('filament-breezy::default.profile.account');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-breezy::default.profile.profile');
    }

    protected function getTitle(): string
    {
        return __('filament-breezy::default.profile.my_profile');
    }

    protected static function shouldRegisterNavigation(): bool
    {
        return config('filament-breezy.show_profile_page_in_navbar');
    }
}
