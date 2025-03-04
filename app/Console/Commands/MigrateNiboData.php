<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
// IMPORT MODELS QUE SERÃO USADOS
use App\Models\Banking\BankAccount;
use App\Models\Accounting\Transaction;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Account;

// php artisan app:migrate-nibo-data

class MigrateNiboData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-nibo-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    // Ajuste conforme sua API
    private string $baseUrl = 'https://api.nibo.com.br/empresas/v1'; 
    private ?string $apiToken = null; // Carregado do .env ou config

    // Mapeamentos em memória: Nibo IDs => IDs do ERP
    private array $accountMap = [];    // Ex.: [ 'afeb336d-2a11-...' => 123 ]
    private array $scheduleMap = [];   // Ex.: [ '039679e2-2ba6-...' => bill_id ou invoice_id ]
    private array $stakeholderMap = []; // Ex.: [ 'b93729d0-c0b5-...' => client_id ou vendor_id, etc. ]
    private array $categoryMap = [];   // Ex.: [ 'b93729d0-c0b5-...' => category_id ]


    public function __construct()
    {
        parent::__construct();
        $this->apiToken = env('DB_CONNECTION', 'mysql');
        // Exemplo de buscar token do .env
        // (ou use config('services.nibo.token'), se preferir)
        $this->apiToken = env('Tk_B2BF');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando migração de dados do Nibo...');
        
    }
}
