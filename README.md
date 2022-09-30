![Screenshot 2022-09-30 082304](https://user-images.githubusercontent.com/104294090/193270027-6f932801-53d8-44b8-84cd-4e796fb5046a.png)
![Screenshot 2022-09-30 082341](https://user-images.githubusercontent.com/104294090/193270025-b41aaa3a-f00d-4d9d-a97d-97ca4ee32d4e.png)
![Screenshot 2022-09-30 082408](https://user-images.githubusercontent.com/104294090/193270023-d12f8f57-73e1-4a9d-bca5-eacf7b6631a1.png)
![Screenshot 2022-09-30 082444](https://user-images.githubusercontent.com/104294090/193270021-b792fb8d-fe77-47b2-8541-57f66ec7861a.png)
![Screenshot 2022-09-30 082530](https://user-images.githubusercontent.com/104294090/193270020-9003caee-0b95-4ad4-ac1c-7f2a20f259a6.png)
![Screenshot 2022-09-30 082555](https://user-images.githubusercontent.com/104294090/193270019-6f06ae46-d0ee-4c75-b418-9f3d5132a66d.png)
![Screenshot 2022-09-30 082717](https://user-images.githubusercontent.com/104294090/193270017-02620516-7a8d-490c-bc0c-028b8af7bd7a.png)
![Screenshot 2022-09-30 082641](https://user-images.githubusercontent.com/104294090/193270018-f88cff1a-5169-491b-9065-75fc9c375d18.png)
# ERPSAAS 

An ERP Software System Package for Filament:

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
Create Bank(s) for Your Company/Companies and Departments
```
```
Create Account(s) for Your Bank(s)
```
```
Create Card(s) for Your Account(s)
```
```
Create\Import Transaction(s) for Your Card(s)
```
```
Make Sure The Columns of Your .csv/.xlsx file are configured in correct format with the columns being on line 1.
Not All Options Have To Be Selected...
```
```
Enjoy!
```

## Contributing
Contributions are needed!!
I plan on making this a full fledged ERP System with more than an accounting Module.
I plan on integrating Plaid for accounting automation in the future..
Please fork this repo and submit pull requests!
