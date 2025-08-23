<?php

namespace App\Filament\Company\Clusters\Settings\Resources;

use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\AdjustmentScope;
use App\Enums\Accounting\AdjustmentStatus;
use App\Enums\Accounting\AdjustmentType;
use App\Filament\Company\Clusters\Settings;
use App\Filament\Company\Clusters\Settings\Resources\AdjustmentResource\Pages\CreateAdjustment;
use App\Filament\Company\Clusters\Settings\Resources\AdjustmentResource\Pages\EditAdjustment;
use App\Filament\Company\Clusters\Settings\Resources\AdjustmentResource\Pages\ListAdjustments;
use App\Models\Accounting\Adjustment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdjustmentResource extends Resource
{
    protected static ?string $model = Adjustment::class;

    protected static ?string $cluster = Settings::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->schema([
                        TextInput::make('name')
                            ->autofocus()
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Description'),
                    ]),
                Section::make('Configuration')
                    ->schema([
                        Select::make('category')
                            ->localizeLabel()
                            ->options(AdjustmentCategory::class)
                            ->default(AdjustmentCategory::Tax)
                            ->live()
                            ->required(),
                        Select::make('type')
                            ->localizeLabel()
                            ->options(AdjustmentType::class)
                            ->default(AdjustmentType::Sales)
                            ->live()
                            ->required(),
                        Checkbox::make('recoverable')
                            ->label('Recoverable')
                            ->default(false)
                            ->helperText('When enabled, tax is tracked separately as claimable from the government. Non-recoverable taxes are treated as part of the expense.')
                            ->visible(fn (Get $get) => AdjustmentCategory::parse($get('category'))->isTax() && AdjustmentType::parse($get('type'))->isPurchase()),
                    ])
                    ->columns()
                    ->visibleOn('create'),
                Section::make('Adjustment Details')
                    ->schema([
                        Select::make('computation')
                            ->localizeLabel()
                            ->options(AdjustmentComputation::class)
                            ->default(AdjustmentComputation::Percentage)
                            ->live()
                            ->required(),
                        TextInput::make('rate')
                            ->localizeLabel()
                            ->rate(static fn (Get $get) => $get('computation'))
                            ->required(),
                        Select::make('scope')
                            ->localizeLabel()
                            ->options(AdjustmentScope::class),
                    ])
                    ->columns(),
                Section::make('Dates')
                    ->schema([
                        DateTimePicker::make('start_date'),
                        DateTimePicker::make('end_date')
                            ->after('start_date'),
                    ])
                    ->columns()
                    ->visible(fn (Get $get) => AdjustmentCategory::parse($get('category'))->isDiscount()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('rate')
                    ->localizeLabel()
                    ->rate(static fn (Adjustment $record) => $record->computation->value)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('paused_until')
                    ->label('Auto-Resume Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->native(false)
                    ->default('unarchived')
                    ->options(
                        collect(AdjustmentStatus::cases())
                            ->mapWithKeys(fn (AdjustmentStatus $status) => [$status->value => $status->getLabel()])
                            ->merge([
                                'unarchived' => 'Unarchived',
                            ])
                            ->toArray()
                    )
                    ->indicateUsing(function (SelectFilter $filter, array $state) {
                        if (blank($state['value'] ?? null)) {
                            return [];
                        }

                        $label = collect($filter->getOptions())
                            ->mapWithKeys(fn (string | array $label, string $value): array => is_array($label) ? $label : [$value => $label])
                            ->get($state['value']);

                        if (blank($label)) {
                            return [];
                        }

                        $indicator = $filter->getIndicator();

                        if (! $indicator instanceof Indicator) {
                            if ($state['value'] === 'unarchived') {
                                $indicator = $label;
                            } else {
                                $indicator = Indicator::make("{$indicator}: {$label}");
                            }
                        }

                        return [$indicator];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        if ($data['value'] !== 'unarchived') {
                            return $query->where('status', $data['value']);
                        } else {
                            return $query->where('status', '!=', AdjustmentStatus::Archived->value);
                        }
                    }),
                SelectFilter::make('category')
                    ->label('Category')
                    ->native(false)
                    ->options(AdjustmentCategory::class),
                SelectFilter::make('type')
                    ->label('Type')
                    ->native(false)
                    ->options(AdjustmentType::class),
                SelectFilter::make('computation')
                    ->label('Computation')
                    ->native(false)
                    ->options(AdjustmentComputation::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('pause')
                        ->label('Pause')
                        ->icon('heroicon-m-pause')
                        ->schema([
                            DateTimePicker::make('paused_until')
                                ->label('Auto-resume date')
                                ->helperText('When should this adjustment automatically resume? Leave empty to keep paused indefinitely.')
                                ->after('now'),
                            Textarea::make('status_reason')
                                ->label('Reason for pausing')
                                ->maxLength(255),
                        ])
                        ->databaseTransaction()
                        ->successNotificationTitle('Adjustment paused')
                        ->failureNotificationTitle('Failed to pause adjustment')
                        ->visible(fn (Adjustment $record) => $record->canBePaused())
                        ->action(function (Adjustment $record, array $data, Action $action) {
                            $pausedUntil = $data['paused_until'] ?? null;
                            $reason = $data['status_reason'] ?? null;
                            $record->pause($reason, $pausedUntil);

                            $action->success();
                        }),
                    Action::make('resume')
                        ->label('Resume')
                        ->icon('heroicon-m-play')
                        ->requiresConfirmation()
                        ->databaseTransaction()
                        ->successNotificationTitle('Adjustment resumed')
                        ->failureNotificationTitle('Failed to resume adjustment')
                        ->visible(fn (Adjustment $record) => $record->canBeResumed())
                        ->action(function (Adjustment $record, Action $action) {
                            $record->resume();

                            $action->success();
                        }),
                    Action::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-m-archive-box')
                        ->color('danger')
                        ->schema([
                            Textarea::make('status_reason')
                                ->label('Reason for archiving')
                                ->maxLength(255),
                        ])
                        ->databaseTransaction()
                        ->successNotificationTitle('Adjustment archived')
                        ->failureNotificationTitle('Failed to archive adjustment')
                        ->visible(fn (Adjustment $record) => $record->canBeArchived())
                        ->action(function (Adjustment $record, array $data, Action $action) {
                            $reason = $data['status_reason'] ?? null;
                            $record->archive($reason);

                            $action->success();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('pause')
                        ->label('Pause')
                        ->icon('heroicon-m-pause')
                        ->form([
                            DateTimePicker::make('paused_until')
                                ->label('Auto-resume date')
                                ->helperText('When should these adjustments automatically resume? Leave empty to keep paused indefinitely.')
                                ->after('now'),
                            Textarea::make('status_reason')
                                ->label('Reason for pausing')
                                ->maxLength(255),
                        ])
                        ->databaseTransaction()
                        ->successNotificationTitle('Adjustments paused')
                        ->failureNotificationTitle('Failed to pause adjustments')
                        ->beforeFormFilled(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Adjustment $record) => ! $record->canBePaused());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Pause failed')
                                    ->body('Only adjustments that are currently active can be paused. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data, BulkAction $action) {
                            $pausedUntil = $data['paused_until'] ?? null;
                            $reason = $data['status_reason'] ?? null;

                            $records->each(function (Adjustment $record) use ($reason, $pausedUntil) {
                                $record->pause($reason, $pausedUntil);
                            });

                            $action->success();
                        }),
                    BulkAction::make('resume')
                        ->label('Resume')
                        ->icon('heroicon-m-play')
                        ->databaseTransaction()
                        ->requiresConfirmation()
                        ->successNotificationTitle('Adjustments resumed')
                        ->failureNotificationTitle('Failed to resume adjustments')
                        ->before(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Adjustment $record) => ! $record->canBeResumed());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Resume failed')
                                    ->body('Only adjustments that are currently paused can be resumed. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, BulkAction $action) {
                            $records->each(function (Adjustment $record) {
                                $record->resume();
                            });

                            $action->success();
                        }),
                    BulkAction::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-m-archive-box')
                        ->color('danger')
                        ->form([
                            Textarea::make('status_reason')
                                ->label('Reason for archiving')
                                ->maxLength(255),
                        ])
                        ->databaseTransaction()
                        ->successNotificationTitle('Adjustments archived')
                        ->failureNotificationTitle('Failed to archive adjustments')
                        ->beforeFormFilled(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Adjustment $record) => ! $record->canBeArchived());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Archive failed')
                                    ->body('Only adjustments that are currently active or paused can be archived. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data, BulkAction $action) {
                            $reason = $data['status_reason'] ?? null;

                            $records->each(function (Adjustment $record) use ($reason) {
                                $record->archive($reason);
                            });

                            $action->success();
                        }),
                ]),
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
            'index' => ListAdjustments::route('/'),
            'create' => CreateAdjustment::route('/create'),
            'edit' => EditAdjustment::route('/{record}/edit'),
        ];
    }
}
