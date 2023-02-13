<?php

namespace App\Components;

use Filament\Forms\Components\TextInput;

class PasswordGenerator extends TextInput
{
    protected int $passwordLength = 12;

    protected bool $hasNumbers = true;

    protected bool $hasSymbols = true;

    protected string $view = 'components.password-generator';

    public function passwordLength(int $passwordLength): static
    {
        $this->passwordLength = $passwordLength;

        return $this;
    }

    public function hasNumbers(bool $hasNumbers): static
    {
        $this->hasNumbers = $hasNumbers;

        return $this;
    }

    public function hasSymbols(bool $hasSymbols): static
    {
        $this->hasSymbols = $hasSymbols;

        return $this;
    }

    public function getPasswordLength(): int
    {
        return $this->passwordLength;
    }

    public function getChars(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*_-+=';

        if ($this->hasNumbers) {
            $chars .= $numbers;
        }

        if ($this->hasSymbols) {
            $chars .= $symbols;
        }

        return $chars;
    }
}
