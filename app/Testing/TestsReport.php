<?php

namespace App\Testing;

use App\Contracts\ExportableReport;
use Closure;
use Livewire\Features\SupportTesting\Testable;

/**
 * @mixin Testable
 */
class TestsReport
{
    /**
     * Asserts the report table data.
     */
    public function assertReportTableData(): Closure
    {
        return function (): static {
            /** @var ExportableReport $report */
            $report = $this->get('report');

            // Assert headers
            $this->assertSeeTextInOrder($report->getHeaders());

            // Assert categories, headers, data, and summaries
            $categories = $report->getCategories();
            foreach ($categories as $category) {
                $header = $category->header;
                $data = $category->data;
                $summary = $category->summary;

                // Assert header
                $this->assertSeeTextInOrder($header);

                // Assert data rows
                foreach ($data as $row) {
                    $flatRow = [];

                    foreach ($row as $value) {
                        if (is_array($value)) {
                            $flatRow[] = $value['name'];
                        } else {
                            $flatRow[] = $value;
                        }
                    }

                    $this->assertSeeTextInOrder($flatRow);
                }

                // Assert summary
                $this->assertSeeTextInOrder($summary);
            }

            // Assert overall totals
            $this->assertSeeTextInOrder($report->getOverallTotals());

            return $this;
        };
    }
}
