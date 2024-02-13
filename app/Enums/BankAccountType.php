<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BankAccountType: string implements HasLabel
{
    case Investment = 'investment';
    case Credit = 'credit';
    case Depository = 'depository';
    case Loan = 'loan';
    case Other = 'other';

    public const DEFAULT = self::Depository;

    public function getLabel(): ?string
    {
        return translate($this->name);
    }

    public function getSubtypes(): array
    {
        return match ($this) {
            self::Depository => [
                BankAccountSubtype::Checking,
                BankAccountSubtype::Savings,
                BankAccountSubtype::HealthSavingsAccountCash,
                BankAccountSubtype::CertificateOfDeposit,
                BankAccountSubtype::MoneyMarket,
                BankAccountSubtype::Paypal,
                BankAccountSubtype::Prepaid,
                BankAccountSubtype::CashManagement,
                BankAccountSubtype::ElectronicBenefitsTransfer,
            ],
            self::Credit => [
                BankAccountSubtype::CreditCard,
                BankAccountSubtype::PaypalCredit,
            ],
            self::Loan => [
                BankAccountSubtype::Auto,
                BankAccountSubtype::Business,
                BankAccountSubtype::Commercial,
                BankAccountSubtype::Construction,
                BankAccountSubtype::Consumer,
                BankAccountSubtype::HomeEquity,
                BankAccountSubtype::Loan,
                BankAccountSubtype::Mortgage,
                BankAccountSubtype::Overdraft,
                BankAccountSubtype::LineOfCredit,
                BankAccountSubtype::Student,
                BankAccountSubtype::Other,
            ],
            self::Investment => [
                BankAccountSubtype::CollegeSavings529,
                BankAccountSubtype::Retirement401a,
                BankAccountSubtype::Retirement401K,
                BankAccountSubtype::Retirement403b,
                BankAccountSubtype::DeferredCompensation457b,
                BankAccountSubtype::Brokerage,
                BankAccountSubtype::CashIndividualSavingsAccount,
                BankAccountSubtype::CryptoCurrencyExchange,
                BankAccountSubtype::EducationSavingsAccount,
                BankAccountSubtype::FixedAnnuity,
                BankAccountSubtype::GuaranteedInvestmentCertificate,
                BankAccountSubtype::HealthSavingsAccountNonCash,
                BankAccountSubtype::IndividualRetirementAccount,
                BankAccountSubtype::IndividualSavingsAccount,
                BankAccountSubtype::KeoghPlan,
                BankAccountSubtype::LifeIncomeFund,
                BankAccountSubtype::LifeInsuranceAccount,
                BankAccountSubtype::LockedInRetirementAccount,
                BankAccountSubtype::LockedInRetirementIncomeFund,
                BankAccountSubtype::LockedInRetirementSavingsPlan,
                BankAccountSubtype::MutualFundAccount,
                BankAccountSubtype::CryptoCurrencyWallet,
                BankAccountSubtype::NonTaxableBrokerageAccount,
                BankAccountSubtype::AnnuityAccountOther,
                BankAccountSubtype::InsuranceAccountOther,
                BankAccountSubtype::PensionAccount,
                BankAccountSubtype::PrescribedRetirementIncomeFund,
                BankAccountSubtype::ProfitSharingPlanAccount,
                BankAccountSubtype::QualifyingShareAccount,
                BankAccountSubtype::RegisteredDisabilitySavingsPlan,
                BankAccountSubtype::RegisteredEducationSavingsPlan,
                BankAccountSubtype::RetirementAccountOther,
                BankAccountSubtype::RestrictedLifeIncomeFund,
                BankAccountSubtype::RothIRA,
                BankAccountSubtype::Roth401k,
                BankAccountSubtype::RegisteredRetirementIncomeFund,
                BankAccountSubtype::RegisteredRetirementSavingsPlan,
                BankAccountSubtype::SalaryReductionSEPPlan,
                BankAccountSubtype::SimplifiedEmployeePensionIRA,
                BankAccountSubtype::SavingsIncentiveMatchPlanForEmployeesIRA,
                BankAccountSubtype::SelfInvestedPersonalPension,
                BankAccountSubtype::StockPlanAccount,
                BankAccountSubtype::TaxFreeSavingsAccount,
                BankAccountSubtype::TrustAccount,
                BankAccountSubtype::UniformGiftToMinorsAct,
                BankAccountSubtype::UniformTransfersToMinorsAct,
                BankAccountSubtype::VariableAnnuityAccount,
            ],
            self::Other => [
                BankAccountSubtype::Other,
            ],
        };
    }
}
