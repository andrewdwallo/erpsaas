<?php

return [
    'client_id' => env('PLAID_CLIENT_ID'),
    'client_secret' => env('PLAID_CLIENT_SECRET'),
    'environment' => env('PLAID_ENVIRONMENT', 'sandbox'),
    'webhook_url' => env('PLAID_WEBHOOK_URL'), // Example: https://my-static-domain.ngrok-free.app/api/plaid/webhook
];
