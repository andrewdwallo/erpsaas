<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// MODELS
use App\Models\Company;
use App\Models\Banking\Institution;
use App\Models\Banking\BankAccount;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Common\Vendor;
use App\Models\Common\Client as MyClient;
use App\Models\User;

class MigrateNiboData extends Command
{
    protected $signature = 'app:migrate-nibo-data';
    protected $description = 'Migra dados do Nibo (vários endpoints) para o ERP';

    private string $baseUrl   = 'https://api.nibo.com.br/empresas/v1/';
    private ?string $apiToken = null;

    // Mapeamentos internos (Nibo ID => ID local)
    private array $organizationMap = [];
    private array $accountMap      = [];
    private array $scheduleMap     = [];
    private array $stakeholderMap  = [];
    private array $bankMap         = [];

    public function __construct()
    {
        parent::__construct();
        $this->apiToken = env('Tk_B2BF', 'chave_invalida');
    }

    public function handle()
    {
        $this->info('Iniciando migração de dados do Nibo...');

        $maps = [
            [
                'endpoint' => 'organizations',
                'table' => 'companies',
                'model' => Company::class,
                // Neste array, a chave (ex: 'organizationId') é a propriedade do JSON da API
                // e o valor (ex: 'nibo_org_id') é a coluna do DB
                'fields' => [
                    'organizationId'    => 'nibo_org_id',
                    'name'              => 'name',
                    'some_api_property' => 'personal_company', 
                    // ...
                ],
            ],
            [
                'endpoint' => 'users',
                'table' => 'users',
                'fields' => [
                    'id'    => 'nibo_user_id',
                    'name'  => 'name',
                    'email' => 'email',
                    // ...
                ],
            ],
            // quantos blocos você quiser...
        ];
        //$this->compareAndLogApiToDbColumns('erpsaas', $maps);
        // Chamada do método genérico
        $orgs = $this->fetchPaginated('organizations'); // Chamando o endpoint
        $this->compareApiKeysWithTableColumnsColorPro(
            tableName: 'companies',            // nome da tabela no DB
            apiItems:  $orgs ?? [],   // passa itens para pegar o primeiro.
            // quero fazer o bind de chaves de um endpoint para uma tabela
            //companies:   ['name' => 'organizationId', 'personal_company' => 'name'] // organizations
        );
            //users:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
            //paring:   [' ' => ' ', ' ' => ' '] // mapeamento de chaves :   =>  
       // );

        // // Se quiser transacionar tudo junto, mantendo atomicidade:
        // DB::transaction(function () {
        //     // 1. MIGRAR /organizations
        //     $this->migrateOrganizations();

        //     // 2. MIGRAR /users (se desejar mapear usuários do Nibo para algum modelo local)
        //     $this->migrateUsers();

        //     // 3. MIGRAR /banks (caso necessário para popular Institution ou algo similar)
        //     $this->migrateBanks();

        //     // 4. MIGRAR /accounts
        //     $this->migrateAccounts();

        //     // 5. MIGRAR /costcenters
        //     $this->migrateCostCenters();

        //     // 6. MIGRAR /categories
        //     $this->migrateCategories();

        //     // 7. MIGRAR /schedules (pagamentos, recebimentos, etc.)
        //     $this->migrateSchedules();

        //     // 8. MIGRAR /payments
        //     $this->migratePayments();

        //     // 9. MIGRAR /receipts
        //     $this->migrateReceipts();

        //     // 10. MIGRAR /accounts/transfer
        //     $this->migrateTransfers();

        //     // 11. MIGRAR /accounts/{accountId}/reconciliation
        //     $this->migrateReconciliation();

        //     // 12. MIGRAR /nfse, /nfse/serviceprofiles, etc., conforme necessidade
        //     // $this->migrateNfse();

        //     // ...
        // });

        $this->info('Migração concluída com sucesso!');
    }

