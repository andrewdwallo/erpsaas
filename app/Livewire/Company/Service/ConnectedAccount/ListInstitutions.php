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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use JsonException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

class ListInstitutions extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected PlaidService $plaidService;

    public User $user;

    public ?ConnectedBankAccount $connectedBankAccount = null;

    public function boot(PlaidService $plaidService): void
    {
        $this->plaidService = $plaidService;
    }

    public function mount(): void
    {
        $this->user = Auth::user();
    }

    #[Computed]
    public function connectedInstitutions(): Collection|array
    {
        return Institution::withWhereHas('connectedBankAccounts')
            ->get();
    }

    public function startImportingTransactions(): Action
    {
        return Action::make('startImportingTransactions')
            ->link()
            ->icon('heroicon-o-cloud-arrow-down')
            ->label('Start Importing Transactions')
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->record($this->connectedBankAccount)
            ->mountUsing(function (array $arguments, Form $form) {
                $connectedAccountId = $arguments['connectedBankAccount'];

                $this->connectedBankAccount = ConnectedBankAccount::find($connectedAccountId);

                $form
                    ->fill($this->connectedBankAccount->toArray())
                    ->operation('edit')
                    ->model($this->connectedBankAccount);
            })
            ->form([
                Select::make('bank_account_id')
                    ->label('Select Account')
                    ->visible(static fn (?ConnectedBankAccount $connectedBankAccount) => $connectedBankAccount?->bank_account_id === null)
                    ->options(fn () => $this->getBankAccountOptions())
                    ->required()
                    ->placeholder('Select an account to start importing transactions for.'),
                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->placeholder('Select a start date for importing transactions.'),
            ])
            ->action(function (array $arguments, array $data, ConnectedBankAccount $connectedBankAccount) {
                $connectedBankAccountId = $arguments['connectedBankAccount'];
                $selectedBankAccountId = $data['bank_account_id'] ?? $connectedBankAccount->bank_account_id;
                $startDate = $data['start_date'];
                $company = $this->user->currentCompany;

                StartTransactionImport::dispatch($company, $connectedBankAccountId, $selectedBankAccountId, $startDate);

                unset($this->connectedInstitutions);
            });
    }

    public function getBankAccountOptions(): array
    {
        $institutionId = $this->connectedBankAccount->institution_id ?? null;

        $options = BankAccount::query()
            ->where('company_id', $this->user->currentCompany->id)
            ->when($institutionId, static fn($query) => $query->where('institution_id', $institutionId))
            ->whereDoesntHave('connectedBankAccount')
            ->with('account')
            ->get()
            ->pluck('account.name', 'id')
            ->toArray();

        return ['new' => 'New Account'] + $options;
    }

    public function stopImportingTransactions(): Action
    {
        return Action::make('stopImportingTransactions')
            ->link()
            ->icon('heroicon-o-stop-circle')
            ->label('Stop Importing Transactions')
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
                        'import_transactions' => !$connectedBankAccount->import_transactions,
                    ]);
                }

                unset($this->connectedInstitutions);
            });
    }

    public function deleteBankConnection(): Action
    {
        return Action::make('deleteBankConnection')
            ->iconButton()
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading('Delete Bank Connection')
            ->modalDescription('Deleting this bank connection will stop the import of transactions for all accounts associated with this bank. Existing transactions will remain unchanged.')
            ->action(function (array $arguments) {
                $institutionId = $arguments['institution'];

                $institution = Institution::find($institutionId);

                if ($institution) {
                    $institution->connectedBankAccounts()->delete();
                }

                unset($this->connectedInstitutions);
            });
    }

    #[On('createToken')]
    public function createLinkToken(): void
    {
        $company = $this->user->currentCompany;

        $companyLanguage = $company->locale->language ?? 'en';
        $companyCountry = $company->profile->country ?? 'US';

        $plaidUser = $this->plaidService->createPlaidUser($company);

        try {
            $response = $this->plaidService->createToken($companyLanguage, $companyCountry, $plaidUser, ['transactions']);

            $plaidLinkToken = $response->link_token;

            $this->dispatch('initializeLink', $plaidLinkToken)->self();
        } catch (RuntimeException) {
            Log::error("Error creating Plaid token.");

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
            ->title('Hold On...')
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
