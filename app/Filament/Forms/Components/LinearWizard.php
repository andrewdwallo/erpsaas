<?php

namespace App\Filament\Forms\Components;

use Filament\Schemas\Components\Wizard;

class LinearWizard extends Wizard
{
    protected string $view = 'filament.forms.components.linear-wizard';

    protected bool $hideStepTabs = false;

    protected ?string $currentStepDescription = null;

    /**
     * Hide the step tabs at the top of the wizard
     */
    public function hideStepTabs(bool $condition = true): static
    {
        $this->hideStepTabs = $condition;

        return $this;
    }

    /**
     * Add a description for the current step
     */
    public function currentStepDescription(?string $description): static
    {
        $this->currentStepDescription = $description;

        return $this;
    }

    /**
     * Get whether the step tabs should be hidden
     */
    public function areStepTabsHidden(): bool
    {
        return $this->hideStepTabs;
    }

    /**
     * Get the description for the current step
     */
    public function getCurrentStepDescription(): ?string
    {
        return $this->currentStepDescription;
    }
}
