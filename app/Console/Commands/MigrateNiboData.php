<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

// MODELS
use App\Models\Banking\BankAccount;
use App\Models\Banking\Institution;
use App\Models\Accounting\Transaction;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Account;
use App\Models\Common\Vendor;
use App\Models\Common\Client as MyClient;

class MigrateNiboData extends Command
{
    protected $signature = 'app:migrate-nibo-data';
    protected $description = 'Migra dados do Nibo (organizational, balance, schedule, payments, receipts, statements) para o ERP.';

    // Ajuste conforme sua API
    private string $baseUrl = 'https://api.nibo.com.br/empresas/v1';
    private ?string $apiToken = null; // Carregado do .env ou config

    // Mapeamentos em memória: Nibo IDs => IDs do ERP
    private array $organizationMap = [];
    private array $accountMap = [];
    private array $scheduleMap = [];
    private array $stakeholderMap = [];

    public function __construct()
    {
        parent::__construct();
        // Carrega tokens do .env
        $this->apiToken = env('Tk_B2BF', 'x');
    }

    public function handle()
    {
        $this->info('Iniciando migração de dados do Nibo...');
        
        // Se quiser que tudo seja atômico (rollback em falha):
        DB::transaction(function () {
            // EXTRACT / TRANSFORM / LOAD

            // 1) Carregar “Listas” em geral, p. ex. /organizations
            $this->migrateOrganizations();

            // 2) Migrar as contas (endpoint balance) → bank_accounts
            $this->migrateBalances();

            // 3) Migrar schedules → bills ou invoices
            $this->migrateSchedules();

            // 4) Migrar payments → transactions (saída)
            $this->migratePayments();

            // 5) Migrar receipts → transactions (entrada)
            $this->migrateReceipts();

            // 6) (Opcional) Migrar extrato detalhado
            $this->migrateStatements();
        });

        $this->info('Migração concluída com sucesso!');
    }

    /* -----------------------------------------------------------------------------------------
       1) MIGRAÇÃO DAS “LISTAS” (ORGANIZATIONS)
       ----------------------------------------------------------------------------------------- */

    /**
     * Exemplo para extrair /organizations (Listas), se você quiser armazenar isso como “Company” ou algo do tipo.
     */
    private function migrateOrganizations()
    {
        $this->info('> Buscando /organizations...');
        // Se o CSV “MAPA ETL Nibo - Listas.csv” indica que /organizations exige GET sem parâmetros,
        // podemos chamar um método fetchPaginated ou fetchSimple.
        $endpoint = 'organizations';
        $orgs = $this->fetchSimple($endpoint);

        // Transform/Load: mapear “organizationId” => “companies” (ou outra tabela)
        foreach ($orgs['items'] ?? [] as $org) {
            $orgId = $org['organizationId'];
            $orgName = $org['name'] ?? 'Sem nome';
            $cnpj = $org['cnpj'] ?? null;

            // Exemplo de “firstOrCreate” em “companies” (depende se quiser mapear organizations do Nibo -> Company)
            // Ajuste para o seu schema real
            // 
            // $company = Company::firstOrCreate(
            //     [ 'nibo_org_id' => $orgId ], // Necessita de uma coluna extra “nibo_org_id”? 
            //     [
            //         'name' => $orgName,
            //         'cnpj' => $cnpj,
            //         // ...
            //     ]
            // );
            // $this->organizationMap[$orgId] = $company->id;
        }
    }

    /* -----------------------------------------------------------------------------------------
       2) MIGRAÇÃO DE BALANCES (CONTAS BANCÁRIAS)
       ----------------------------------------------------------------------------------------- */

