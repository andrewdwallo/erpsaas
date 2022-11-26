![ERPSAAS create account](https://user-images.githubusercontent.com/104294090/204077432-5431ab48-d0ba-448c-99f3-1fe2c8e8cfb8.png)
![ERPSAAS create account 2](https://user-images.githubusercontent.com/104294090/204077431-0c47d44f-9e32-484e-93b0-adbf258c0f5a.png)
![ERPSAAS Profile New](https://user-images.githubusercontent.com/104294090/204077433-9121b495-033c-45d0-8fce-2d1903b67670.png)
![ERPSAAS users](https://user-images.githubusercontent.com/104294090/204077434-ddae02ed-91ad-4310-9a8f-9a4b3147b5c2.png)
![10](https://user-images.githubusercontent.com/104294090/198823691-dd503f53-0ff0-4c24-b8f6-5c6f4f03c32a.png)
![11](https://user-images.githubusercontent.com/104294090/198823738-48abf5de-e5ff-4bd7-9ecd-8eeafa24ed2d.png)
![13](https://user-images.githubusercontent.com/104294090/198823891-4153e2fe-d516-4ee8-bd8f-e78c97acd316.png)
![CompaniesPageGithub1](https://user-images.githubusercontent.com/104294090/193767442-13fec3f6-fd24-4057-87b2-5d3352d42af4.png)
![DepartmentsPageGithub1](https://user-images.githubusercontent.com/104294090/193767444-218ff1b4-8eb6-4b4e-84be-72f040601052.png)
![EmployeesPageGithub1](https://user-images.githubusercontent.com/104294090/193767445-3207f1fc-e79a-42a3-99e8-93e645def04b.png)
![BanksPageGithub1](https://user-images.githubusercontent.com/104294090/193767439-eca66f6e-23d6-443e-bd09-f2d2fb92dc9b.png)
![AccountsPageGithub1](https://user-images.githubusercontent.com/104294090/193767436-0bff8d27-03e9-4c06-81b6-b9c90eb69919.png)
![CardsPageGithub1](https://user-images.githubusercontent.com/104294090/193767440-6da9c416-d227-489f-959d-e3ec2d7be17a.png)
![IncomesTransactionPageGithub1](https://user-images.githubusercontent.com/104294090/193767450-a6b19f9c-e9bd-4b41-83ed-2aeec5b8be0a.png)
![ExpensesTransactionPage1](https://user-images.githubusercontent.com/104294090/193767448-00fb0433-6d97-4480-a6c6-0e64b156b45b.png)
![RevenueAccountPage1](https://user-images.githubusercontent.com/104294090/193767451-9d6d02b3-8041-4154-84a1-3e0a2a7d2398.png)
![ExpensesAccountPage1](https://user-images.githubusercontent.com/104294090/193767446-67bebb68-7fcb-4085-8d8e-90e0179a664c.png)

# ERPSAAS

### An ERP Software System using Filament: Currently a WIP

## Installation

1. Git clone:

```bash
git clone https://github.com/andrewdwallo/erpsaas.git
```

2. Cd into erpsaas directory

```bash
cd erpsaas
```

3. Install via composer: You will get an error that vite manifest cannot be found, just keep following instructions.
```bash
composer install
```

4. Install Dependencies: You can use one of either pnpm, npm, or yarn.

```bash
pnpm install
```

5. Build Manifest
```bash
pnpm run build
```

6. Copy .env.example and configure your database:
```bash
cp .env.example .env
```

7. Generate APP_KEY for Laravel:
```bash
php artisan key:generate
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
##### Note: I added the app/Policies folder to .gitignore, if you want to keep them remove it from .gitignore after finishing installation.
```bash
php artisan shield:install --fresh
```

11. Run Dev:
```bash
pnpm run dev
```

12. Follow the prompts, then login with your email and password at the following url or similar at your-url/admin:
```
https://erpsaas.test/admin 
```

13. In this order:
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
Create An Asset for Your Bank First with Account Name being your Bank Name (example. Bank of America) as a Current Asset
You can now Create an Asset, along with Liabilities, etc from the Dashboard Page (Chart of Accounts).
```
```
Create Bank(s) for Your Company/Companies and Departments
```
```
Create Account(s) for Your Bank(s)
```
```
Create Card(s) for Your Account(s)
```
```
Create Income & Expense Transaction(s) for Your Card(s)
```
```
Enjoy!
```

## Contributing
Please make suggestions if you don't want to contribute.
I plan on making this a full fledged ERP System with more than an accounting Module.
I plan on integrating Plaid for accounting automation in the future..
Please fork this repo and submit pull requests if you want to contribute!
