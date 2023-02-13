<?php

namespace App\Filament\Resources;

use App\Components\PasswordGenerator;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use BezhanSalleh\FilamentShield\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Closure;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $label = 'User';

    protected static ?string $navigationGroup = 'Filament Shield';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->unique(User::class, 'email', fn ($record) => $record),
                        Forms\Components\Toggle::make('reset_password')
                            ->columnSpan('full')
                            ->reactive()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                        PasswordGenerator::make('password')
                            ->columnSpan('full')
                            ->visible(fn ($livewire, $get) => $livewire instanceof CreateUser || $get('reset_password') == true)
                            ->rules(config('filament-breezy.password_rules', 'max:8'))
                            ->required()
                            ->dehydrateStateUsing(function ($state) {
                                return Hash::make($state);
                            }),
                        Forms\Components\CheckboxList::make('roles')
                            ->columnSpan('full')
                            ->reactive()
                            ->relationship('roles', 'name', function (Builder $query) {
                                if (! auth()->user()->hasRole('super_admin')) {
                                    return $query->where('name', '<>', 'super_admin');
                                }

                                return $query;
                            })
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return Str::of($record->name)->headline();
                            })
                            ->columns(4),
                        Forms\Components\TextInput::make('company_name')->required()->maxLength(100)->autofocus(),
                        Forms\Components\TextInput::make('website')->prefix('https://')->maxLength(250),
                        Forms\Components\TextInput::make('address')->maxLength(250),
                        Forms\Components\FileUpload::make('logo')->image()->directory('logos'),
                    ])->columns(['md' => 2]),
                Forms\Components\Section::make('Permissions')
                    ->description('Users with roles have permission to completely manage resources based on the permissions set under the Roles Menu. To limit a user\'s access to specific resources disable thier roles and assign them individual permissions below.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Tabs::make('Permissions')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.resources'))
                                ->visible(fn (): bool => (bool) Utils::isResourceEntityEnabled())
                                ->reactive()
                                ->schema(static::getResourceEntitiesSchema()),
                            Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.pages'))
                                ->visible(fn (): bool => (bool) Utils::isPageEntityEnabled() && (count(FilamentShield::getPages()) > 0 ? true : false))
                                ->reactive()
                                ->schema(static::getPageEntityPermissionsSchema()),
                            Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.widgets'))
                                ->visible(fn (): bool => (bool) Utils::isWidgetEntityEnabled() && (count(FilamentShield::getWidgets()) > 0 ? true : false))
                                ->reactive()
                                ->schema(static::getWidgetEntityPermissionSchema()),
                            Forms\Components\Tabs\Tab::make(__('filament-shield::filament-shield.custom'))
                                ->visible(fn (): bool => (bool) Utils::isCustomPermissionEntityEnabled())
                                ->reactive()
                                ->schema(static::getCustomEntitiesPermisssionSchema()),
                        ])
                        ->columnSpan('full'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->formatStateUsing(function ($state) {
                        return Str::of($state)->headline();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    protected static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function getResourceEntitiesSchema(): ?array
    {
        return collect(FilamentShield::getResources())->sortKeys()->reduce(function ($entities, $entity) {
            $entities[] = Forms\Components\Card::make()
                    ->extraAttributes(['class' => 'border-0 shadow-lg dark:bg-gray-900'])
                    ->schema([
                        Forms\Components\Toggle::make($entity['resource'])
                            ->label(FilamentShield::getLocalizedResourceLabel($entity['fqcn']))
                            ->onIcon('heroicon-s-lock-open')
                            ->offIcon('heroicon-s-lock-closed')
                            ->reactive()
                            ->afterStateUpdated(function (Closure $set, Closure $get, $state) use ($entity) {
                                collect(Utils::getGeneralResourcePermissionPrefixes())->each(function ($permission) use ($set, $entity, $state) {
                                    $set($permission.'_'.$entity['resource'], $state);
                                });

                                if (! $state) {
                                    $set('select_all', false);
                                }

                                static::refreshSelectAllStateViaEntities($set, $get);
                            })
                            ->dehydrated(false),
                        Forms\Components\Fieldset::make('Permissions')
                            ->label(__('filament-shield::filament-shield.column.permissions'))
                            ->extraAttributes(['class' => 'text-primary-600 border-gray-300 dark:border-gray-800'])
                            ->columns([
                                'default' => 2,
                                'md' => 3,
                                'lg' => 3,
                                'xl' => 4,
                            ])
                            ->schema(static::getResourceEntityPermissionsSchema($entity)),
                    ])
                    ->columns(2)
                    ->columnSpan(1);

            return $entities;
        }, collect())
        ->toArray();
    }

    public static function getResourceEntityPermissionsSchema($entity): ?array
    {
        return collect(Utils::getGeneralResourcePermissionPrefixes())->reduce(function ($permissions /** @phpstan ignore-line */, $permission) use ($entity) {
            $permissions[] = Forms\Components\Checkbox::make($permission.'_'.$entity['resource'])
                ->label(FilamentShield::getLocalizedResourcePermissionLabel($permission))
                ->extraAttributes(['class' => 'text-primary-600'])
                ->afterStateHydrated(function (Closure $set, Closure $get, $record) use ($entity, $permission) {
                    if (is_null($record)) {
                        return;
                    }

                    $set($permission.'_'.$entity['resource'], $record->checkPermissionTo($permission.'_'.$entity['resource']));

                    static::refreshResourceEntityStateAfterHydrated($record, $set, $entity['resource']);

                    static::refreshSelectAllStateViaEntities($set, $get);
                })
                ->reactive()
                ->afterStateUpdated(function (Closure $set, Closure $get, $state) use ($entity) {
                    static::refreshResourceEntityStateAfterUpdate($set, $get, Str::of($entity['resource']));

                    if (! $state) {
                        $set($entity['resource'], false);
                        $set('select_all', false);
                    }

                    static::refreshSelectAllStateViaEntities($set, $get);
                })
                ->dehydrated(fn ($state): bool => $state);

            return $permissions;
        }, collect())
        ->toArray();
    }

    protected static function refreshSelectAllStateViaEntities(Closure $set, Closure $get): void
    {
        $entitiesStates = collect(FilamentShield::getResources())
            ->when(Utils::isPageEntityEnabled(), fn ($entities) => $entities->merge(FilamentShield::getPages()))
            ->when(Utils::isWidgetEntityEnabled(), fn ($entities) => $entities->merge(FilamentShield::getWidgets()))
            ->when(Utils::isCustomPermissionEntityEnabled(), fn ($entities) => $entities->merge(static::getCustomEntities()))
            ->map(function ($entity) use ($get) {
                if (is_array($entity)) {
                    return (bool) $get($entity['resource']);
                }

                return (bool) $get($entity);
            });

        if ($entitiesStates->containsStrict(false) === false) {
            $set('select_all', true);
        }

        if ($entitiesStates->containsStrict(false) === true) {
            $set('select_all', false);
        }
    }

    protected static function refreshEntitiesStatesViaSelectAll(Closure $set, $state): void
    {
        collect(FilamentShield::getResources())->each(function ($entity) use ($set, $state) {
            $set($entity['resource'], $state);
            collect(Utils::getGeneralResourcePermissionPrefixes())->each(function ($permission) use ($entity, $set, $state) {
                $set($permission.'_'.$entity['resource'], $state);
            });
        });

        collect(FilamentShield::getPages())->each(function ($page) use ($set, $state) {
            if (Utils::isPageEntityEnabled()) {
                $set($page, $state);
            }
        });

        collect(FilamentShield::getWidgets())->each(function ($widget) use ($set, $state) {
            if (Utils::isWidgetEntityEnabled()) {
                $set($widget, $state);
            }
        });

        static::getCustomEntities()->each(function ($custom) use ($set, $state) {
            if (Utils::isCustomPermissionEntityEnabled()) {
                $set($custom, $state);
            }
        });
    }

    protected static function refreshResourceEntityStateAfterUpdate(Closure $set, Closure $get, string $entity): void
    {
        $permissionStates = collect(Utils::getGeneralResourcePermissionPrefixes())
            ->map(function ($permission) use ($get, $entity) {
                return (bool) $get($permission.'_'.$entity);
            });

        if ($permissionStates->containsStrict(false) === false) {
            $set($entity, true);
        }

        if ($permissionStates->containsStrict(false) === true) {
            $set($entity, false);
        }
    }

    protected static function refreshResourceEntityStateAfterHydrated(Model $record, Closure $set, string $entity): void
    {
        $permissions = $record->getPermissionsViaRoles() ?: $record->permissions;

        $entities = $permissions->pluck('name')
            ->reduce(function ($roles, $role) {
                $roles[$role] = Str::afterLast($role, '_');

                return $roles;
            }, collect())
            ->values()
            ->groupBy(function ($item) {
                return $item;
            })->map->count()
            ->reduce(function ($counts, $role, $key) {
                if ($role > 1 && $role == count(Utils::getGeneralResourcePermissionPrefixes())) {
                    $counts[$key] = true;
                } else {
                    $counts[$key] = false;
                }

                return $counts;
            }, []);

        // set entity's state if one are all permissions are true
        if (Arr::exists($entities, $entity) && Arr::get($entities, $entity)) {
            $set($entity, true);
        } else {
            $set($entity, false);
            $set('select_all', false);
        }
    }

    protected static function getPageEntityPermissionsSchema(): ?array
    {
        return collect(FilamentShield::getPages())->sortKeys()->reduce(function ($pages, $page) {
            $pages[] = Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Checkbox::make($page)
                            ->label(FilamentShield::getLocalizedPageLabel($page))
                            ->inline()
                            ->afterStateHydrated(function (Closure $set, Closure $get, $record) use ($page) {
                                if (is_null($record)) {
                                    return;
                                }

                                $set($page, $record->checkPermissionTo($page));

                                static::refreshSelectAllStateViaEntities($set, $get);
                            })
                            ->reactive()
                            ->afterStateUpdated(function (Closure $set, Closure $get, $state) {
                                if (! $state) {
                                    $set('select_all', false);
                                }

                                static::refreshSelectAllStateViaEntities($set, $get);
                            })
                            ->dehydrated(fn ($state): bool => $state),
                    ])
                    ->columns(1)
                    ->columnSpan(1);

            return $pages;
        }, []);
    }
    /**--------------------------------*
    | Page Related Logic End          |
    *----------------------------------*/

    /**--------------------------------*
    | Widget Related Logic Start       |
    *----------------------------------*/

    protected static function getWidgetEntityPermissionSchema(): ?array
    {
        return collect(FilamentShield::getWidgets())->reduce(function ($widgets, $widget) {
            $widgets[] = Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Checkbox::make($widget)
                            ->label(FilamentShield::getLocalizedWidgetLabel($widget))
                            ->inline()
                            ->afterStateHydrated(function (Closure $set, Closure $get, $record) use ($widget) {
                                if (is_null($record)) {
                                    return;
                                }

                                $set($widget, $record->checkPermissionTo($widget));

                                static::refreshSelectAllStateViaEntities($set, $get);
                            })
                            ->reactive()
                            ->afterStateUpdated(function (Closure $set, Closure $get, $state) {
                                if (! $state) {
                                    $set('select_all', false);
                                }

                                static::refreshSelectAllStateViaEntities($set, $get);
                            })
                            ->dehydrated(fn ($state): bool => $state),
                    ])
                    ->columns(1)
                    ->columnSpan(1);

            return $widgets;
        }, []);
    }
    /**--------------------------------*
    | Widget Related Logic End          |
    *----------------------------------*/

    protected static function getCustomEntities(): ?Collection
    {
        $resourcePermissions = collect();
        collect(FilamentShield::getResources())->each(function ($entity) use ($resourcePermissions) {
            collect(Utils::getGeneralResourcePermissionPrefixes())->map(function ($permission) use ($resourcePermissions, $entity) {
                $resourcePermissions->push((string) Str::of($permission.'_'.$entity['resource']));
            });
        });

        $entitiesPermissions = $resourcePermissions
            ->merge(FilamentShield::getPages())
            ->merge(FilamentShield::getWidgets())
            ->values();

        return Permission::whereNotIn('name', $entitiesPermissions)->pluck('name');
    }

    protected static function getCustomEntitiesPermisssionSchema(): ?array
    {
        return collect(static::getCustomEntities())->reduce(function ($customEntities, $customPermission) {
            $customEntities[] = Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Checkbox::make($customPermission)
                            ->label(Str::of($customPermission)->headline())
                            ->inline()
                            ->afterStateHydrated(function (Closure $set, Closure $get, $record) use ($customPermission) {
                                if (is_null($record)) {
                                    return;
                                }

                                $set($customPermission, $record->checkPermissionTo($customPermission));

                                static::refreshSelectAllStateViaEntities($set, $get);
                            })
                            ->reactive()
                            ->afterStateUpdated(function (Closure $set, Closure $get, $state) {
                                if (! $state) {
                                    $set('select_all', false);
                                }

                                static::refreshSelectAllStateViaEntities($set, $get);
                            })
                            ->dehydrated(fn ($state): bool => $state),
                    ])
                    ->columns(1)
                    ->columnSpan(1);

            return $customEntities;
        }, []);
    }
}