    private function migrateBalances()
    {
        $this->info('> Migrando /accounts/balance...');
        $endpoint = 'accounts/balance';
        $balances = $this->fetchPaginated($endpoint);

        foreach ($balances as $item) {
            // Ex.: $item['accountId'], $item['accountName'], $item['balance']
            $niboAccountId = $item['accountId'];
            $bankName = $item['bank']['name'] ?? 'Banco Desconhecido';

            // 1) Localizar/criar Institution
            $institution = Institution::firstOrCreate(
                ['name' => $bankName],
                ['website' => null /* ex. */]
            );

            // 2) Criar ou localizar conta contábil (Account)
            $account = Account::firstOrCreate(
                [
                    'company_id' => 1,
                    'name'       => $item['accountName'],
                ],
                [
                    'type'       => 'asset',  // ou outro
                    'description'=> 'Conta importada do Nibo'
                ]
            );

            // 3) Criar ou localizar a BankAccount
            $bankAccount = BankAccount::firstOrCreate(
                [
                    'company_id' => 1,
                    'account_id' => $account->id,
                ],
                [
                    'institution_id' => $institution->id,
                    'number'         => $item['accountName'], 
                    'type'           => 'depository',
                    'enabled'        => true,
                ]
            );

            // 4) Guardar no array de mapeamento
            $this->accountMap[$niboAccountId] = $bankAccount->id;

            // 5) Se quiser transação de “saldo inicial”, crie transaction ou journal entry de abertura
        }
    }

    /* -----------------------------------------------------------------------------------------
       3) MIGRAÇÃO DE SCHEDULES → BILLS OU INVOICES
       ----------------------------------------------------------------------------------------- */

    private function migrateSchedules()
    {
        $this->info('> Migrando /schedules...');
        $endpoint = 'schedules';
        $items = $this->fetchPaginated($endpoint);

        foreach ($items as $item) {
            // Ex.: $item['scheduleId'], $item['type'], $item['value'], etc.
            $scheduleId = $item['scheduleId'] ?? null;
            if (!$scheduleId) continue;

            $isCredit = ($item['type'] === 'Credit');
            $isPaid = (bool) ($item['isPaid'] ?? false);

            // stakeholder => vendor/client
            $stakeholder = $item['stakeholder'] ?? null;
            [$vendorId, $clientId] = $this->resolveStakeholder($stakeholder);

            // Decide se vira “invoice” ou “bill”
            if ($isCredit) {
                $invoice = Invoice::create([
                    'company_id'   => 1,
                    'client_id'    => $clientId,
                    'date'         => $this->toDate($item['accrualDate']),
                    'due_date'     => $this->toDate($item['dueDate']),
                    'status'       => $isPaid ? 'paid' : 'open',
                    'total'        => $this->toInt($item['value'] ?? 0),
                    'amount_paid'  => $isPaid ? $this->toInt($item['value'] ?? 0) : 0,
                    'notes'        => $item['description'] ?? '',
                ]);
                // map
                $this->scheduleMap[$scheduleId] = ['type' => 'invoice', 'id' => $invoice->id];
            } else {
                $bill = Bill::create([
                    'company_id'   => 1,
                    'vendor_id'    => $vendorId,
                    'date'         => $this->toDate($item['accrualDate']),
                    'due_date'     => $this->toDate($item['dueDate']),
                    'status'       => $isPaid ? 'paid' : 'open',
                    'total'        => $this->toInt($item['value'] ?? 0),
                    'amount_paid'  => $isPaid ? $this->toInt($item['value'] ?? 0) : 0,
                    'notes'        => $item['description'] ?? '',
                ]);
                $this->scheduleMap[$scheduleId] = ['type' => 'bill', 'id' => $bill->id];
            }
        }
    }

    /* -----------------------------------------------------------------------------------------
       4) MIGRAÇÃO DE PAYMENTS → TRANSAÇÕES (SAÍDA)
       ----------------------------------------------------------------------------------------- */

