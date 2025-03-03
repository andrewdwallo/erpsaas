<?php

namespace App\Facades;

use App\Services\ReportService;
use Illuminate\Support\Facades\Facade;

class Reporting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ReportService::class;
    }
}
