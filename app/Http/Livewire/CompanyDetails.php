<?php

namespace App\Http\Livewire;

use App\Abstracts\Forms\EditFormRecord;
use App\Models\Company;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * @property ComponentContainer $form
 */
class CompanyDetails extends EditFormRecord
{
    public Company $company;

    protected function getFormModel(): Model|string|null
    {
        return $this->company;
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('General')
                ->schema([
                    Group::make()
                        ->schema([
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->nullable(),
                            TextInput::make('phone')
                                ->label('Phone')
                                ->tel()
                                ->maxLength(20),
                        ])->columns(1),
                    Group::make()
                        ->schema([
                            FileUpload::make('logo')
                                ->label('Logo')
                                ->disk('public')
                                ->directory('logos/company')
                                ->imageResizeMode('cover')
                                ->imagePreviewHeight('150')
                                ->imageCropAspectRatio('2:1')
                                ->panelAspectRatio('2:1')
                                ->reactive()
                                ->enableOpen()
                                ->preserveFilenames()
                                ->visibility('public')
                                ->image(),
                        ])->columns(1),
                ])->columns(),
            Section::make('Address')
                ->schema([
                    TextInput::make('address')
                        ->label('Address')
                        ->maxLength(100)
                        ->columnSpanFull()
                        ->nullable(),
                    TextInput::make('country')
                        ->label('Country')
                        ->nullable(),
                    TextInput::make('state')
                        ->label('Province/State')
                        ->nullable(),
                    TextInput::make('city')
                        ->label('Town/City')
                        ->maxLength(100)
                        ->nullable(),
                    TextInput::make('zip_code')
                        ->label('Postal/Zip Code')
                        ->maxLength(100)
                        ->nullable(),
                ])->columns(),
        ];
    }

    public function render(): View
    {
        return view('livewire.company-details');
    }
}
