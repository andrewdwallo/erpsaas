# ERPSAAS 

A Multi-tenant SaaS ERP Software System Package for Filament:

## Installation

1. Git clone:

```bash
git clone https://github.com/andrewdwallo/erpsaas.git
```

2. Cd into erpsaas directory

```bash
cd erpsaas
```

3. Install via composer:
```bash
composer install
```
4. Copy .env.example and configure your database:
```bash
cp .env.example .env
```

5. Generate APP_KEY for Laravel:
```bash
php artisan key:generate
```

6. Install Dependencies via Yarn:
If yarn is not installed on your system globally install via npm:

```bash
npm install --global yarn
```

```bash
yarn install
```

7. Run Dev:
```bash
yarn run dev
```

8. IMPORTANT! Link your database (preferably mysql) to app storage in order to generate assets/images/csv files:
```bash
php artisan storage:link
```

9. Migrate the database tables to your DB:
```bash
php artisan migrate
```

10. Now run the following command to install shield (do --fresh just in case):
```bash
php artisan shield:install --fresh
```

11. Follow the prompts, then login with your email and password at the following url or similar at your-url/admin:
```
https://erpsaas.test/admin 
```

12. In this order:
```
Create A New Company (As Many As You Want)
```
```
Create Department(s) For Company/Companies
```
```
Create Employee(s) For Your Company/Companies
```
```
Go To Transactions and Import A .csv or .xlsx file of your transaction statement(s) for a selected company.
```
```
Make Sure The Columns of Your .csv/.xlsx file are configured in correct format with the columns being on line 1.
Not All Options Have To Be Selected...
```
```
Enjoy!
```
