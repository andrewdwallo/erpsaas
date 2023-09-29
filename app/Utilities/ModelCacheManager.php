<?php

namespace App\Utilities;

use Closure;
use Illuminate\Support\Facades\{Cache, Log};

class ModelCacheManager
{
    public static function cacheData(string $csv, string $cacheKey, ?Closure $transformer = null, $expiration = null): void
    {
        $expiration = $expiration ?? now()->addDays(1);

        Cache::remember($cacheKey, $expiration, static function () use ($csv, $transformer) {
            $dataCollection = collect();

            $handle = fopen($csv, 'rb');
            if (! $handle) {
                Log::error("Could not open CSV file at path: {$csv}");

                return $dataCollection;
            }

            $headers = fgetcsv($handle);
            if (! $headers) {
                Log::error("CSV file headers could not be read from path: {$csv}");
                fclose($handle);

                return $dataCollection;
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowData = array_combine($headers, $row);

                // Decode JSON 'timezones' field before storing
                if (isset($rowData['timezones'])) {
                    $rowData['timezones'] = json_decode($rowData['timezones'], true);
                }

                if ($transformer) {
                    $rowData = $transformer($rowData);
                }

                $dataCollection->push($rowData);
            }

            fclose($handle);

            return $dataCollection;
        });
    }
}
