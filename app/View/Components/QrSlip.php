<?php

namespace App\View\Components;

use App\Models\Common\Client;
use App\Models\Company;
use App\Models\Locale\Country;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Sprain\SwissQrBill as QrBill;
use Sprain\SwissQrBill\PaymentPart\Output\DisplayOptions;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\HtmlOutput;

class QrSlip extends Component
{
    public string $qrPaymentSlip = "";
    public $client;
    public $company;
    public $accountNumber;
    public $amount;
    public $currency;
    public $number;
    public $reference;

    /**
     * Create a new component instance.
     */
    public function __construct($client, $company, $amount, $currency, $number, $reference)
    {
        $this->client = $client;
        $this->company = $company;
        $companyModel = Company::where("name", $company->name)->first();
        $this->accountNumber = $companyModel->bankAccounts->where("enabled", true)->load("account")->whereNotNull("account.description")?->first()->account->description ?? die("No bank account found for company " . $company->name);
        $this->currency = $currency;
        switch ($this->currency) {
            case "CHF":
                // Remove the first three characters (e.g. "Fr. ") and convert to float
                $this->amount = (float) filter_var(substr($amount, 3), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                break;
            case "EUR":
                // Remove the first three characters (e.g. "€ ") and convert to float
                $this->amount = (float) filter_var(substr($amount, 2), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                break;
            default:
                die("Currency not supported for QR Payment Slip: " . $this->currency);
        }
        $this->number = $number;
        $this->reference = $reference;
        $this->generateQrPaymentSlip();
    }

    /**
     * Generate QR Payment Slip
     */
    public function generateQrPaymentSlip(): void
    {
        $qrBill = QrBill\QrBill::create();
        $qrBill->setCreditor(
            QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
                $this->company->name,
                "",
                "",
                $this->company->postalCode,
                $this->company->city,
                Country::where("native_name", $this->company->country)->first()->iso_code_2 ?? die("Country not found: " . $this->company->country)
            )
        );
        $qrBill->setCreditorInformation(
            QrBill\DataGroup\Element\CreditorInformation::create(
                $this->accountNumber
            )
        );

        $qrBill->setUltimateDebtor(
            QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
                $this->client->name,
                $this->client->addressLine1,
                $this->client->addressLine2,
                $this->client->postalCode,
                $this->client->city,
                Country::where("native_name", $this->client->country)->first()->iso_code_2 ?? die("Country not found: " . $this->client->country)
            )
        );

        $qrBill->setPaymentAmountInformation(
            QrBill\DataGroup\Element\PaymentAmountInformation::create(
                $this->currency,
                $this->amount
            )
        );

        $qrBill->setPaymentReference(
            QrBill\DataGroup\Element\PaymentReference::create(
                QrBill\DataGroup\Element\PaymentReference::TYPE_SCOR,
                QrBill\Reference\RfCreditorReferenceGenerator::generate(
                    date("Y") . explode("-", $this->number)[1]
                )
            )
        );

        $qrBill->setAdditionalInformation(
            QrBill\DataGroup\Element\AdditionalInformation::create(
                $this->reference
            )
        );

        $output = new HtmlOutput($qrBill, "de");
        $displayOptions = new DisplayOptions();
        $displayOptions
            ->setPrintable(false)
            ->setDisplayTextDownArrows(false)
            ->setDisplayScissors(false)
            ->setPositionScissorsAtBottom(false);

        if (count($qrBill->getViolations()) > 0) {
            dd($qrBill->getViolations());
        }
        $this->qrPaymentSlip = $output
            ->setDisplayOptions($displayOptions)
            ->getPaymentPart();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.company.document-template.qr-slip');
    }
}
