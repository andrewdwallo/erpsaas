<?php

namespace App\Services;

use Nordigen\NordigenPHP\API\NordigenClient;
use Nordigen\NordigenPHP\DTO\Nordigen\SessionDTO;


class NordigenService
{

    private NordigenClient $client;

    public function __construct(string $secretId, string $secretKey) {
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
        $this->client = new NordigenClient($secretId, $secretKey);
        $this->client->createAccessToken();
    }


    public function getListOfInstitutions(string $country)
    {
        return $this->client->institution->getInstitutionsByCountry($country);
    }

    public function getSessionData(string $redirectUri, string $institutionId, int $maxHistoricalDays = 90): array
    {
        return $this->client->initSession($institutionId, $redirectUri, $maxHistoricalDays);
    }

    private function getListOfAccounts(string $requisitionId): array
    {
        return $this->client->requisition->getRequisition($requisitionId)["accounts"];
    }

    public function getAccountData(string $requisitionId): array
    {
        $accountArray = $this->getListOfAccounts($requisitionId);
        $accountData = [];

        foreach($accountArray as $id) {
            $account = $this->client->account($id);
            $accountData[] = [
                "metaData"     => $account->getAccountMetaData(),
                "details"      => $account->getAccountDetails(),
                "balances"     => $account->getAccountBalances(),
                "transactions" => $account->getAccountTransactions()
            ];
        }
        return $accountData;
    }

}