    /** 
     * Exibe em tabela as colunas do DB e as keys do primeiro objeto da resposta da API.
     *
     * Exemplo de uso: compareApiKeysWithTableColumns('column', $apiObjectList, );
     * 
     * @param  string  $tableName  Nome da tabela do DB, ex.: 'companies'
     * @param  array   $apiItems   Array de itens da resposta da API
     */
    private function compareApiKeysWithTableColumns(string $tableName, array $apiItems): void
    {
        // Obtém as colunas da tabela
        $columns = Schema::getColumnListing($tableName);

        // Verifica se há itens na resposta da API
        if (empty($apiItems)) {
            $this->info("Nenhum item retornado da API para comparação:" );
            return;
        }

        // Obtém as keys do primeiro objeto da API
        $apiKeys = array_keys($apiItems[0]);

        // Determina o número máximo de linhas (para cobrir todos os elementos)
        $maxCount = max(count($columns), count($apiKeys));
        
        // Monta as linhas da tabela, exibindo lado a lado a coluna do DB e a key da API (se houver)
        $rows = [];
        for ($i = 0; $i < $maxCount; $i++) {
            $dbColumn = $columns[$i] ?? '';   // se não existir, deixa em branco
            $apiKey   = $apiKeys[$i]   ?? '';   // se não existir, deixa em branco
            $rows[]   = [$dbColumn, $apiKey];
        }

        // Exibe a tabela comparativa
        $this->table(["DB Column: $tableName", "API Key (primeiro objeto)"], $rows);

        // Opcional: exibe as keys extras que estão na API e não na tabela
        $extraKeys = array_diff($apiKeys, $columns);
        if (!empty($extraKeys)) {
            $this->info("Chaves extras da API não existentes em '$tableName': " . implode(', ', $extraKeys));
        }
    }

    /** 
     * Exibe em tabela as colunas do DB e as keys do primeiro objeto da resposta da API.
     * 
     * @param  string  $tableName  Nome da tabela do DB, ex.: 'companies'
     * @param  array   $apiItems   Array de itens da resposta da API
     */
    private function compareApiKeysWithTableColumnsColor(string $tableName, array $apiItems): void
    {
        // Obtém as colunas da tabela via Schema
        $columns = Schema::getColumnListing($tableName);

        // Verifica se há itens na resposta da API
        if (empty($apiItems)) {
            $this->info("Nenhum item retornado da API para comparação.");
            return;
        }

        // Obtém as keys do primeiro objeto da API
        $apiKeys = array_keys($apiItems[0]);

        // Exemplo de mapeamento: se a tabela for 'companies', obtemos os fillable do model Company
        $fillable = [];
        if ($tableName === 'companies') {
            $modelClass = \App\Models\Company::class;
            $fillable = (new $modelClass)->getFillable();
        }

        // Determina o número máximo de linhas (para cobrir todos os elementos)
        $maxCount = max(count($columns), count($apiKeys));

        // Monta as linhas da tabela, exibindo lado a lado a coluna do DB e a key da API (se houver)
        $rows = [];
        for ($i = 0; $i < $maxCount; $i++) {
            $dbColumn = $columns[$i] ?? '';
            // Se a coluna for fillable, marca com formatação de cor verde
            if (in_array($dbColumn, $fillable)) {
                $dbColumn = "<fg=green>{$dbColumn}</>";
            }
            $apiKey = $apiKeys[$i] ?? '';
            $rows[] = [$dbColumn, $apiKey];
        }

        // Exibe a tabela comparativa
        $this->table(["DB Column: $tableName", "API Key (primeiro objeto)"], $rows);

        // Opcional: exibe as keys extras que estão na API e não na tabela
        $extraKeys = array_diff($apiKeys, $columns);
        if (!empty($extraKeys)) {
            $this->info("Chaves extras da API não existentes em '$tableName': " . implode(', ', $extraKeys));
        }
    }

