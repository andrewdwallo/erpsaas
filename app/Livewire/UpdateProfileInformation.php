<?php

namespace App\Livewire;

use App\Models\User;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use RuntimeException;
use Wallo\FilamentCompanies\Features;

/**
 * @property Form $form
 */
class UpdateProfileInformation extends Component implements HasForms
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
        $data = $this->getUser()->withoutRelations()->toArray();

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
                Forms\Components\FileUpload::make('profile_photo_path')
                    ->label('Photo')
                    ->avatar()
                    ->extraAttributes([
                        'style' => 'width: 6rem; height: 6rem;',
                    ])
                    ->placeholder(static function () {
                        return new HtmlString('
                            <div style="display: inline-block; cursor: pointer;">
                                <div class="flex items-center justify-center bg-gray-50 dark:bg-gray-800" style="
                                    border-radius: 50%;
                                    width: 50px;
                                    height: 50px;">
                                   ' . Blade::render('<x-heroicon-o-camera class="w-8 h-8 text-gray-800 dark:text-gray-300" />') . '
                                </div>
                            </div>
                        ');
                    })
                    ->disk(Features::profilePhotoDisk())
                    ->directory(Features::profilePhotoStoragePath())
                    ->saveUploadedFileUsing(function (User $record, UploadedFile $file) {
                        $record->updateProfilePhoto($file);
                    })
                    ->deleteUploadedFileUsing(function (User $record) {
                        $record->deleteProfilePhoto();
                    })
                    ->image()
                    ->nullable(),
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                Forms\Components\TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->required()
                    ->autocomplete('username')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ])
            ->operation('edit')
            ->model($this->getUser())
            ->statePath('data');
    }

    public function render(): View
    {
        return view('livewire.update-profile-information');
    }
}
