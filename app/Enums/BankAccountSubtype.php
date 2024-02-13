<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BankAccountSubtype: string implements HasLabel
{
    // Depository Types

    case Checking = 'checking';
    case Savings = 'savings';
    case HealthSavingsAccountCash = 'cash_hsa';
    case CertificateOfDeposit = 'cd';
    case MoneyMarket = 'money_market';
    case Paypal = 'paypal';
    case Prepaid = 'prepaid';
    case CashManagement = 'cash_management';
    case ElectronicBenefitsTransfer = 'ebt';

    // Credit Types
    case CreditCard = 'credit_card';
    case PaypalCredit = 'paypal_credit';

    // Loan Types
    case Auto = 'auto';
    case Business = 'business';
    case Commercial = 'commercial';
    case Construction = 'construction';
    case Consumer = 'consumer';
    case HomeEquity = 'home_equity';
    case Loan = 'loan'; // Generic loan
    case Mortgage = 'mortgage';
    case Overdraft = 'overdraft';
    case LineOfCredit = 'line_of_credit'; // Pre-approved line of credit
    case Student = 'student';
    case Other = 'other';

    // Investment Types
    case CollegeSavings529 = '529';
    case Retirement401a = '401a';
    case Retirement401K = '401k';
    case Retirement403b = '403b';
    case DeferredCompensation457b = '457b';
    case Brokerage = 'brokerage';
    case CashIndividualSavingsAccount = 'cash_isa';
    case CryptoCurrencyExchange = 'crypto_exchange';
    case EducationSavingsAccount = 'esa';
    case FixedAnnuity = 'fixed_annuity';
    case GuaranteedInvestmentCertificate = 'gic';
    case HealthReimbursementArrangement = 'hra';
    case HealthSavingsAccountNonCash = 'non_cash_hsa';
    case IndividualRetirementAccount = 'ira';
    case IndividualSavingsAccount = 'isa';
    case KeoghPlan = 'keogh';
    case LifeIncomeFund = 'lif';
    case LifeInsuranceAccount = 'life_insurance';
    case LockedInRetirementAccount = 'lira'; // Instead of LIRA
    case LockedInRetirementIncomeFund = 'lrif'; // Instead of LRIF
    case LockedInRetirementSavingsPlan = 'lrsp'; // Instead of LRSP
    case MutualFundAccount = 'mutual_fund'; // Instead of MutualFund
    case CryptoCurrencyWallet = 'non_custodial_wallet'; // Instead of NonCustodialWallet
    case NonTaxableBrokerageAccount = 'non_taxable_brokerage_account'; // Instead of NonTaxableBrokerageAccount
    case AnnuityAccountOther = 'other_annuity'; // Instead of OtherAnnuity
    case InsuranceAccountOther = 'other_insurance'; // Instead of OtherInsurance
    case PensionAccount = 'pension'; // Instead of Pension
    case PrescribedRetirementIncomeFund = 'prif'; // Instead of PRIF
    case ProfitSharingPlanAccount = 'profit_sharing_plan'; // Instead of ProfitSharingPlan
    case QualifyingShareAccount = 'qshr'; // Instead of QSHR
    case RegisteredDisabilitySavingsPlan = 'rdsp'; // Instead of RDSP
    case RegisteredEducationSavingsPlan = 'resp'; // Instead of RESP
    case RetirementAccountOther = 'retirement'; // Instead of Retirement
    case RestrictedLifeIncomeFund = 'rlif'; // Instead of RLIF
    case RothIRA = 'roth'; // Instead of Roth
    case Roth401k = 'roth_401k'; // Instead of RothFourOhOneK
    case RegisteredRetirementIncomeFund = 'rrif'; // Instead of RRIF
    case RegisteredRetirementSavingsPlan = 'rrsp'; // Instead of RRSP
    case SalaryReductionSEPPlan = 'sarsep'; // Instead of SARSEP
    case SimplifiedEmployeePensionIRA = 'sep_ira'; // Instead of SEPIRA
    case SavingsIncentiveMatchPlanForEmployeesIRA = 'simple_ira'; // Instead of SIMPLEIRA
    case SelfInvestedPersonalPension = 'sipp'; // Instead of SIPP
    case StockPlanAccount = 'stock_plan'; // Instead of StockPlan
    case TaxFreeSavingsAccount = 'tfsa'; // Instead of TFSA
    case TrustAccount = 'trust'; // Instead of Trust
    case UniformGiftToMinorsAct = 'ugma'; // Instead of UGMA
    case UniformTransfersToMinorsAct = 'utma'; // Instead of UTMA
    case VariableAnnuityAccount = 'variable_annuity'; // Instead of VariableAnnuity

    public function getLabel(): ?string
    {
        $label = match ($this) {
            self::Checking => 'Checking',
            self::Savings => 'Savings',
            self::HealthSavingsAccountCash => 'Health Savings Account (Cash)',
            self::CertificateOfDeposit => 'Certificate of Deposit',
            self::MoneyMarket => 'Money Market',
            self::Paypal => 'PayPal',
            self::Prepaid => 'Prepaid',
            self::CashManagement => 'Cash Management',
            self::ElectronicBenefitsTransfer => 'Electronic Benefits Transfer (EBT)',
            self::CreditCard => 'Credit Card',
            self::PaypalCredit => 'PayPal Credit',
            self::Auto => 'Auto',
            self::Business => 'Business',
            self::Commercial => 'Commercial',
            self::Construction => 'Construction',
            self::Consumer => 'Consumer',
            self::HomeEquity => 'Home Equity',
            self::Loan => 'Loan',
            self::Mortgage => 'Mortgage',
            self::Overdraft => 'Overdraft',
            self::LineOfCredit => 'Line of Credit',
            self::Student => 'Student',
            self::Other => 'Other',
            self::CollegeSavings529 => '529 College Savings Plan',
            self::Retirement401a => '401(a)',
            self::Retirement401K => '401(k)',
            self::Retirement403b => '403(b)',
            self::DeferredCompensation457b => '457(b)',
            self::Brokerage => 'Brokerage',
            self::CashIndividualSavingsAccount => 'Cash Individual Savings Account (ISA)',
            self::CryptoCurrencyExchange => 'Crypto Currency Exchange',
            self::EducationSavingsAccount => 'Education Savings Account (ESA)',
            self::FixedAnnuity => 'Fixed Annuity',
            self::GuaranteedInvestmentCertificate => 'Guaranteed Investment Certificate (GIC)',
            self::HealthSavingsAccountNonCash => 'Health Savings Account (Non-Cash)',
            self::IndividualRetirementAccount => 'Individual Retirement Account (IRA)',
            self::IndividualSavingsAccount => 'Individual Savings Account (ISA)',
            self::KeoghPlan => 'Keogh Plan',
            self::LifeIncomeFund => 'Life Income Fund (LIF)',
            self::LifeInsuranceAccount => 'Life Insurance Account',
            self::LockedInRetirementAccount => 'Locked-In Retirement Account (LIRA)',
            self::LockedInRetirementIncomeFund => 'Locked-In Retirement Income Fund (LRIF)',
            self::LockedInRetirementSavingsPlan => 'Locked-In Retirement Savings Plan (LRSP)',
            self::MutualFundAccount => 'Mutual Fund Account',
            self::CryptoCurrencyWallet => 'Non-Custodial Wallet',
            self::NonTaxableBrokerageAccount => 'Non-Taxable Brokerage Account',
            self::AnnuityAccountOther => 'Other Annuity',
            self::InsuranceAccountOther => 'Other Insurance',
            self::PensionAccount => 'Pension',
            self::PrescribedRetirementIncomeFund => 'Prescribed Retirement Income Fund (PRIF)',
            self::ProfitSharingPlanAccount => 'Profit Sharing Plan',
            self::QualifyingShareAccount => 'Qualifying Share Account (QSHR)',
            self::RegisteredDisabilitySavingsPlan => 'Registered Disability Savings Plan (RDSP)',
            self::RegisteredEducationSavingsPlan => 'Registered Education Savings Plan (RESP)',
            self::RetirementAccountOther => 'Retirement',
            self::RestrictedLifeIncomeFund => 'Restricted Life Income Fund (RLIF)',
            self::RothIRA => 'Roth IRA',
            self::Roth401k => 'Roth 401(k)',
            self::RegisteredRetirementIncomeFund => 'Registered Retirement Income Fund (RRIF)',
            self::RegisteredRetirementSavingsPlan => 'Registered Retirement Savings Plan (RRSP)',
            self::SalaryReductionSEPPlan => 'Salary Reduction SEP Plan (SARSEP)',
            self::SimplifiedEmployeePensionIRA => 'Simplified Employee Pension IRA (SEP IRA)',
            self::SavingsIncentiveMatchPlanForEmployeesIRA => 'Savings Incentive Match Plan for Employees IRA (SIMPLE IRA)',
            self::SelfInvestedPersonalPension => 'Self-Invested Personal Pension (SIPP)',
            self::StockPlanAccount => 'Stock Plan',
            self::TaxFreeSavingsAccount => 'Tax-Free Savings Account (TFSA)',
            self::TrustAccount => 'Trust',
            self::UniformGiftToMinorsAct => 'Uniform Gift to Minors Act (UGMA)',
            self::UniformTransfersToMinorsAct => 'Uniform Transfers to Minors Act (UTMA)',
            self::VariableAnnuityAccount => 'Variable Annuity',
        };

        return $label;
    }
}
