<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConvertTimezones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:timezones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts countries csv to generate a timezones csv file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourcePath = resource_path('data/countries.csv');
        $destinationPath = resource_path('data/timezones.csv');

        $source = fopen($sourcePath, 'rb');
        $destination = fopen($destinationPath, 'wb');

        fputcsv($destination, ['id', 'country_id', 'country_code', 'name', 'gmt_offset', 'gmt_offset_name', 'abbreviation', 'tz_name']);

        $idCounter = 1;

        $headers = fgetcsv($source);

        while (($row = fgetcsv($source)) !== false) {
            $rowAssoc = array_combine($headers, $row);
            $countryId = $rowAssoc['id'];
            $countryCode = $rowAssoc['iso_code_2'];
            $timezonesJson = $rowAssoc['timezones'];

            $timezonesArray = json_decode($timezonesJson, true);

            foreach ($timezonesArray as $timezone) {
                $newRow = [
                    $idCounter++,
                    $countryId,
                    $countryCode,
                    $timezone['zoneName'],
                    $timezone['gmtOffset'],
                    $timezone['gmtOffsetName'],
                    $timezone['abbreviation'],
                    $timezone['tzName'],
                ];

                fputcsv($destination, $newRow);
            }
        }

        fclose($source);
        fclose($destination);

        $this->info('Timezones csv file generated successfully.');

        return 0;
    }
}
