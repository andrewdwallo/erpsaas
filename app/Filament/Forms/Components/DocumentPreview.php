<?php

namespace App\Filament\Forms\Components;

use App\Enums\Setting\Template;
use Closure;
use Filament\Schemas\Components\Grid;

class DocumentPreview extends Grid
{
    protected string $view = 'filament.forms.components.document-preview';

    protected bool | Closure $isPreview = false;

    protected Template | Closure $template = Template::Default;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function preview(bool | Closure $condition = true): static
    {
        $this->isPreview = $condition;

        return $this;
    }

    public function template(Template | Closure $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function isPreview(): bool
    {
        return (bool) $this->evaluate($this->isPreview);
    }

    public function getTemplate(): Template
    {
        return $this->evaluate($this->template);
    }
}
