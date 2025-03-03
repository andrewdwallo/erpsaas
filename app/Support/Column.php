<?php

namespace App\Support;

use Filament\Support\Components\Component;
use Filament\Support\Concerns\HasAlignment;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Concerns\CanBeHidden;
use Filament\Tables\Columns\Concerns\CanBeToggled;
use Filament\Tables\Columns\Concerns\HasLabel;
use Filament\Tables\Columns\Concerns\HasName;

class Column extends Component
{
    use CanBeHidden;
    use CanBeToggled;
    use HasAlignment;
    use HasLabel;
    use HasName;

    protected bool $isDate = false;

    final public function __construct(string $name)
    {
        $this->name($name);
    }

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }

    public function getAlignmentClass(): string
    {
        return match ($this->getAlignment()) {
            Alignment::Center, Alignment::Justify, Alignment::Between => 'text-center',
            Alignment::Left, Alignment::Start => 'text-left',
            Alignment::Right, Alignment::End => 'text-right',
            default => '',
        };
    }

    public function markAsDate(): static
    {
        $this->isDate = true;

        return $this;
    }

    public function isDate(): bool
    {
        return $this->isDate;
    }
}
