<?php

namespace App\Livewire\Company\Service\ConnectedAccount;

use App\Events\PlaidSuccess;
use App\Events\StartTransactionImport;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Banking\Institution;
use App\Models\User;
use App\Services\PlaidService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

class ListInstitutions extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected PlaidService $plaidService;

    public User $user;

    public string $modalWidth;

    public function boot(PlaidService $plaidService): void
    {
        $this->plaidService = $plaidService;
    }

    public function mount(): void
    {
        $this->user = Auth::user();
    }

    #[Computed]
    public function connectedInstitutions(): Collection | array
    {
        return Institution::withWhereHas('connectedBankAccounts')
            ->get();
    }

    public function startImportingTransactions(): Action
    {
        return Action::make('startImportingTransactions')
            ->link()
            ->icon('heroicon-o-cloud-arrow-down')
            ->label('Start importing transactions')
            ->modalWidth(fn () => $this->modalWidth)
            ->modalFooterActionsAlignment(fn () => $this->modalWidth === 'screen' ? Alignment::Center : Alignment::Start)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->record(fn (array $arguments) => ConnectedBankAccount::find($arguments['connectedBankAccount']))
            ->form([
                Placeholder::make('import_from')
                    ->label('Import transactions from')
                    ->content(static fn (ConnectedBankAccount $connectedBankAccount): View => view(
                        'components.actions.transaction-import-modal',
                        compact('connectedBankAccount')
                    )),
                Placeholder::make('info')
                    ->hiddenLabel()
                    ->visible(static fn (ConnectedBankAccount $connectedBankAccount) => $connectedBankAccount->bank_account_id === null)
                    ->content(static fn (ConnectedBankAccount $connectedBankAccount) => 'If ' . $connectedBankAccount->name . ' already has transactions for an existing account, select the account to import transactions into.'),
                Select::make('bank_account_id')
                    ->label('Select account')
                    ->visible(static fn (ConnectedBankAccount $connectedBankAccount) => $connectedBankAccount->bank_account_id === null)
                    ->options(fn (ConnectedBankAccount $connectedBankAccount) => $this->getBankAccountOptions($connectedBankAccount))
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Start date')
                    ->required()
                    ->placeholder('Select a start date for importing transactions.'),
            ])
            ->action(function (array $data, ConnectedBankAccount $connectedBankAccount) {
                $selectedBankAccountId = $data['bank_account_id'] ?? $connectedBankAccount->bank_account_id;
                $startDate = $data['start_date'];
                $company = $this->user->currentCompany;

                StartTransactionImport::dispatch($company, $connectedBankAccount, $selectedBankAccountId, $startDate);

                unset($this->connectedInstitutions);
            });
    }

    public function getBankAccountOptions(ConnectedBankAccount $connectedBankAccount): array
    {
        $institutionId = $connectedBankAccount->institution_id ?? null;
        $options = ['new' => 'New Account'];

        if ($institutionId) {
            $options += BankAccount::query()
                ->where('company_id', $this->user->currentCompany->id)
                ->where('institution_id', $institutionId)
                ->whereDoesntHave('connectedBankAccount')
                ->with('account')
                ->get()
                ->pluck('account.name', 'id')
                ->toArray();
        }

        return $options;
    }

    public function stopImportingTransactions(): Action
    {
        return Action::make('stopImportingTransactions')
            ->link()
            ->icon('heroicon-o-stop-circle')
            ->label('Stop importing transactions')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Stop Importing Transactions')
            ->modalDescription('Importing transactions automatically helps keep your bookkeeping up to date. Are you sure you want to turn this off?')
            ->modalSubmitActionLabel('Turn Off')
            ->modalCancelActionLabel('Keep On')
            ->action(function (array $arguments) {
                $connectedBankAccount = ConnectedBankAccount::find($arguments['connectedBankAccount']);

                if ($connectedBankAccount) {
                    $connectedBankAccount->update([
                        'import_transactions' => false,
                    ]);
                }

                unset($this->connectedInstitutions);
            });
    }

    public function refreshTransactions(): Action
    {
        return Action::make('refreshTransactions')
            ->iconButton()
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->record(fn (array $arguments) => Institution::find($arguments['institution']))
            ->modalWidth(fn () => $this->modalWidth)
            ->modalFooterActionsAlignment(fn () => $this->modalWidth === 'screen' ? Alignment::Center : Alignment::Start)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalHeading('Refresh Transactions')
            ->modalSubmitActionLabel('Refresh')
            ->form([
                Placeholder::make('modalDetails')
                    ->hiddenLabel()
                    ->content('Refreshing transactions will update the selected account with the latest transactions from the bank if there are any new transactions available. This may take a few moments.'),
                Select::make('connected_bank_account_id')
                    ->label('Select account')
                    ->softRequired()
                    ->selectablePlaceholder(false)
                    ->hint(
                        fn (Institution $institution) => $institution->getEnabledConnectedBankAccounts()->count() . ' ' .
                        Str::plural('account', $institution->getEnabledConnectedBankAccounts()->count()) . ' available'
                    )
                    ->hintColor('primary')
                    ->options(fn (Institution $institution) => $institution->getEnabledConnectedBankAccounts()->pluck('name', 'id')->toArray())
                    ->default(fn (Institution $institution) => $institution->getEnabledConnectedBankAccounts()->first()?->id),
            ])
            ->action(function (array $data) {
                $connectedBankAccountId = $data['connected_bank_account_id'];
                $connectedBankAccount = ConnectedBankAccount::find($connectedBankAccountId);

                if ($connectedBankAccount) {
                    $access_token = $connectedBankAccount->access_token;
                    $this->plaidService->refreshTransactions($access_token);
                }

                unset($this->connectedInstitutions);
            });
    }

    public function deleteBankConnection(): Action
    {
        return Action::make('deleteBankConnection')
            ->iconButton()
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->modalHeading('Delete Bank Connection')
            ->modalWidth(fn () => $this->modalWidth)
            ->modalFooterActionsAlignment(fn () => $this->modalWidth === 'screen' ? Alignment::Center : Alignment::Start)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->record(fn (array $arguments) => Institution::find($arguments['institution']))
            ->form([
                Placeholder::make('modalDetails')
                    ->hiddenLabel()
                    ->content(static fn (Institution $institution): View => view(
                        'components.actions.delete-bank-connection-modal',
                        compact('institution')
                    )),
                Checkbox::make('confirm')
                    ->label('Yes, I want to delete this bank connection.')
                    ->markAsRequired(false)
                    ->required(),
            ])
            ->action(function (array $arguments, Institution $institution) {
                try {
                    $this->processBankConnectionDeletion($institution);
                } catch (RuntimeException $e) {
                    Log::error('Error deleting bank connection ' . $e->getMessage());

                    $this->sendErrorNotification("We're currently experiencing issues deleting your bank connection. Please try again in a few moments.");
                } finally {
                    unset($this->connectedInstitutions);
                }
            });
    }

    private function processBankConnectionDeletion(Institution $institution): void
    {
        DB::transaction(function () use ($institution) {
            $accessTokens = $institution->connectedBankAccounts->pluck('access_token')->unique()->toArray();

            foreach ($accessTokens as $accessToken) {
                $this->plaidService->removeItem($accessToken);
            }

            $institution->connectedBankAccounts()->each(fn (ConnectedBankAccount $connectedBankAccount) => $connectedBankAccount->delete());
        });
    }

    #[On('createToken')]
    public function createLinkToken(): void
    {
        try {
            $company = $this->user->currentCompany;

            $companyLanguage = $company->locale->language ?? 'en';
            $companyCountry = $company->profile->country ?? 'US';

            $plaidUser = $this->plaidService->createPlaidUser($company);

            $response = $this->plaidService->createToken($companyLanguage, $companyCountry, $plaidUser, ['transactions']);

            $plaidLinkToken = $response->link_token;

            $this->dispatch('initializeLink', $plaidLinkToken)->self();
        } catch (RuntimeException) {
            Log::error('Error creating Plaid token.');

            $this->sendErrorNotification("We're currently experiencing issues connecting your account. Please try again in a few moments.");
        }
    }

    #[On('linkSuccess')]
    public function handleLinkSuccess($publicToken, $metadata): void
    {
        $response = $this->plaidService->exchangePublicToken($publicToken);

        $accessToken = $response->access_token;

        $company = $this->user->currentCompany;

        PlaidSuccess::dispatch($publicToken, $accessToken, $company);

        unset($this->connectedInstitutions);
    }

    public function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Hold on...')
            ->danger()
            ->body($message)
            ->persistent()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.company.service.connected-account.list-institutions');
    }
}