    /**
    * Exibe em tabela as colunas do DB e as keys do primeiro objeto da resposta da API,
    * destacando em verde as colunas que são fillable.
    *
    * @param  string  $tableName  Nome da tabela do DB, ex.: 'companies'
    * @param  array   $apiItems   Array de itens da resposta da API
    */
    private function compareApiKeysWithTableColumnsColorPro(string $tableName, array $apiItems): void
    {
        // Obtém as colunas da tabela via Schema
        $columns = Schema::getColumnListing($tableName);

        // Verifica se há itens na resposta da API
        if (empty($apiItems)) {
            $this->info("Nenhum item retornado da API para comparação.");
            return;
        }

        // Obtém as keys do primeiro objeto da API
        $apiKeys = array_keys($apiItems[0]);

        // Lista completa de models baseada na estrutura do diretório /app/Models
        $modelClasses = [
            // Accounting
            \App\Models\Accounting\Account::class,
            \App\Models\Accounting\AccountSubtype::class,
            \App\Models\Accounting\Adjustment::class,
            \App\Models\Accounting\Bill::class,
            \App\Models\Accounting\Document::class,
            \App\Models\Accounting\DocumentLineItem::class,
            \App\Models\Accounting\Estimate::class,
            \App\Models\Accounting\Invoice::class,
            \App\Models\Accounting\JournalEntry::class,
            \App\Models\Accounting\RecurringInvoice::class,
            \App\Models\Accounting\Transaction::class,
            // Banking
            \App\Models\Banking\BankAccount::class,
            \App\Models\Banking\ConnectedBankAccount::class,
            \App\Models\Banking\Institution::class,
            // Common
            \App\Models\Common\Address::class,
            \App\Models\Common\Client::class,
            \App\Models\Common\Contact::class,
            \App\Models\Common\Offering::class,
            \App\Models\Common\Vendor::class,
            // Diretório raiz
            \App\Models\Company::class,
            \App\Models\CompanyInvitation::class,
            \App\Models\ConnectedAccount::class,
            \App\Models\Employeeship::class,
            \App\Models\User::class,
            // Core
            \App\Models\Core\Department::class,
            // Locale
            \App\Models\Locale\City::class,
            \App\Models\Locale\Country::class,
            \App\Models\Locale\Currency::class,
            \App\Models\Locale\State::class,
            // Service
            \App\Models\Service\CurrencyList::class,
            // Setting
            \App\Models\Setting\CompanyDefault::class,
            \App\Models\Setting\CompanyProfile::class,
            \App\Models\Setting\Currency::class,
            \App\Models\Setting\DocumentDefault::class,
            \App\Models\Setting\Localization::class,
        ];

        // Agrega os campos fillable dos models que correspondem à tabela
        $fillable = [];
        
        foreach ($modelClasses as $modelClass) {
            $modelInstance = new $modelClass;
            //dd($modelInstance instanceof Account);//true
            // Verifica se o nome da tabela do model é igual a $tableName
            if ($modelInstance->getTable() === $tableName) {
                // Mescla os fillables (evitando duplicatas, se necessário)
                $fillable = array_unique(array_merge($fillable, $modelInstance->getFillable()));
            }
        }

        // Determina o número máximo de linhas para cobrir todos os elementos
        $maxCount = max(count($columns), count($apiKeys));

        // Monta as linhas da tabela, exibindo lado a lado a coluna do DB e a key da API (se houver)
        $rows = [];
        for ($i = 0; $i < $maxCount; $i++) {
            $dbColumn = $columns[$i] ?? '';
            // Se a coluna estiver entre os fillables, marca com formatação de cor verde
            if (in_array($dbColumn, $fillable)) {
                $dbColumn = "<fg=green>{$dbColumn}</>";
            }
            $apiKey = $apiKeys[$i] ?? '';
            $rows[] = [$dbColumn, $apiKey];
        }

        // Exibe a tabela comparativa
        $this->table(["DB Column: $tableName", "API Key (primeiro objeto)"], $rows);

        // Opcional: exibe as keys extras que estão na API e não na tabela
        $extraKeys = array_diff($apiKeys, $columns);
        if (!empty($extraKeys)) {
            $this->info("Chaves extras da API não existentes em '$tableName': " . implode(', ', $extraKeys));
        }
    }


