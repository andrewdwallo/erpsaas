<?php

namespace App\Filament\Company\Clusters\Settings\Resources;

use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\DocumentType;
use App\Enums\Setting\Font;
use App\Enums\Setting\PaymentTerms;
use App\Enums\Setting\Template;
use App\Filament\Company\Clusters\Settings;
use App\Filament\Company\Clusters\Settings\Resources\DocumentDefaultResource\Pages\EditDocumentDefault;
use App\Filament\Company\Clusters\Settings\Resources\DocumentDefaultResource\Pages\ListDocumentDefaults;
use App\Filament\Forms\Components\DocumentPreview;
use App\Models\Setting\DocumentDefault;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentDefaultResource extends Resource
{
    protected static ?string $model = DocumentDefault::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $modelLabel = 'document template';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->live()
            ->components([
                self::getGeneralSection(),
                self::getContentSection(),
                self::getTemplateSection(),
                self::getBillColumnLabelsSection(),
            ]);
    }

    public static function getGeneralSection(): Component
    {
        return Section::make('General')
            ->schema([
                TextInput::make('number_prefix')
                    ->localizeLabel()
                    ->nullable(),
                Select::make('payment_terms')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(PaymentTerms::class),
                Select::make('discount_method')
                    ->softRequired()
                    ->options(DocumentDiscountMethod::class),
            ])->columns();
    }

    public static function getContentSection(): Component
    {
        return Section::make('Content')
            ->hidden(static fn (DocumentDefault $record) => $record->type === DocumentType::Bill)
            ->schema([
                TextInput::make('header')
                    ->localizeLabel()
                    ->nullable(),
                TextInput::make('subheader')
                    ->localizeLabel()
                    ->nullable(),
                Textarea::make('terms')
                    ->localizeLabel()
                    ->nullable(),
                Textarea::make('footer')
                    ->localizeLabel('Footer')
                    ->nullable(),
            ])->columns();
    }

    public static function getTemplateSection(): Component
    {
        return Section::make('Template')
            ->description('Choose the template and edit the column names.')
            ->hidden(static fn (DocumentDefault $record) => $record->type === DocumentType::Bill)
            ->schema([
                Grid::make(1)
                    ->schema([
                        FileUpload::make('logo')
                            ->maxSize(1024)
                            ->localizeLabel()
                            ->openable()
                            ->directory('logos/document')
                            ->image()
                            ->imageCropAspectRatio('3:2')
                            ->panelAspectRatio('3:2')
                            ->panelLayout('compact')
                            ->extraAttributes([
                                'class' => 'es-file-upload document-logo-preview',
                            ])
                            ->loadingIndicatorPosition('left')
                            ->removeUploadedFileButtonPosition('right'),
                        Checkbox::make('show_logo')
                            ->localizeLabel(),
                        ColorPicker::make('accent_color')
                            ->localizeLabel(),
                        Select::make('font')
                            ->softRequired()
                            ->localizeLabel()
                            ->allowHtml()
                            ->options(
                                collect(Font::cases())
                                    ->mapWithKeys(static fn ($case) => [
                                        $case->value => "<span style='font-family:{$case->getLabel()}'>{$case->getLabel()}</span>",
                                    ]),
                            ),
                        Select::make('template')
                            ->softRequired()
                            ->localizeLabel()
                            ->options(Template::class),
                        ...static::getColumnLabelsSchema(),
                    ])->columnSpan(1),
                DocumentPreview::make()
                    ->template(static fn (Get $get) => Template::parse($get('template')))
                    ->preview()
                    ->columnSpan([
                        'lg' => 2,
                    ]),
            ])->columns(3);
    }

    public static function getBillColumnLabelsSection(): Component
    {
        return Section::make('Column Labels')
            ->visible(static fn (DocumentDefault $record) => $record->type === DocumentType::Bill)
            ->schema(static::getColumnLabelsSchema())->columns();
    }

    public static function getColumnLabelsSchema(): array
    {
        return [
            Select::make('item_name.option')
                ->softRequired()
                ->localizeLabel('Item name')
                ->options(DocumentDefault::getAvailableItemNameOptions())
                ->afterStateUpdated(static function (Get $get, Set $set, $state, $old) {
                    if ($state !== 'other' && $old === 'other' && filled($get('item_name.custom'))) {
                        $set('item_name.old_custom', $get('item_name.custom'));
                        $set('item_name.custom', null);
                    }

                    if ($state === 'other' && $old !== 'other') {
                        $set('item_name.custom', $get('item_name.old_custom'));
                    }
                }),
            TextInput::make('item_name.custom')
                ->hiddenLabel()
                ->extraFieldWrapperAttributes(static fn (DocumentDefault $record) => [
                    'class' => $record->type === DocumentType::Bill ? 'report-hidden-label' : '',
                ])
                ->disabled(static fn (callable $get) => $get('item_name.option') !== 'other')
                ->nullable(),
            Select::make('unit_name.option')
                ->softRequired()
                ->localizeLabel('Unit name')
                ->options(DocumentDefault::getAvailableUnitNameOptions())
                ->afterStateUpdated(static function (Get $get, Set $set, $state, $old) {
                    if ($state !== 'other' && $old === 'other' && filled($get('unit_name.custom'))) {
                        $set('unit_name.old_custom', $get('unit_name.custom'));
                        $set('unit_name.custom', null);
                    }

                    if ($state === 'other' && $old !== 'other') {
                        $set('unit_name.custom', $get('unit_name.old_custom'));
                    }
                }),
            TextInput::make('unit_name.custom')
                ->hiddenLabel()
                ->extraFieldWrapperAttributes(static fn (DocumentDefault $record) => [
                    'class' => $record->type === DocumentType::Bill ? 'report-hidden-label' : '',
                ])
                ->disabled(static fn (callable $get) => $get('unit_name.option') !== 'other')
                ->nullable(),
            Select::make('price_name.option')
                ->softRequired()
                ->localizeLabel('Price name')
                ->options(DocumentDefault::getAvailablePriceNameOptions())
                ->afterStateUpdated(static function (Get $get, Set $set, $state, $old) {
                    if ($state !== 'other' && $old === 'other' && filled($get('price_name.custom'))) {
                        $set('price_name.old_custom', $get('price_name.custom'));
                        $set('price_name.custom', null);
                    }

                    if ($state === 'other' && $old !== 'other') {
                        $set('price_name.custom', $get('price_name.old_custom'));
                    }
                }),
            TextInput::make('price_name.custom')
                ->hiddenLabel()
                ->extraFieldWrapperAttributes(static fn (DocumentDefault $record) => [
                    'class' => $record->type === DocumentType::Bill ? 'report-hidden-label' : '',
                ])
                ->disabled(static fn (callable $get) => $get('price_name.option') !== 'other')
                ->nullable(),
            Select::make('amount_name.option')
                ->softRequired()
                ->localizeLabel('Amount name')
                ->options(DocumentDefault::getAvailableAmountNameOptions())
                ->afterStateUpdated(static function (Get $get, Set $set, $state, $old) {
                    if ($state !== 'other' && $old === 'other' && filled($get('amount_name.custom'))) {
                        $set('amount_name.old_custom', $get('amount_name.custom'));
                        $set('amount_name.custom', null);
                    }

                    if ($state === 'other' && $old !== 'other') {
                        $set('amount_name.custom', $get('amount_name.old_custom'));
                    }
                }),
            TextInput::make('amount_name.custom')
                ->hiddenLabel()
                ->extraFieldWrapperAttributes(static fn (DocumentDefault $record) => [
                    'class' => $record->type === DocumentType::Bill ? 'report-hidden-label' : '',
                ])
                ->disabled(static fn (callable $get) => $get('amount_name.option') !== 'other')
                ->nullable(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('number_prefix'),
                TextColumn::make('template')
                    ->badge(),
                IconColumn::make('show_logo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentDefaults::route('/'),
            'edit' => EditDocumentDefault::route('/{record}/edit'),
        ];
    }
}
