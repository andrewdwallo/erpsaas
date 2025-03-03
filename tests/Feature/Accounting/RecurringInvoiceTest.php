<?php

use App\Enums\Accounting\EndType;
use App\Enums\Accounting\Frequency;
use App\Enums\Accounting\IntervalType;
use App\Models\Accounting\RecurringInvoice;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->withOfferings();
});

test('recurring invoice properly handles months with fewer days for monthly frequency', function () {
    // Start from January 31st
    Carbon::setTestNow('2024-01-31');

    RecurringInvoice::unsetEventDispatcher();

    // Create a recurring invoice set for the 31st of each month
    $recurringInvoice = RecurringInvoice::factory()
        ->withLineItems()
        ->withSchedule(
            frequency: Frequency::Monthly,
            startDate: Carbon::now(),
        )
        ->approved()
        ->create();

    // First invoice should be the start date
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-01-31');

    // Now set last_date to simulate first invoice being generated
    $recurringInvoice->update(['last_date' => '2024-01-31']);
    $recurringInvoice->refresh();
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-02-29');

    // Update last_date to Feb 29 and check next date (should be March 31)
    $recurringInvoice->update(['last_date' => '2024-02-29']);
    $recurringInvoice->refresh();
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-03-31');

    // Update last_date to March 31 and check next date (should be April 30)
    $recurringInvoice->update(['last_date' => '2024-03-31']);
    $recurringInvoice->refresh();
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-04-30');

    // Update last_date to April 30 and check next date (should be May 31)
    $recurringInvoice->update(['last_date' => '2024-04-30']);
    $recurringInvoice->refresh();
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-05-31');
});

test('recurring invoice properly handles months with fewer days for yearly frequency', function () {
    // Start from January 31st
    Carbon::setTestNow('2024-02-29');

    RecurringInvoice::unsetEventDispatcher();

    // Create a recurring invoice set for the 31st of each month
    $recurringInvoice = RecurringInvoice::factory()
        ->withLineItems()
        ->withSchedule(
            frequency: Frequency::Yearly,
            startDate: Carbon::now(),
        )
        ->approved()
        ->create();

    // First invoice should be the start date
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-02-29');

    // Next date should be Feb 28, 2025 (non-leap year)
    $recurringInvoice->update(['last_date' => '2024-02-29']);
    $recurringInvoice->refresh();
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2025-02-28');

    // Next date should be Feb 29, 2026 (leap year)
    $recurringInvoice->update(['last_date' => '2025-02-28']);
    $recurringInvoice->refresh();
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2026-02-28');
});

test('recurring invoice properly handles weekly frequency and custom weekly intervals', function () {
    Carbon::setTestNow('2024-01-31'); // Wednesday

    RecurringInvoice::unsetEventDispatcher();

    // Test regular weekly frequency
    $recurringInvoice = RecurringInvoice::factory()
        ->withLineItems()
        ->withSchedule(
            frequency: Frequency::Weekly,
            startDate: Carbon::now(),
        )
        ->approved()
        ->create();

    // First invoice should be the start date
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-01-31');

    // Next date should be that Friday
    $recurringInvoice->update(['last_date' => '2024-01-31']);
    $recurringInvoice->refresh();
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-02-07');

    // Test custom weekly frequency (every 2 weeks)
    $recurringInvoice = RecurringInvoice::factory()
        ->withLineItems()
        ->withCustomSchedule(
            startDate: Carbon::now(), // Wednesday
            endType: EndType::Never,
            intervalType: IntervalType::Week,
            intervalValue: 2,
        )
        ->approved()
        ->create();

    // First invoice should be the start date
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-01-31');

    // Next date should be two weeks from start, on Friday
    $recurringInvoice->update(['last_date' => '2024-01-31']);
    $recurringInvoice->refresh();
    expect($recurringInvoice->calculateNextDate())
        ->toBeInstanceOf(Carbon::class)
        ->toDateString()->toBe('2024-02-14');
});

test('recurring invoice generates correct sequence of invoices across different month lengths', function () {
    Carbon::setTestNow('2024-01-31');

    $recurringInvoice = RecurringInvoice::factory()
        ->withLineItems()
        ->withSchedule(
            frequency: Frequency::Monthly,
            startDate: Carbon::now(),
        )
        ->approved()
        ->create();

    // Generate first invoice
    $recurringInvoice->generateDueInvoices();

    $invoices = $recurringInvoice->invoices()
        ->orderBy('date')
        ->get();

    expect($invoices)->toHaveCount(1)
        ->and($invoices->pluck('date')->map->toDateString()->toArray())->toBe([
            '2024-01-31',
        ]);

    // Move time forward to February (leap year)
    Carbon::setTestNow('2024-02-29');
    $recurringInvoice->generateDueInvoices();

    $invoices = $recurringInvoice->invoices()
        ->orderBy('date')
        ->get();

    expect($invoices)->toHaveCount(2)
        ->and($invoices->pluck('date')->map->toDateString()->toArray())->toBe([
            '2024-01-31',
            '2024-02-29',
        ]);

    // Move time forward to March
    Carbon::setTestNow('2024-03-31');
    $recurringInvoice->generateDueInvoices();

    $invoices = $recurringInvoice->invoices()
        ->orderBy('date')
        ->get();

    expect($invoices)->toHaveCount(3)
        ->and($invoices->pluck('date')->map->toDateString()->toArray())->toBe([
            '2024-01-31',
            '2024-02-29',
            '2024-03-31',
        ]);
});