    /**
     * Compara as chaves de um endpoint da API com as colunas de uma tabela do DB.
     * 
     * @param  string  $databaseName  Nome da conexão do DB, ex.: 'erpsaas'
     * @param  array   $endpointTableMappings  Array de mapeamentos de endpoint para tabela
     */
    private function compareAndLogApiToDbColumns(
        string $databaseName,
        array $endpointTableMappings
    ): void
        {
        // Percorre cada mapeamento
        foreach ($endpointTableMappings as $mapping) {
            // 1) Extrai as informações do array
            $endpoint   = $mapping['endpoint'] ?? null;
            $tableName  = $mapping['table']    ?? null;
            $fieldsMap  = $mapping['fields']   ?? [];

            if (!$endpoint || !$tableName) {
                $this->error("Mapping inválido ou incompleto. Pulei este bloco.");
                continue;
            }

            // 2) Chama a API (pode usar fetchPaginated ou fetchSimple, conforme o caso)
            $this->info("Buscando dados em /$endpoint para comparar com a tabela '$tableName'...");
            $items = $this->fetchPaginated($endpoint); 
            // $items deveria ser um array com “items” ou “data”. 
            // Ajuste se o retorno da sua API for diferente:
            //   $items = $items['items'] ?? [];

            // 3) Obtém as colunas do DB (usando a conexão e tabela informadas)
            $dbColumns = Schema::connection($databaseName)->getColumnListing($tableName);

            // 4) Para cada item retornado pela API, vamos exibir uma tabela comparativa
            foreach ($items as $idx => $item) {
                // Cabeçalho apenas ilustrativo
                $this->info("-------------------------------------------------------------------------------------");
                $this->info("Comparando item #$idx de '/$endpoint' com colunas de '$tableName'...");

                // Monta um array de linhas para exibir de forma tabular:
                // Cada linha terá:
                //   1. Nome do campo da API
                //   2. Valor do item
                //   3. Qual coluna do DB que mapeia (se existir)
                //   4. Indica se essa coluna de fato existe na tabela
                $rows = [];
                foreach ($fieldsMap as $apiKey => $dbCol) {
                    $apiValue   = $item[$apiKey] ?? 'N/A';
                    $columnExists = in_array($dbCol, $dbColumns, true) ? 'SIM' : 'NÃO';
                    
                    $rows[] = [
                        "API Field: $apiKey",
                        "Value: $apiValue",
                        "DB Column: $dbCol",
                        "Existe na tabela? $columnExists"
                    ];
                }

                // Exibe em formato de tabela
                $this->table(
                    ['API Field', 'Valor da API', 'DB Column', 'Existe?'],
                    $rows
                );

                // (Opcional) detectar colunas do DB que não foram mapeadas:
                //   $unmapped = array_diff($dbColumns, array_values($fieldsMap));
                //   if (!empty($unmapped)) {
                //       $this->info("Essas colunas do DB não têm correspondência no fieldsMap: " . implode(', ', $unmapped));
                //   }

                // (Opcional) detectar campos da API que não estão no $fieldsMap
                //   $extraApiFields = array_diff(array_keys($item), array_keys($fieldsMap));
                //   if (! empty($extraApiFields)) {
                //       $this->info("Estas chaves da API não foram mapeadas em 'fields': " . implode(', ', $extraApiFields));
                //   }
            }
        }
    }

    /**
     * EXEMPLO: /organizations => Tabela "companies"
     */
    private function migrateOrganizations()
    {
        $this->info('> Buscando /organizations...');
        $endpoint = 'organizations';
        $data = $this->fetchSimple($endpoint);

        foreach ($data['items'] ?? [] as $org) {
            $orgId   = $org['organizationId'];
            $orgName = $org['name'] ?? 'Sem nome';
            $cnpj    = $org['cnpj'] ?? null;

            // Exemplo de persistência
            $company = Company::firstOrCreate(
                ['nibo_org_id' => $orgId], // campo adicional na sua tabela companies
                [
                    'name' => $orgName,
                    // 'cnpj' => $cnpj, // se houver campo
                ]
            );

            $this->organizationMap[$orgId] = $company->id;
        }
    }

    /**
     * EXEMPLO: /users => Tabela "users" ou "employees" etc.
     * Ajuste conforme a necessidade do seu projeto.
     */
    private function migrateUsers()
    {
        $this->info('> Buscando /users...');
        $endpoint = 'users';
        $data = $this->fetchPaginated($endpoint);

        foreach ($data as $userData) {
            $niboUserId = $userData['id'] ?? null; 
            if (!$niboUserId) {
                continue;
            }

            // Exemplo de persistência
            $user = User::firstOrCreate(
                // Ajuste conforme as colunas que você criou
                ['nibo_user_id' => $niboUserId],
                [
                    'name'  => $userData['name']  ?? 'Sem Nome',
                    'email' => $userData['email'] ?? null,
                    // ...
                ]
            );
            // Se quiser guardar mapping
            // $this->someArrayMap[$niboUserId] = $user->id;
        }
    }