    private function migratePayments()
    {
        $this->info('> Migrando /payments...');
        $endpoint = 'payments';
        $items = $this->fetchPaginated($endpoint);

        foreach ($items as $item) {
            // Ex.: $item['scheduleId'], $item['account']['id'], $item['value']
            $scheduleId = $item['scheduleId'] ?? null;
            $niboBankAccountId = $item['account']['id'] ?? null;
            $value = $this->toInt($item['value'] ?? 0);

            // Localiza bankAccount no ERP
            $erpBankAccountId = $this->accountMap[$niboBankAccountId] ?? null;
            if (!$erpBankAccountId) continue;

            // Cria transaction (saída)
            $transaction = Transaction::create([
                'company_id'      => 1,
                'bank_account_id' => $erpBankAccountId,
                'type'            => 'withdrawal',
                'description'     => $item['description'] ?? 'Payment from Nibo',
                'posted_at'       => $this->toDate($item['date']),
                'amount'          => $value,
                'reference'       => $item['reference'] ?? null,
                'reviewed'        => (bool) $item['isReconciliated'],
            ]);

            // Se o schedule existe, atualizar Bill/Invoice
            if ($scheduleId && isset($this->scheduleMap[$scheduleId])) {
                $ref = $this->scheduleMap[$scheduleId];
                if ($ref['type'] === 'bill') {
                    $bill = Bill::find($ref['id']);
                    if ($bill) {
                        $bill->amount_paid += $value;
                        if ($bill->amount_paid >= $bill->total) {
                            $bill->status = 'paid';
                        }
                        $bill->save();
                    }
                } elseif ($ref['type'] === 'invoice') {
                    $invoice = Invoice::find($ref['id']);
                    if ($invoice) {
                        $invoice->amount_paid += $value;
                        if ($invoice->amount_paid >= $invoice->total) {
                            $invoice->status = 'paid';
                        }
                        $invoice->save();
                    }
                }
            }
        }
    }

    /* -----------------------------------------------------------------------------------------
       5) MIGRAÇÃO DE RECEIPTS → TRANSAÇÕES (ENTRADA)
       ----------------------------------------------------------------------------------------- */

    private function migrateReceipts()
    {
        $this->info('> Migrando /receipts...');
        $endpoint = 'receipts';
        $items = $this->fetchPaginated($endpoint);

        foreach ($items as $item) {
            $scheduleId = $item['scheduleId'] ?? null;
            $niboBankAccountId = $item['account']['id'] ?? null;
            $value = $this->toInt($item['value'] ?? 0);

            $erpBankAccountId = $this->accountMap[$niboBankAccountId] ?? null;
            if (!$erpBankAccountId) continue;

            // Cria transaction (entrada)
            $transaction = Transaction::create([
                'company_id'      => 1,
                'bank_account_id' => $erpBankAccountId,
                'type'            => 'deposit',
                'description'     => $item['description'] ?? 'Receipt from Nibo',
                'posted_at'       => $this->toDate($item['date']),
                'amount'          => $value,
                'reference'       => $item['reference'] ?? null,
                'reviewed'        => (bool) $item['isReconciliated'],
            ]);

            // Se tiver schedule => invoice/bill
            if ($scheduleId && isset($this->scheduleMap[$scheduleId])) {
                $ref = $this->scheduleMap[$scheduleId];
                if ($ref['type'] === 'invoice') {
                    $invoice = Invoice::find($ref['id']);
                    if ($invoice) {
                        $invoice->amount_paid += $value;
                        if ($invoice->amount_paid >= $invoice->total) {
                            $invoice->status = 'paid';
                        }
                        $invoice->save();
                    }
                } elseif ($ref['type'] === 'bill') {
                    // Caso inusitado, mas se fosse “Crédito” num Bill...
                    $bill = Bill::find($ref['id']);
                    if ($bill) {
                        $bill->amount_paid += $value;
                        if ($bill->amount_paid >= $bill->total) {
                            $bill->status = 'paid';
                        }
                        $bill->save();
                    }
                }
            }
        }
    }

    /* -----------------------------------------------------------------------------------------
       6) MIGRAÇÃO DE STATEMENTS (opcional, /accounts/{id}/views/statement)
       ----------------------------------------------------------------------------------------- */

    private function migrateStatements()
    {
        $this->info('> Migrando extratos de cada conta...');
        // Supondo que iremos pegar o range de datas do CSV ou de .env
        $startDate = '2025-01-01';
        $endDate   = '2025-02-19';

        foreach ($this->accountMap as $niboAccountId => $erpBankAccountId) {
            $endpoint = "accounts/{$niboAccountId}/views/statement";
            $items = $this->fetchStatements($endpoint, $startDate, $endDate);

            // Exemplo: $items["items"] = [ { "entryId": "...", "description": "Saldo Inicial", ... } ]
            foreach ($items['items'] ?? [] as $stItem) {
                // A maior parte destas transações já podem estar cobertas por "payments" e "receipts",
                // mas se precisar criar entradas extras (ex.: "Saldo Inicial" sem scheduleId),
                // você pode criar transaction. Ou ignorar se duplicaria.
            }
        }
    }

