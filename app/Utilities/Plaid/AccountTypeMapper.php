<?php

namespace App\Utilities\Plaid;

use App\Enums\BankAccountSubtype;
use App\Enums\BankAccountType;

class AccountTypeMapper
{
    public static function mapToEnums(string $plaidType, string $plaidSubtype): array
    {
        $mappedType = self::mapType($plaidType);
        $mappedSubtype = self::mapSubtype($plaidType, $plaidSubtype);

        return [
            'type' => $mappedType,
            'subtype' => $mappedSubtype,
        ];
    }

    private static function mapType(string $plaidType): BankAccountType
    {
        return match ($plaidType) {
            'depository' => BankAccountType::Depository,
            'credit' => BankAccountType::Credit,
            'loan' => BankAccountType::Loan,
            'investment' => BankAccountType::Investment,
            default => BankAccountType::Other,
        };
    }

    private static function mapSubtype(string $plaidType, string $plaidSubtype): ?BankAccountSubtype
    {
        return match ($plaidType) {
            'depository' => match ($plaidSubtype) {
                'checking' => BankAccountSubtype::Checking,
                'savings' => BankAccountSubtype::Savings,
                'hsa' => BankAccountSubtype::HealthSavingsAccountCash,
                'cd' => BankAccountSubtype::CertificateOfDeposit,
                'money market' => BankAccountSubtype::MoneyMarket,
                'paypal' => BankAccountSubtype::Paypal,
                'prepaid' => BankAccountSubtype::Prepaid,
                'cash management' => BankAccountSubtype::CashManagement,
                'ebt' => BankAccountSubtype::ElectronicBenefitsTransfer,
                default => BankAccountSubtype::Other,
            },
            'credit' => match ($plaidSubtype) {
                'credit card' => BankAccountSubtype::CreditCard,
                'paypal' => BankAccountSubtype::PaypalCredit,
                default => BankAccountSubtype::Other,
            },
            'loan' => match ($plaidSubtype) {
                'auto' => BankAccountSubtype::Auto,
                'business' => BankAccountSubtype::Business,
                'commercial' => BankAccountSubtype::Commercial,
                'construction' => BankAccountSubtype::Construction,
                'consumer' => BankAccountSubtype::Consumer,
                'home equity' => BankAccountSubtype::HomeEquity,
                'loan' => BankAccountSubtype::Loan,
                'mortgage' => BankAccountSubtype::Mortgage,
                'overdraft' => BankAccountSubtype::Overdraft,
                'line of credit' => BankAccountSubtype::LineOfCredit,
                'student' => BankAccountSubtype::Student,
                default => BankAccountSubtype::Other,
            },
            'investment' => match ($plaidSubtype) {
                '529' => BankAccountSubtype::CollegeSavings529,
                '401a' => BankAccountSubtype::Retirement401a,
                '401k' => BankAccountSubtype::Retirement401K,
                '403B' => BankAccountSubtype::Retirement403b,
                '457b' => BankAccountSubtype::DeferredCompensation457b,
                'brokerage' => BankAccountSubtype::Brokerage,
                'cash isa' => BankAccountSubtype::CashIndividualSavingsAccount,
                'crypto exchange' => BankAccountSubtype::CryptoCurrencyExchange,
                'education savings account' => BankAccountSubtype::EducationSavingsAccount,
                'fixed annuity' => BankAccountSubtype::FixedAnnuity,
                'gic' => BankAccountSubtype::GuaranteedInvestmentCertificate,
                'health reimbursement arrangement' => BankAccountSubtype::HealthReimbursementArrangement,
                'hsa' => BankAccountSubtype::HealthSavingsAccountNonCash,
                'ira' => BankAccountSubtype::IndividualRetirementAccount,
                'isa' => BankAccountSubtype::IndividualSavingsAccount,
                'keogh' => BankAccountSubtype::KeoghPlan,
                'lif' => BankAccountSubtype::LifeIncomeFund,
                'life insurance' => BankAccountSubtype::LifeInsuranceAccount,
                'lira' => BankAccountSubtype::LockedInRetirementAccount,
                'lrif' => BankAccountSubtype::LockedInRetirementIncomeFund,
                'lrsp' => BankAccountSubtype::LockedInRetirementSavingsPlan,
                'mutual fund' => BankAccountSubtype::MutualFundAccount,
                'non-custodial wallet' => BankAccountSubtype::CryptoCurrencyWallet,
                'non-taxable brokerage account' => BankAccountSubtype::NonTaxableBrokerageAccount,
                'other annuity' => BankAccountSubtype::AnnuityAccountOther,
                'other insurance' => BankAccountSubtype::InsuranceAccountOther,
                'pension' => BankAccountSubtype::PensionAccount,
                'prif' => BankAccountSubtype::PrescribedRetirementIncomeFund,
                'profit sharing plan' => BankAccountSubtype::ProfitSharingPlanAccount,
                'qshr' => BankAccountSubtype::QualifyingShareAccount,
                'rdsp' => BankAccountSubtype::RegisteredDisabilitySavingsPlan,
                'resp' => BankAccountSubtype::RegisteredEducationSavingsPlan,
                'retirement' => BankAccountSubtype::RetirementAccountOther,
                'rlif' => BankAccountSubtype::RestrictedLifeIncomeFund,
                'roth' => BankAccountSubtype::RothIRA,
                'roth 401k' => BankAccountSubtype::Roth401k,
                'rrif' => BankAccountSubtype::RegisteredRetirementIncomeFund,
                'rrsp' => BankAccountSubtype::RegisteredRetirementSavingsPlan,
                'sarsep' => BankAccountSubtype::SalaryReductionSEPPlan,
                'sep ira' => BankAccountSubtype::SimplifiedEmployeePensionIRA,
                'simple ira' => BankAccountSubtype::SavingsIncentiveMatchPlanForEmployeesIRA,
                'sipp' => BankAccountSubtype::SelfInvestedPersonalPension,
                'stock plan' => BankAccountSubtype::StockPlanAccount,
                'tfsa' => BankAccountSubtype::TaxFreeSavingsAccount,
                'trust' => BankAccountSubtype::TrustAccount,
                'ugma' => BankAccountSubtype::UniformGiftToMinorsAct,
                'utma' => BankAccountSubtype::UniformTransfersToMinorsAct,
                'variable annuity' => BankAccountSubtype::VariableAnnuityAccount,
                default => BankAccountSubtype::Other,
            },

            default => BankAccountSubtype::Other,

        };

    }
}