    /**
     * EXEMPLO: /banks => Tabela "institutions" (similar a MIGRAÇÃO DE BALANCES)
     */
    private function migrateBanks()
    {
        $this->info('> Migrando /banks...');
        $endpoint = 'banks';
        $banks = $this->fetchSimple($endpoint);

        foreach ($banks['items'] ?? [] as $bk) {
            $bankId = $bk['bankId'] ?? null;
            $name   = $bk['name']   ?? 'Banco Sem Nome';

            $institution = Institution::firstOrCreate(
                ['nibo_bank_id' => $bankId],
                ['name' => $name]
            );

            $this->bankMap[$bankId] = $institution->id;
        }
    }

    /**
     * EXEMPLO: /accounts => Tabela "bank_accounts" e "accounts" contábeis
     * (Pode ser diferente de /accounts/balance, tudo depende de como a API do Nibo retorna)
     */
    private function migrateAccounts()
    {
        $this->info('> Migrando /accounts...');
        $endpoint = 'accounts';
        $accounts = $this->fetchPaginated($endpoint);

        foreach ($accounts as $acc) {
            $niboAccountId = $acc['id'] ?? null;
            if (!$niboAccountId) {
                continue;
            }
            $accountName = $acc['accountName'] ?? 'Conta Desconhecida';

            // 1) Mapeia banco
            $bankId = $acc['bank']['id'] ?? null;
            $institutionId = $this->bankMap[$bankId] ?? null;

            // 2) Localizar/criar a Account contábil
            $account = Account::firstOrCreate(
                [
                    'company_id' => 1,
                    'name'       => $accountName,
                ],
                [
                    'type'        => 'asset',  
                    'description' => 'Conta importada /accounts Nibo'
                ]
            );

            // 3) Localizar/criar BankAccount
            $bankAccount = BankAccount::firstOrCreate(
                [
                    'company_id' => 1,
                    'account_id' => $account->id,
                ],
                [
                    'institution_id' => $institutionId,
                    'number'         => $acc['accountNumber'] ?? '',
                    'type'           => 'depository',
                    'enabled'        => true,
                ]
            );

            $this->accountMap[$niboAccountId] = $bankAccount->id;
        }
    }

    /**
     * EXEMPLO: /accounts/transfer
     */
    private function migrateTransfers()
    {
        $this->info('> Migrando /accounts/transfer...');
        $endpoint = 'accounts/transfer';
        $data = $this->fetchPaginated($endpoint);

        foreach ($data as $transfer) {
            // Exemplo: 
            $fromAccountId = $transfer['from']['id'] ?? null;
            $toAccountId   = $transfer['to']['id']   ?? null;
            $amount        = $transfer['amount']     ?? 0;
            $date          = $transfer['transferDate'] ?? null;

            $erpFromId = $this->accountMap[$fromAccountId] ?? null;
            $erpToId   = $this->accountMap[$toAccountId]   ?? null;
            if (!$erpFromId || !$erpToId) {
                continue;
            }

            // Você poderia criar duas Transactions (saída e entrada),
            // ou uma "transferência" interna, dependendo da sua modelagem.
            // Exemplo simplificado:
            Transaction::create([
                'company_id'      => 1,
                'bank_account_id' => $erpFromId,
                'type'            => 'withdrawal',
                'description'     => 'Transferência de conta X p/ conta Y',
                'posted_at'       => $this->toDate($date),
                'amount'          => $this->toInt($amount),
            ]);

            Transaction::create([
                'company_id'      => 1,
                'bank_account_id' => $erpToId,
                'type'            => 'deposit',
                'description'     => 'Transferência recebida de conta X',
                'posted_at'       => $this->toDate($date),
                'amount'          => $this->toInt($amount),
            ]);
        }
    }

