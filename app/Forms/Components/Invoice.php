<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class Invoice extends Field
{
    protected string $view = 'forms.components.invoice';

    protected ?string $companyName = null;

    protected ?string $companyAddress = null;

    protected ?string $companyCity = null;

    protected ?string $companyState = null;

    protected ?string $companyZip = null;

    protected ?string $companyCountry = null;

    protected ?string $documentNumberPrefix = null;

    protected ?string $documentNumberDigits = null;
}
