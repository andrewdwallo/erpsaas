<?php

namespace App\Filament\Company\Pages\Setting;

use App\Enums\DocumentType;
use App\Enums\Font;
use App\Enums\PaymentTerms;
use App\Enums\Template;
use App\Models\Setting\DocumentDefault as InvoiceModel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use function Filament\authorize;

/**
 * @property Form $form
 */
class Invoice extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Invoice';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/invoice';

    protected ?string $heading = 'Invoice';

    protected static string $view = 'filament.company.pages.setting.invoice';

    public ?array $data = [];

    public ?InvoiceModel $record = null;

    public function mount(): void
    {
        $this->record = InvoiceModel::invoice()
            ->firstOrNew([
                'company_id' => auth()->user()->currentCompany->id,
                'type' => DocumentType::Invoice->value,
            ]);

        abort_unless(static::canView($this->record), 404);

        $this->fillForm();
    }

    public function fillForm(): void
    {
        $data = $this->record->attributesToArray();

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $data = $this->mutateFormDataBeforeSave($data);

            $this->handleRecordUpdate($this->record, $data);

        } catch (Halt $exception) {
            return;
        }

        $this->getSavedNotification()?->send();

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl);
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        $title = $this->getSavedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($this->getSavedNotificationTitle());
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('filament-panels::pages/tenancy/edit-tenant-profile.notifications.saved.title');
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getGeneralSection(),
                $this->getContentSection(),
                $this->getTemplateSection(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    protected function getGeneralSection(): Component
    {
        return Section::make('General')
            ->schema([
                TextInput::make('number_prefix')
                    ->label('Number Prefix')
                    ->live()
                    ->required(),
                Select::make('number_digits')
                    ->label('Number Digits')
                    ->options(InvoiceModel::availableNumberDigits())
                    ->native(false)
                    ->live()
                    ->required(),
                TextInput::make('number_next')
                    ->label('Next Number')
                    ->live()
                    ->maxLength(static fn (Get $get) => $get('number_digits'))
                    ->suffix(static function (Get $get, $state) {
                        $number_prefix = $get('number_prefix');
                        $number_digits = $get('number_digits');
                        $number_next = $state;

                        return InvoiceModel::getNumberNext(true, true, $number_prefix, $number_digits, $number_next);
                    })
                    ->required(),
                Select::make('payment_terms')
                    ->label('Payment Terms')
                    ->options(PaymentTerms::class)
                    ->native(false)
                    ->live()
                    ->required(),
            ])->columns();
    }

    protected function getContentSection(): Component
    {
        return Section::make('Content')
            ->schema([
                TextInput::make('header')
                    ->label('Header')
                    ->live()
                    ->required(),
                TextInput::make('subheader')
                    ->label('Subheader')
                    ->live()
                    ->nullable(),
                Textarea::make('terms')
                    ->label('Terms')
                    ->live()
                    ->nullable(),
                Textarea::make('footer')
                    ->label('Footer / Notes')
                    ->live()
                    ->nullable(),
            ])->columns();
    }

    protected function getTemplateSection(): Component
    {
        return Section::make('Template')
            ->description('Choose the template and edit the column names.')
            ->schema([
                Group::make()
                    ->live()
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->disk('public')
                            ->directory('logos/document')
                            ->imageResizeMode('contain')
                            ->imagePreviewHeight('250')
                            ->imageCropAspectRatio('2:1')
                            ->getUploadedFileNameForStorageUsing(
                                static fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(Auth::user()->currentCompany->id . '_'),
                            )
                            ->openable()
                            ->maxSize(2048)
                            ->image()
                            ->visibility('public')
                            ->acceptedFileTypes(['image/png', 'image/jpeg']),
                        Checkbox::make('show_logo')
                            ->label('Show Logo'),
                        ColorPicker::make('accent_color')
                            ->label('Accent Color'),
                        Select::make('font')
                            ->label('Font')
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->rule('required')
                            ->allowHtml()
                            ->options(collect(Font::cases())
                                ->mapWithKeys(static fn ($case) => [
                                    $case->value => "<span style='font-family:{$case->getLabel()}'>{$case->getLabel()}</span>"
                                ]),
                            ),
                        Select::make('template')
                            ->label('Template')
                            ->native(false)
                            ->options(Template::class)
                            ->required(),
                        Select::make('item_name.option')
                            ->label('Item Name')
                            ->native(false)
                            ->required()
                            ->options(InvoiceModel::getAvailableItemNameOptions()),
                        TextInput::make('item_name.custom')
                            ->hiddenLabel()
                            ->disabled(static fn (callable $get) => $get('item_name.option') !== 'other')
                            ->nullable(),
                        Select::make('unit_name.option')
                            ->label('Unit Name')
                            ->native(false)
                            ->required()
                            ->options(InvoiceModel::getAvailableUnitNameOptions()),
                        TextInput::make('unit_name.custom')
                            ->hiddenLabel()
                            ->disabled(static fn (callable $get) => $get('unit_name.option') !== 'other')
                            ->nullable(),
                        Select::make('price_name.option')
                            ->label('Price Name')
                            ->native(false)
                            ->required()
                            ->options(InvoiceModel::getAvailablePriceNameOptions()),
                        TextInput::make('price_name.custom')
                            ->hiddenLabel()
                            ->disabled(static fn (callable $get) => $get('price_name.option') !== 'other')
                            ->nullable(),
                        Select::make('amount_name.option')
                            ->label('Amount Name')
                            ->native(false)
                            ->required()
                            ->options(InvoiceModel::getAvailableAmountNameOptions()),
                        TextInput::make('amount_name.custom')
                            ->hiddenLabel()
                            ->disabled(static fn (callable $get) => $get('amount_name.option') !== 'other')
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
                        ViewField::make('preview.classic')
                            ->label('Preview')
                            ->visible(static fn (callable $get) => $get('template') === 'classic')
                            ->view('components.invoice-layouts.classic'),
                    ])->columnSpan(2),
            ])->columns(3);
    }

    protected function handleRecordUpdate(InvoiceModel $record, array $data): InvoiceModel
    {
        $record->update($data);

        return $record;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::pages/tenancy/edit-tenant-profile.form.actions.save.label'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public static function canView(Model $record): bool
    {
        try {
            return authorize('update', $record)->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }
}
