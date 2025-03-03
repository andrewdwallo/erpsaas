<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use RuntimeException;

/**
 * @property Form $form
 */
class UpdatePassword extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    public function getUser(): Authenticatable | Model
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
            throw new RuntimeException('The authenticated user object must be an Eloquent model to allow profile information to be updated.');
        }

        return $user;
    }

    public function fillForm(): void
    {
        $data = $this->getUser()->attributesToArray();

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $data = $this->mutateFormDataBeforeSave($data);

            $this->handleRecordUpdate($this->getUser(), $data);
        } catch (Halt $exception) {
            return;
        }

        if (session() !== null) {
            session()->put([
                'password_hash_' . Filament::getAuthGuard() => Filament::auth()->user()?->getAuthPassword(),
            ]);
        }

        $this->getSavedNotification()?->send();

        $this->fillForm();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        $title = $this->getSavedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($this->getSavedNotificationTitle())
            ->body($this->getSavedNotificationBody());
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('filament-companies::default.notifications.profile_information_updated.title');
    }

    protected function getSavedNotificationBody(): ?string
    {
        return __('filament-companies::default.notifications.profile_information_updated.body');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('current_password')
                    ->label(__('filament-companies::default.fields.current_password'))
                    ->password()
                    ->currentPassword()
                    ->revealable()
                    ->validationMessages([
                        'current_password' => __('filament-companies::default.errors.password_does_not_match'),
                    ])
                    ->autocomplete('current-password')
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label(__('filament-companies::default.labels.new_password'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->rule(Password::default())
                    ->required()
                    ->dehydrated(static fn ($state): bool => filled($state))
                    ->dehydrateStateUsing(static fn ($state): string => Hash::make($state))
                    ->same('password_confirmation'),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label(__('filament-companies::default.labels.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->dehydrated(false),
            ])
            ->operation('edit')
            ->model($this->getUser())
            ->statePath('data');
    }

    public function render(): View
    {
        return view('livewire.update-password-form');
    }
}
