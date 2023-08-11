<?php

namespace App\Http\Livewire;

use App\Abstracts\Forms\EditFormRecord;
use App\Models\Setting\DocumentDefault;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * @property ComponentContainer $form
 */
class Invoice extends EditFormRecord
{
    public DocumentDefault $invoice;

    protected function getFormModel(): Model|string|null
    {
        $this->invoice = DocumentDefault::where('type', 'invoice')->firstOrNew();

        return $this->invoice;
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    public function fillForm(): void
    {
        $data = $this->getFormModel()->attributesToArray();

        unset($data['id']);

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('General')
                ->schema([
                    TextInput::make('number_prefix')
                        ->label('Number Prefix')
                        ->default('INV-')
                        ->reactive()
                        ->required(),
                    Select::make('number_digits')
                        ->label('Number Digits')
                        ->options($this->invoice->getAvailableNumberDigits())
                        ->default($this->invoice->getDefaultNumberDigits())
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $numDigits = $state;
                            $nextNumber = $this->invoice->getNextDocumentNumber($numDigits);

                            return $set('number_next', $nextNumber);
                        })
                        ->required()
                        ->searchable(),
                    TextInput::make('number_next')
                        ->label('Next Number')
                        ->reactive()
                        ->required()
                        ->default($this->invoice->getNextDocumentNumber($this->invoice->getDefaultNumberDigits())),
                    Select::make('payment_terms')
                        ->label('Payment Terms')
                        ->options($this->invoice->getPaymentTerms())
                        ->default($this->invoice->getDefaultPaymentTerms())
                        ->searchable()
                        ->reactive()
                        ->required(),
                ])->columns(),
            Section::make('Content')
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->reactive()
                        ->default('Invoice')
                        ->nullable(),
                    TextInput::make('subheading')
                        ->label('Subheading')
                        ->reactive()
                        ->nullable(),
                    Textarea::make('footer')
                        ->label('Footer')
                        ->reactive()
                        ->nullable(),
                    Textarea::make('terms')
                        ->label('Notes / Terms')
                        ->nullable()
                        ->reactive(),
                ])->columns(),
            Section::make('Template Settings')
                ->description('Choose the template and edit the titles of the columns on your invoices.')
                ->schema([
                    Group::make()
                        ->schema([
                            FileUpload::make('document_logo')
                                ->label('Logo')
                                ->disk('public')
                                ->directory('logos/documents')
                                ->imageResizeMode('contain')
                                ->imagePreviewHeight('250')
                                ->imageCropAspectRatio('2:1')
                                ->reactive()
                                ->enableOpen()
                                ->preserveFilenames()
                                ->visibility('public')
                                ->image(),
                            ColorPicker::make('accent_color')
                                ->label('Accent Color')
                                ->reactive()
                                ->default('#d9d9d9'),
                            Select::make('template')
                                ->label('Template')
                                ->options([
                                    'default' => 'Default',
                                    'modern' => 'Modern',
                                ])
                                ->reactive()
                                ->default('modern')
                                ->required(),
                            Radio::make('item_column')
                                ->label('Items')
                                ->options($this->invoice->getItemColumns())
                                ->dehydrateStateUsing(static function (callable $get, $state) {
                                    return $state === 'other' ? $get('custom_item_column') : $state;
                                })
                                ->afterStateHydrated(function (callable $set, callable $get, $state, Radio $component) {
                                    if (isset($this->invoice->getItemColumns()[$state])) {
                                        $component->state($state);
                                    } else {
                                        $component->state('other');
                                        $set('custom_item_column', $state);
                                    }
                                })
                                ->default($this->invoice->getDefaultItemColumn())
                                ->reactive(),
                            TextInput::make('custom_item_column')
                                ->reactive()
                                ->disableLabel()
                                ->disabled(static fn (callable $get) => $get('item_column') !== 'other')
                                ->nullable(),
                            Radio::make('unit_column')
                                ->label('Units')
                                ->options(DocumentDefault::getUnitColumns())
                                ->dehydrateStateUsing(static function (callable $get, $state) {
                                    return $state === 'other' ? $get('custom_unit_column') : $state;
                                })
                                ->afterStateHydrated(function (callable $set, callable $get, $state, Radio $component) {
                                    if (isset($this->invoice->getUnitColumns()[$state])) {
                                        $component->state($state);
                                    } else {
                                        $component->state('other');
                                        $set('custom_unit_column', $state);
                                    }
                                })
                                ->default($this->invoice->getDefaultUnitColumn())
                                ->reactive(),
                            TextInput::make('custom_unit_column')
                                ->reactive()
                                ->disableLabel()
                                ->disabled(static fn (callable $get) => $get('unit_column') !== 'other')
                                ->nullable(),
                            Radio::make('price_column')
                                ->label('Price')
                                ->options($this->invoice->getPriceColumns())
                                ->dehydrateStateUsing(static function (callable $get, $state) {
                                    return $state === 'other' ? $get('custom_price_column') : $state;
                                })
                                ->afterStateHydrated(function (callable $set, callable $get, $state, Radio $component) {
                                    if (isset($this->invoice->getPriceColumns()[$state])) {
                                        $component->state($state);
                                    } else {
                                        $component->state('other');
                                        $set('custom_price_column', $state);
                                    }
                                })
                                ->default($this->invoice->getDefaultPriceColumn())
                                ->reactive(),
                            TextInput::make('custom_price_column')
                                ->reactive()
                                ->disableLabel()
                                ->disabled(static fn (callable $get) => $get('price_column') !== 'other')
                                ->nullable(),
                            Radio::make('amount_column')
                                ->label('Amount')
                                ->options($this->invoice->getAmountColumns())
                                ->dehydrateStateUsing(static function (callable $get, $state) {
                                    return $state === 'other' ? $get('custom_amount_column') : $state;
                                })
                                ->afterStateHydrated(function (callable $set, callable $get, $state, Radio $component) {
                                    if (isset($this->invoice->getAmountColumns()[$state])) {
                                        $component->state($state);
                                    } else {
                                        $component->state('other');
                                        $set('custom_amount_column', $state);
                                    }
                                })
                                ->default($this->invoice->getDefaultAmountColumn())
                                ->reactive(),
                            TextInput::make('custom_amount_column')
                                ->reactive()
                                ->disableLabel()
                                ->disabled(static fn (callable $get) => $get('amount_column') !== 'other')
                                ->nullable(),
                        ])->columns(1),
                    Group::make()
                        ->schema([
                            ViewField::make('preview.default')
                                ->label('Preview')
                                ->visible(static fn (callable $get) => $get('template') === 'default')
                                ->view('components.invoice-layouts.default'),
                            ViewField::make('preview.modern')
                                ->label('Preview')
                                ->visible(static fn (callable $get) => $get('template') === 'modern')
                                ->view('components.invoice-layouts.modern'),
                        ])->columnSpan(2),
                ])->columns(3),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = 'invoice';

        return $data;
    }

    public function render(): View
    {
        return view('livewire.invoice');
    }
}
