<?php

namespace App\Actions\FilamentCompanies;

use Illuminate\Http\Response;
use Laravel\Socialite\Two\InvalidStateException;
use Wallo\FilamentCompanies\Contracts\HandlesInvalidState;

class HandleInvalidState implements HandlesInvalidState
{
    /**
     * Handle an invalid state exception from a Socialite provider.
     */
    public function handle(InvalidStateException $exception, callable|null $callback = null): Response
    {
        if ($callback) {
            return $callback($exception);
        }

        throw $exception;
    }
}
