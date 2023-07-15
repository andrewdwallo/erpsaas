<?php

namespace App\Http\Livewire;

use App\Models\Setting\DocumentDefault;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;

/**
 * @property ComponentContainer $form
 */
class Invoice extends Component implements HasForms
{
    use InteractsWithForms;

    public DocumentDefault $invoice;

    public $data;

    public $record;

    public function mount(): void
    {
        $this->invoice = DocumentDefault::where('type', 'invoice')->firstOrNew();

        $this->form->fill([
            'document_number_prefix' => $this->invoice->document_number_prefix,
            'document_number_digits' => $this->invoice->document_number_digits,
            'document_number_next' => $this->invoice->document_number_next,
            'payment_terms' => $this->invoice->payment_terms,
            'template' => $this->invoice->template,
            'title' => $this->invoice->title,
            'subheading' => $this->invoice->subheading,
            'notes' => $this->invoice->notes,
            'footer' => $this->invoice->footer,
            'terms' => $this->invoice->terms,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('General')
                ->schema([
                    TextInput::make('document_number_prefix')
                        ->label('Number Prefix')
                        ->default('INV-')
                        ->required(),
                    Select::make('document_number_digits')
                        ->label('Number Digits')
                        ->options(DocumentDefault::getDocumentNumberDigits())
                        ->default(DocumentDefault::getDefaultDocumentNumberDigits())
                        ->reactive()
                        ->afterStateUpdated(static function (callable $set, $state) {
                            $numDigits = $state;
                            $nextNumber = DocumentDefault::getDefaultDocumentNumberNext($numDigits);

                            return $set('document_number_next', $nextNumber);
                        })
                        ->searchable()
                        ->required(),
                    TextInput::make('document_number_next')
                        ->label('Next Number')
                        ->default(DocumentDefault::getDefaultDocumentNumberNext(DocumentDefault::getDefaultDocumentNumberDigits()))
                        ->required(),
                    Select::make('payment_terms')
                        ->label('Payment Terms')
                        ->options(DocumentDefault::getPaymentTerms())
                        ->default(DocumentDefault::getDefaultPaymentTerms())
                        ->searchable()
                        ->required(),
                ])->columns(),
            Section::make('Template')
                ->schema([
                    Select::make('template')
                        ->label('Template')
                        ->options([
                            'default' => 'Default',
                            'simple' => 'Simple',
                            'modern' => 'Modern',
                        ])
                        ->default('default')
                        ->searchable()
                        ->required(),
                ])->columns(),
            Section::make('Content')
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->default('Invoice')
                        ->nullable(),
                    TextInput::make('subheading')
                        ->label('Subheading')
                        ->nullable(),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->nullable(),
                    Textarea::make('footer')
                        ->label('Footer')
                        ->nullable(),
                    Textarea::make('terms')
                        ->label('Terms')
                        ->nullable()
                        ->columnSpanFull(),
                ])->columns(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $data = $this->mutateFormDataBeforeSave($data);

        $this->record = $this->invoice->update($data);

        $this->form->model($this->record)->saveRelationships();

        $this->getSavedNotification()?->send();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['company_id'] = Auth::user()->currentCompany->id;
        $data['type'] = 'invoice';

        return $data;
    }

    protected function getSavedNotification():?Notification
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


    public function render(): View
    {
        return view('livewire.invoice');
    }
}