    /* -----------------------------------------------------------------------------------------
       MÉTODOS AUXILIARES DE EXTRAÇÃO
       ----------------------------------------------------------------------------------------- */

    /**
     * Busca dados simples (sem paginação) – ex.: /organizations 
     */
    private function fetchSimple(string $endpoint): array
    {
        $client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'apitoken' => $this->apiToken,
                'Accept'   => 'application/json',
            ],
            'timeout' => 60,
        ]);

        $response = $client->request('GET', $endpoint);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Falha ao consultar $endpoint. Status: ".$response->getStatusCode());
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Busca com paginação ($skip, $top), como payments/schedules/receipts/balance.
     */
    private function fetchPaginated(string $endpoint, array $query = [], string $orderby = null, int $top = 500): array
    {
        $client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'apitoken' => $this->apiToken,
                'Accept'   => 'application/json',
            ],
            'timeout' => 60,
        ]);

        $allItems = [];
        $skip = 0;
        $count = null;

        while ($count === null || $skip < $count) {
            $params = array_merge($query, [
                '$top' => $top,
                '$skip' => $skip,
            ]);
            if ($orderby) {
                $params['$orderby'] = $orderby;
            }

            $response = $client->request('GET', $endpoint, ['query' => $params]);
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Falha ao consultar $endpoint. HTTP Status: " . $response->getStatusCode());
            }

            $data = json_decode($response->getBody()->getContents(), true);
            if (!isset($data['items'])) {
                break;
            }

            $items = $data['items'];
            $allItems = array_merge($allItems, $items);

            if ($count === null) {
                $count = $data['count'] ?? count($items);
            }
            $skip += $top;
        }

        return $allItems;
    }

    /**
     * Busca statements (extrato) para um accountId específico. 
     * Normalmente sem paginação, mas se precisar, você pode adaptar.
     */
    private function fetchStatements(string $endpoint, string $startDate, string $endDate): array
    {
        $client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'apitoken' => $this->apiToken,
                'Accept'   => 'application/json',
            ],
            'timeout' => 60,
        ]);

        $response = $client->request('GET', $endpoint, [
            'query' => [
                'startDate' => $startDate,
                'endDate'   => $endDate
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            $this->warn("Falha ao consultar statement em $endpoint. Status=".$response->getStatusCode());
            return [];
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /* -----------------------------------------------------------------------------------------
       FUNÇÕES DE TRANSFORMAÇÃO
       ----------------------------------------------------------------------------------------- */

    /**
     * Exemplo para converter data (string ISO8601) em formato YYYY-MM-DD.
     */
    private function toDate(?string $dateStr)
    {
        if (!$dateStr) {
            return null;
        }
        return \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
    }

    /**
     * Exemplo para converter valor float em centavos (int).
     */
    private function toInt(float $value)
    {
        return (int) round($value * 100);
    }

    /**
     * Resolve se stakeholder é “vendor” ou “client”.
     * Retorna array [vendor_id, client_id].
     */
    private function resolveStakeholder(?array $stakeholder): array
    {
        if (!$stakeholder) {
            return [null, null];
        }
        $name = $stakeholder['name'] ?? 'Sem Nome';
        $type = strtolower($stakeholder['type'] ?? 'customer');

        $vendorId = null;
        $clientId = null;

        if ($type === 'customer') {
            $client = MyClient::firstOrCreate([
                'company_id' => 1,
                'name'       => $name,
            ]);
            $clientId = $client->id;
        } elseif (in_array($type, ['supplier', 'vendor', 'employee'])) {
            $vendor = Vendor::firstOrCreate([
                'company_id' => 1,
                'name'       => $name,
            ], [
                'type' => 'company', 
            ]);
            $vendorId = $vendor->id;
        } else {
            // fallback = client
            $client = MyClient::firstOrCreate([
                'company_id' => 1,
                'name'       => $name,
            ]);
            $clientId = $client->id;
        }

        return [$vendorId, $clientId];
    }
}
