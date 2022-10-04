<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Models\Bank;
use App\Filament\Resources\AssetResource;
use Filament\Pages\Actions;
use Filament\Forms;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Filament\Resources\Pages\CreateRecord;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

}
