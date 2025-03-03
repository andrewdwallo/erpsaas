<?php

namespace App\Filament\Components;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Panel\Concerns\HasNavigation;
use Filament\View\PanelsRenderHook;

class PanelShiftDropdown implements Plugin
{
    use HasNavigation;

    protected string $view = 'components.panel-shift-dropdown';

    protected string $renderHook = PanelsRenderHook::USER_MENU_BEFORE;

    protected bool $hasDisplayAndAccessibility = true;

    protected bool $hasCompanySettings = true;

    protected bool $hasLogoutItem = true;

    protected int $groupIndex = 0;

    public function displayAndAccessibility(bool $condition = true): static
    {
        $this->hasDisplayAndAccessibility = $condition;

        return $this;
    }

    public function hasDisplayAndAccessibility(): bool
    {
        return $this->hasDisplayAndAccessibility;
    }

    public function companySettings(bool $condition = true): static
    {
        $this->hasCompanySettings = $condition;

        return $this;
    }

    public function hasCompanySettings(): bool
    {
        return $this->hasCompanySettings;
    }

    public function logoutItem(bool $condition = true): static
    {
        $this->hasLogoutItem = $condition;

        return $this;
    }

    public function hasLogoutItem(): bool
    {
        return $this->hasLogoutItem;
    }

    public function getNavigation(): array
    {
        if ($this->hasNavigationBuilder()) {
            return $this->buildNavigation();
        }

        return [];
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'panel-shift-dropdown';
    }

    public function register(Panel $panel): void
    {
        if ($this->hasNavigation()) {
            $panel->renderHook($this->getRenderHook(), function () {
                return view($this->view, [
                    'component' => $this,
                ]);
            });
        }
    }

    public function getNavigationAsHierarchyArray(): array
    {
        $navigation = $this->getNavigation();

        $panels = $this->initializePanels();

        foreach ($navigation as $item) {
            $this->processItem($item, $panels);
        }

        $this->addCompanySettingsItems($panels);
        $this->addAccessibilityItems($panels);

        return $panels;
    }

    protected function initializePanels(): array
    {
        return [
            'main' => [
                'panelId' => 'main',
                'label' => 'Main',
                'items' => [],
                'renderItems' => true,
            ],
        ];
    }

    protected function processItem($item, array &$panels, $parentId = 'main'): void
    {
        if (method_exists($item, 'getItems') && ! empty($item->getLabel())) {
            $this->processGroupItem($item, $panels, $parentId);
        } elseif (method_exists($item, 'getItems') && empty($item->getLabel())) {
            foreach ($item->getItems() as $groupItem) {
                $this->processItem($groupItem, $panels, $parentId);
            }
        } elseif (method_exists($item, 'getChildItems') && ! empty($item->getChildItems())) {
            $this->processNavigationItem($item, $panels, $parentId);
        } else {
            $this->addStandaloneItem($item, $panels, $parentId);
        }
    }

    protected function processGroupItem($item, array &$panels, $parentId): void
    {
        $uniqueId = 'group-' . ++$this->groupIndex;

        $panels[$uniqueId] = $this->createPanel($uniqueId, $item);

        $panels[$parentId]['items'][] = $this->createPanelReference($uniqueId, $item);

        foreach ($item->getItems() as $groupItem) {
            $this->processItem($groupItem, $panels, $uniqueId);
        }
    }

    protected function processNavigationItem($item, array &$panels, $parentId): void
    {
        $uniqueId = 'group-' . ++$this->groupIndex;

        $panels[$uniqueId] = $this->createPanel($uniqueId, $item);

        $panels[$parentId]['items'][] = $this->createPanelReference($uniqueId, $item);

        foreach ($item->getChildItems() as $childItem) {
            $this->processItem($childItem, $panels, $uniqueId);
        }
    }

    protected function addStandaloneItem($item, array &$panels, $parentId): void
    {
        $panels[$parentId]['items'][] = [
            'url' => $item->getUrl(),
            'label' => $item->getLabel(),
            'icon' => $item->getIcon(),
        ];
    }

    protected function addAccessibilityItems(array &$panels): void
    {
        if ($this->hasDisplayAndAccessibility()) {
            $displayAndAccessibilityId = 'display-and-accessibility';
            $panels['main']['items'][] = [
                'panelId' => $displayAndAccessibilityId,
                'label' => 'Display & Accessibility',
                'icon' => 'heroicon-s-moon',
            ];

            $panels[$displayAndAccessibilityId] = [
                'panelId' => $displayAndAccessibilityId,
                'label' => 'Display & Accessibility',
                'items' => [],
                'renderItems' => false,
            ];
        }
    }

    protected function addCompanySettingsItems(array &$panels): void
    {
        if ($this->hasCompanySettings()) {
            $companySettingsId = 'company-settings';
            $panels['main']['items'][] = [
                'panelId' => $companySettingsId,
                'label' => 'Company Settings',
                'icon' => 'heroicon-s-building-office-2',
            ];

            $panels[$companySettingsId] = [
                'panelId' => $companySettingsId,
                'label' => 'Company Settings',
                'items' => [],
                'renderItems' => false,
            ];

            $switchCompanyPanelId = 'company-switcher';
            $panels[$companySettingsId]['items'][] = [
                'panelId' => $switchCompanyPanelId,
                'label' => 'Switch Company',
                'icon' => '',
            ];

            $panels[$switchCompanyPanelId] = [
                'panelId' => $switchCompanyPanelId,
                'label' => 'Switch Company',
                'items' => [],
                'renderItems' => false,
            ];
        }
    }

    protected function createPanel($uniqueId, $item, $renderItems = true): array
    {
        return [
            'panelId' => $uniqueId,
            'label' => $item->getLabel(),
            'icon' => $item->getIcon(),
            'items' => [],
            'renderItems' => $renderItems,
        ];
    }

    protected function createPanelReference($uniqueId, $item): array
    {
        return [
            'panelId' => $uniqueId,
            'label' => $item->getLabel(),
            'icon' => $item->getIcon(),
        ];
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }

    public function renderHook(string $hook): static
    {
        $this->renderHook = $hook;

        return $this;
    }

    public function getRenderHook(): string
    {
        return $this->renderHook;
    }
}
