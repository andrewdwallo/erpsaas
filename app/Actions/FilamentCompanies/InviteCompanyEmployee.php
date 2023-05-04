<?php

namespace App\Actions\FilamentCompanies;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Wallo\FilamentCompanies\Contracts\InvitesCompanyEmployees;
use Wallo\FilamentCompanies\Events\InvitingCompanyEmployee;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\FilamentCompanies\Mail\CompanyInvitation;
use Wallo\FilamentCompanies\Rules\Role;

class InviteCompanyEmployee implements InvitesCompanyEmployees
{
    /**
     * Invite a new company employee to the given company.
     *
     * @throws AuthorizationException
     */
    public function invite(User $user, Company $company, string $email, string|null $role = null): void
    {
        Gate::forUser($user)->authorize('addCompanyEmployee', $company);

        $this->validate($company, $email, $role);

        InvitingCompanyEmployee::dispatch($company, $email, $role);

        $invitation = $company->companyInvitations()->create([
            'email' => $email,
            'role' => $role,
        ]);

        Mail::to($email)->send(new CompanyInvitation($invitation));
    }

    /**
     * Validate the invite employee operation.
     */
    protected function validate(Company $company, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], $this->rules($company), [
            'email.unique' => __('filament-companies::default.errors.employee_already_invited'),
        ])->after(
            $this->ensureUserIsNotAlreadyOnCompany($company, $email)
        )->validateWithBag('addCompanyEmployee');
    }

    /**
     * Get the validation rules for inviting a company employee.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function rules(Company $company): array
    {
        return array_filter([
            'email' => [
                'required', 'email',
                Rule::unique('company_invitations')->where(static function (Builder $query) use ($company) {
                    $query->where('company_id', $company->id);
                }),
            ],
            'role' => FilamentCompanies::hasRoles()
                            ? ['required', 'string', new Role]
                            : null,
        ]);
    }

    /**
     * Ensure that the employee is not already on the company.
     */
    protected function ensureUserIsNotAlreadyOnCompany(Company $company, string $email): Closure
    {
        return static function ($validator) use ($company, $email) {
            $validator->errors()->addIf(
                $company->hasUserWithEmail($email),
                'email',
                __('filament-companies::default.errors.employee_already_belongs_to_company')
            );
        };
    }
}
