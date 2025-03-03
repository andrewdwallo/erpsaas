<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DocumentHeaderSection extends Section
{
    protected string | Closure | null $defaultHeader = null;

    protected string | Closure | null $defaultSubheader = null;

    public function defaultHeader(string | Closure | null $header): static
    {
        $this->defaultHeader = $header;

        return $this;
    }

    public function defaultSubheader(string | Closure | null $subheader): static
    {
        $this->defaultSubheader = $subheader;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->collapsible();
        $this->collapsed();

        $company = Auth::user()->currentCompany;

        $this->schema([
            Split::make([
                Group::make([
                    FileUpload::make('logo')
                        ->openable()
                        ->maxSize(1024)
                        ->localizeLabel()
                        ->visibility('public')
                        ->disk('public')
                        ->directory('logos/document')
                        ->imageResizeMode('contain')
                        ->imageCropAspectRatio('3:2')
                        ->panelAspectRatio('3:2')
                        ->maxWidth(MaxWidth::ExtraSmall)
                        ->panelLayout('integrated')
                        ->removeUploadedFileButtonPosition('center bottom')
                        ->uploadButtonPosition('center bottom')
                        ->uploadProgressIndicatorPosition('center bottom')
                        ->getUploadedFileNameForStorageUsing(
                            static fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                ->prepend(Auth::user()->currentCompany->id . '_'),
                        )
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/gif']),
                ]),
                Group::make([
                    TextInput::make('header')
                        ->default(fn () => $this->getDefaultHeader()),
                    TextInput::make('subheader')
                        ->default(fn () => $this->getDefaultSubheader()),
                    View::make('filament.forms.components.company-info')
                        ->viewData([
                            'company_name' => $company->name,
                            'company_address' => $company->profile->address,
                            'company_city' => $company->profile->city?->name,
                            'company_state' => $company->profile->state?->name,
                            'company_zip' => $company->profile->zip_code,
                            'company_country' => $company->profile->state?->country->name,
                        ]),
                ])->grow(true),
            ])->from('md'),
        ]);
    }

    public function getDefaultHeader(): ?string
    {
        return $this->evaluate($this->defaultHeader);
    }

    public function getDefaultSubheader(): ?string
    {
        return $this->evaluate($this->defaultSubheader);
    }
}
