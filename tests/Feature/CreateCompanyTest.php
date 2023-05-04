<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Wallo\FilamentCompanies\Http\Livewire\CreateCompanyForm;

class CreateCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_companies_can_be_created(): void
    {
        $this->actingAs($user = User::factory()->withPersonalCompany()->create());

        Livewire::test(CreateCompanyForm::class)
                    ->set(['state' => ['name' => 'Test Company']])
                    ->call('createCompany');

        $this->assertCount(2, $user->fresh()->ownedCompanies);
        $this->assertEquals('Test Company', $user->fresh()->ownedCompanies()->latest('id')->first()->name);
    }
}
