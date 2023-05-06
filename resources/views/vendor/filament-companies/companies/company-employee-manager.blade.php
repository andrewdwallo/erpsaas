<div>
    @if (Gate::check('addCompanyEmployee', $company))
        <x-filament-companies::section-border />

        <!-- Add Company Employee -->
        <x-filament-companies::grid-section>
            <x-slot name="title">
                {{ __('filament-companies::default.grid_section_titles.add_company_employee') }}
            </x-slot>

            <x-slot name="description">
                {{ __('filament-companies::default.grid_section_descriptions.add_company_employee') }}
            </x-slot>

            <form wire:submit.prevent="addCompanyEmployee" class="col-span-2 sm:col-span-1 mt-5 md:mt-0">
                <x-filament::card>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('filament-companies::default.subheadings.companies.company_employee_manager') }}
                    </p>

                    <!-- Employee Email -->
                    <x-forms::field-wrapper id="email" statePath="email" required label="{{ __('filament-companies::default.fields.email') }}">
                        <x-filament-companies::input id="email" type="email" wire:model.defer="addCompanyEmployeeForm.email" />
                    </x-forms::field-wrapper>

                    <!-- Role -->
                    @if (count($this->roles) > 0)
                        <x-forms::field-wrapper id="role" statePath="role" required label="{{ __('filament-companies::default.labels.role') }}">
                            <div x-data="{ role: @entangle('addCompanyEmployeeForm.role').defer }" class="relative z-0 mt-1 cursor-pointer rounded-lg border border-gray-200 dark:border-gray-700">
                                @foreach ($this->roles as $index => $role)
                                    <button type="button"
                                            @click="role = '{{ $role->key }}'"
                                            @class([
                                                'relative inline-flex w-full rounded-lg px-4 py-3 transition focus:z-10 focus:outline-none focus:ring-2 focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-600 dark:focus:ring-primary-600',
                                                'border-t border-gray-200 dark:border-gray-700 rounded-t-none' => ($index > 0),
                                                'rounded-b-none' => (! $loop->last),
                                            ])
                                    >
                                        <div :class="role === '{{ $role->key }}' || 'opacity-50'">
                                            <!-- Role Name -->
                                            <div class="flex items-center">
                                                <div class="text-sm text-gray-600 dark:text-gray-400" :class="{'font-semibold': role === '{{ $role->key }}'}">
                                                    {{ $role->name }}
                                                </div>

                                                <svg class="text-primary-500 ml-2 h-5 w-5" x-cloak :class="{ 'hidden': role !== '{{ $role->key }}' }"
                                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                                </svg>
                                            </div>

                                            <!-- Role Description -->
                                            <div class="mt-2 text-left text-sm text-gray-600 dark:text-gray-400">
                                                {{ $role->description }}
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </x-forms::field-wrapper>
                    @endif

                    <x-slot name="footer">
                        <div class="text-left">
                            <x-filament::button type="submit">
                                {{ __('filament-companies::default.buttons.add') }}
                            </x-filament::button>
                        </div>
                    </x-slot>
                </x-filament::card>
            </form>
        </x-filament-companies::grid-section>
    @endif

    @if ($company->companyInvitations->isNotEmpty() && Gate::check('addCompanyEmployee', $company))
        <x-filament-companies::section-border />

        <!-- Pending Employee Invitations -->
        <x-filament-companies::grid-section class="mt-4">
            <x-slot name="title">
                {{ __('filament-companies::default.action_section_titles.pending_company_invitations') }}
            </x-slot>

            <x-slot name="description">
                {{ __('filament-companies::default.action_section_descriptions.pending_company_invitations') }}
            </x-slot>

            <div class="overflow-x-auto space-y-2 bg-white rounded-xl shadow dark:border-gray-600 dark:bg-gray-800 col-span-2 mt-5 sm:col-span-1 md:col-start-2 md:mt-0">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr>
                        <th colspan="3" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('filament-companies::default.fields.email') }}
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($company->companyInvitations as $invitation)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        {{ $invitation->email }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-right">
                                    <!-- Manage Company Employee Role -->
                                    @if (Gate::check('removeCompanyEmployee', $company))
                                        <x-filament::button size="sm" color="danger" outlined="true" wire:click="cancelCompanyInvitation({{ $invitation->id }})">
                                            {{ __('filament-companies::default.buttons.cancel') }}
                                        </x-filament::button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament-companies::grid-section>
    @endif

    @if ($company->users->isNotEmpty())
        <x-filament-companies::section-border />

        <!-- Manage Company Employees -->
        <x-filament-companies::grid-section class="mt-4">
            <x-slot name="title">
                {{ __('filament-companies::default.action_section_titles.company_employees') }}
            </x-slot>

            <x-slot name="description">
                {{ __('filament-companies::default.action_section_descriptions.company_employees') }}
            </x-slot>

            <!-- Company Employee List -->
            <div class="overflow-x-auto space-y-2 bg-white rounded-xl shadow dark:border-gray-600 dark:bg-gray-800 col-span-2 mt-5 sm:col-span-1 md:col-start-2 md:mt-0">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-white dark:bg-gray-800">
                    <tr>
                        <th scope="col" colspan="3" class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('filament-companies::default.fields.name') }}
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($company->users->sortBy('name') as $user)
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-left whitespace-nowrap">
                                <div class="flex items-center text-sm">
                                    <div class="flex-shrink-0">
                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900 dark:text-gray-200">{{ $user->name }}</div>
                                        <div class="text-gray-600 dark:text-gray-400 hidden sm:block">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td colspan="1" class="px-6 py-4 whitespace-nowrap">
                                <div class="space-x-2 text-right">
                                    <!-- Manage Company Employee Role -->
                                    @if (Gate::check('addCompanyEmployee', $company) && Wallo\FilamentCompanies\FilamentCompanies::hasRoles())
                                        <x-filament::button size="sm" outlined="true" :icon="(Wallo\FilamentCompanies\FilamentCompanies::findRole($user->employeeship->role)->key == 'admin') ? 'heroicon-o-shield-check' : 'heroicon-o-pencil'" :color="(Wallo\FilamentCompanies\FilamentCompanies::findRole($user->employeeship->role)->key == 'admin') ? 'primary' : 'warning'" wire:click="manageRole('{{ $user->id }}')">
                                            {{ Wallo\FilamentCompanies\FilamentCompanies::findRole($user->employeeship->role)->name }}
                                        </x-filament::button>
                                    @elseif (Wallo\FilamentCompanies\FilamentCompanies::hasRoles())
                                        <x-filament::button size="sm" disabled="true" outlined="true" :icon="(Wallo\FilamentCompanies\FilamentCompanies::findRole($user->employeeship->role)->key == 'admin') ? 'heroicon-o-shield-check' : 'heroicon-o-pencil'" color="secondary">
                                            {{ Wallo\FilamentCompanies\FilamentCompanies::findRole($user->employeeship->role)->name }}
                                        </x-filament::button>
                                    @endif

                                    <!-- Leave Company -->
                                    @if ($this->user->id === $user->id)
                                        <x-filament::button size="sm" color="danger" wire:click="$toggle('confirmingLeavingCompany')">
                                            {{ __('filament-companies::default.buttons.leave') }}
                                        </x-filament::button>

                                        <!-- Remove Company Employee -->
                                    @elseif (Gate::check('removeCompanyEmployee', $company))
                                        <x-filament::button size="sm" color="danger" wire:click="confirmCompanyEmployeeRemoval('{{ $user->id }}')">
                                            {{ __('filament-companies::default.buttons.remove') }}
                                        </x-filament::button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament-companies::grid-section>
    @endif

    <!-- Role Management Modal -->
    <x-filament-companies::dialog-modal wire:model="currentlyManagingRole">
        <x-slot name="title">
            {{ __('filament-companies::default.modal_titles.manage_role') }}
        </x-slot>

        <x-slot name="content">
            <div x-data="{ role: @entangle('currentRole').defer }"
                 class="relative z-0 mt-1 cursor-pointer rounded-lg border border-gray-200 dark:border-gray-700">
                @foreach ($this->roles as $index => $role)
                    <button type="button"
                            @click="role = '{{ $role->key }}'"
                            @class([
                                'relative inline-flex w-full rounded-lg px-4 py-3 transition focus:z-10 focus:outline-none focus:ring-2 focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-600 dark:focus:ring-primary-600',
                                'border-t border-gray-200 dark:border-gray-700 rounded-t-none' => ($index > 0),
                                'rounded-b-none' => (! $loop->last),
                            ])
                    >
                        <div :class="role === '{{ $role->key }}' || 'opacity-50'">
                            <!-- Role Name -->
                            <div class="flex items-center">
                                <div class="text-sm text-gray-600 dark:text-gray-100" :class="role === '{{ $role->key }}' ? 'font-semibold' : ''">
                                    {{ $role->name }}
                                </div>

                                <svg class="text-primary-500 ml-2 h-5 w-5" x-cloak :class="{ 'hidden': role !== '{{ $role->key }}' }"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                </svg>
                            </div>

                            <!-- Role Description -->
                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                {{ $role->description }}
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-filament::button color="secondary" wire:click="stopManagingRole" wire:loading.attr="disabled">
                {{ __('filament-companies::default.buttons.cancel') }}
            </x-filament::button>

            <x-filament::button wire:click="updateRole" wire:loading.attr="disabled">
                {{ __('filament-companies::default.buttons.save') }}
            </x-filament::button>
        </x-slot>
    </x-filament-companies::dialog-modal>

    <!-- Leave Company Confirmation Modal -->
    <x-filament-companies::dialog-modal wire:model="confirmingLeavingCompany">
        <x-slot name="title">
            {{ __('filament-companies::default.modal_titles.leave_company') }}
        </x-slot>

        <x-slot name="content">
            {{ __('filament-companies::default.modal_descriptions.leave_company') }}
        </x-slot>

        <x-slot name="footer">
            <x-filament::button color="secondary" wire:click="$toggle('confirmingLeavingCompany')" wire:loading.attr="disabled">
                {{ __('filament-companies::default.buttons.cancel') }}
            </x-filament::button>

            <x-filament::button color="danger" wire:click="leaveCompany" wire:loading.attr="disabled">
                {{ __('filament-companies::default.buttons.leave') }}
            </x-filament::button>
        </x-slot>
    </x-filament-companies::dialog-modal>

    <!-- Remove Company Employee Confirmation Modal -->
    <x-filament-companies::dialog-modal wire:model="confirmingCompanyEmployeeRemoval">
        <x-slot name="title">
            {{ __('filament-companies::default.modal_titles.remove_company_employee') }}
        </x-slot>

        <x-slot name="content">
            {{ __('filament-companies::default.modal_descriptions.remove_company_employee') }}
        </x-slot>

        <x-slot name="footer">
            <x-filament::button color="secondary" wire:click="$toggle('confirmingCompanyEmployeeRemoval')" wire:loading.attr="disabled">
                {{ __('filament-companies::default.buttons.cancel') }}
            </x-filament::button>

            <x-filament::button color="danger" wire:click="removeCompanyEmployee" wire:loading.attr="disabled">
                {{ __('filament-companies::default.buttons.remove') }}
            </x-filament::button>
        </x-slot>
    </x-filament-companies::dialog-modal>
</div>
