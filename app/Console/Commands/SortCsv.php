<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SortCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sort:csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sort the cities CSV file by country code and state code';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $inputPath = resource_path('data/cities.csv');
        $outputPath = resource_path('data/cities-sorted.csv');

        $fileInput = fopen($inputPath, 'rb');
        $fileOutput = fopen($outputPath, 'wb');

        // Write header to output file
        if (($header = fgetcsv($fileInput, 1000, ',')) !== false) {
            fputcsv($fileOutput, $header);
        }

        $buffer = [];
        while (($row = fgetcsv($fileInput, 1000, ',')) !== false) {
            $buffer[] = array_combine($header, $row);

            // When buffer reaches some size, sort and write to file
            if (count($buffer) >= 10000) {  // Adjust this number based on your available memory
                $this->sortAndWriteBuffer($buffer, $fileOutput);
                $buffer = [];
            }
        }

        // Sort and write any remaining rows
        $this->sortAndWriteBuffer($buffer, $fileOutput);

        fclose($fileInput);
        fclose($fileOutput);
    }

    protected function sortAndWriteBuffer(array $buffer, $fileOutput): void
    {
        usort($buffer, static function ($a, $b) {
            if ($a['country_code'] === $b['country_code']) {
                return (int) $a['state_id'] - (int) $b['state_id'];
            }

            return strcmp($a['country_code'], $b['country_code']);
        });

        foreach ($buffer as $row) {
            fputcsv($fileOutput, $row);
        }
    }
}