    /**
     * EXEMPLO: /accounts/{id}/reconciliation
     */
    private function migrateReconciliation()
    {
        $this->info('> Migrando conciliações /accounts/{accountId}/reconciliation...');
        foreach ($this->accountMap as $niboAccountId => $erpBankAccountId) {
            $endpoint = "accounts/{$niboAccountId}/reconciliation";
            $data = $this->fetchSimple($endpoint);

            foreach ($data['items'] ?? [] as $row) {
                // Você pode criar/atualizar transações com base nos dados retornados 
                // (como "description", "amount", "isReconciliated", etc.)
                // Este passo varia muito conforme a regra que você pretende adotar.
            }
        }
    }

    /**
     * EXEMPLO: /costcenters => Tabela "departments" ou alguma "cost_centers"
     */
    private function migrateCostCenters()
    {
        $this->info('> Migrando /costcenters...');
        $endpoint = 'costcenters';
        $data = $this->fetchPaginated($endpoint);

        foreach ($data as $row) {
            // Exemplo: name, code, etc.
            // $department = Department::firstOrCreate(
            //     ['nibo_cc_id' => $row['id']],
            //     ['name' => $row['name']]
            // );
        }
    }

    /**
     * EXEMPLO: /categories => Tabela "categories" local
     */
    private function migrateCategories()
    {
        $this->info('> Migrando /categories...');
        $endpoint = 'categories';
        $data = $this->fetchPaginated($endpoint);

        // Idem, varia conforme sua tabela de categorias. 
        foreach ($data as $cat) {
            // ...
        }
    }

    /**
     * MIGRA /schedules (igual ao exemplo original)
     */
    private function migrateSchedules()
    {
        $this->info('> Migrando /schedules...');
        $endpoint = 'schedules';
        $items = $this->fetchPaginated($endpoint);

        foreach ($items as $item) {
            $scheduleId = $item['scheduleId'] ?? null;
            if (!$scheduleId) continue;

            $isCredit = ($item['type'] === 'Credit');
            $isPaid   = (bool) ($item['isPaid'] ?? false);

            [$vendorId, $clientId] = $this->resolveStakeholder($item['stakeholder'] ?? null);

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

    /**
     * MIGRA /payments (igual ao exemplo original)
     */
    private function migratePayments()
    {
        $this->info('> Migrando /payments...');
        $endpoint = 'payments';
        $items = $this->fetchPaginated($endpoint);

        foreach ($items as $item) {
            $scheduleId         = $item['scheduleId'] ?? null;
            $niboBankAccountId  = $item['account']['id'] ?? null;
            $value              = $this->toInt($item['value'] ?? 0);

            $erpBankAccountId = $this->accountMap[$niboBankAccountId] ?? null;
            if (!$erpBankAccountId) continue;

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

            // Ajusta Bill/Invoice vinculado
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

    /**
     * MIGRA /receipts (igual ao exemplo original)
     */
    private function migrateReceipts()
    {
        $this->info('> Migrando /receipts...');
        $endpoint = 'receipts';
        $items = $this->fetchPaginated($endpoint);

        foreach ($items as $item) {
            $scheduleId         = $item['scheduleId'] ?? null;
            $niboBankAccountId  = $item['account']['id'] ?? null;
            $value              = $this->toInt($item['value'] ?? 0);

            $erpBankAccountId = $this->accountMap[$niboBankAccountId] ?? null;
            if (!$erpBankAccountId) continue;

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

    // Se quiser migrar Notas Fiscais de Serviço
    // private function migrateNfse() {
    //     // ...
    // }

    /* =====================================================================
       MÉTODOS DE EXTRAÇÃO (AUXILIARES)
    ====================================================================== */

    /**
     * GET simples sem paginação
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
     * GET com paginação
     */
    private function fetchPaginated(
        string $endpoint,
        array $query = [],
        string $orderby = null,
        int $top = 500
    ): array
    {
        $client = new Client([
            'base_uri' => $this->baseUrl,
            'headers'  => [
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
                '$top'  => $top,
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
                // Se não seguir esse padrão, adapte aqui
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

    /* =====================================================================
       FUNÇÕES DE TRANSFORMAÇÃO / AJUDA
    ====================================================================== */

    private function toDate(?string $dateStr)
    {
        if (!$dateStr) {
            return null;
        }
        return \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
    }

    private function toInt(float $value)
    {
        return (int) round($value * 100);
    }

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
