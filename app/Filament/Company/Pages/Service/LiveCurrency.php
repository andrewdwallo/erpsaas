<?php

namespace App\Filament\Company\Pages\Service;

use App\Facades\Forex;
use App\Models\Service\CurrencyList;
use App\Models\Setting\Currency;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class LiveCurrency extends Page
{
    protected static ?string $title = 'Live Currency';

    protected static ?string $slug = 'services/live-currency';

    protected static string $view = 'filament.company.pages.service.live-currency';

    #[Url]
    public ?string $activeTab = null;

    public function getTitle(): string | Htmlable
    {
        return translate(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return translate(static::$title);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Forex::isEnabled();
    }

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
        abort_unless(Forex::isEnabled(), 403);
    }

    protected function loadDefaultActiveTab(): void
    {
        if (filled($this->activeTab)) {
            return;
        }

        $this->activeTab = $this->getDefaultActiveTab();
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'currency-list';
    }

    public function getViewData(): array
    {
        return [
            'currencyListQuery' => CurrencyList::query()->count(),
            'companyCurrenciesQuery' => Currency::query()->count(),
        ];
    }
}
