<?php

namespace App\Abstracts\Forms;

use Filament\Forms\ComponentContainer;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * @property ComponentContainer $form
 */
abstract class EditFormRecord extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    abstract protected function getFormModel(): Model|string|null;

    public function mount(): void
    {
        $this->fillForm();
    }

    public function fillForm(): void
    {
        $data = $this->getFormModel()->attributesToArray();

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $data = $this->mutateFormDataBeforeSave($data);

        $this->handleRecordUpdate($this->getFormModel(), $data);

        $this->getSavedNotification()?->send();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

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
            ->title($title);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('filament::resources/pages/edit-record.messages.saved');
    }
}
