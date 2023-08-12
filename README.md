# ERPSAAS

![Screenshot 2023-08-12 at 4 26 28 PM](https://github.com/andrewdwallo/erpsaas/assets/104294090/53538a06-38d1-41e6-888f-31960b0ffd8c)

This repo is currently a work in progress — PRs and issues welcome!

# Getting started

## Installation

Please check the official laravel installation guide for server requirements before you start. [Official Documentation](https://laravel.com/docs/10.x)

Clone the repository

    git clone https://github.com/andrewdwallo/erpsaas.git

Switch to the repo folder

    cd erpsaas

Install all the dependencies using composer and npm

    composer install
    npm install

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Generate a new application key

    php artisan key:generate

Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate

Build your dependencies & start the local development server

    npm run build
    npm run dev

You can now access the server at http://localhost:8000

**Command list**

    git clone https://github.com/andrewdwallo/erpsaas.git
    cd erpsaas
    composer install
    npm install
    cp .env.example .env
    php artisan key:generate
    php artisan migrate
    npm run build
    npm run dev

## Database seeding

**You may populate the database to help you get started quickly**

Open the DatabaseSeeder and set the property values as per your requirement

    database/seeders/DatabaseSeeder.php

Default login information:

    email: admin@gmail.com
    password: password

Run the database seeder

    php artisan db:seed

***Note*** : It's recommended to have a clean database before seeding. You can refresh your migrations at any point to clean the database by running the following command

    php artisan migrate:refresh

## Dependencies

- [filamentphp/filament](https://github.com/filamentphp/filament) - A collection of beautiful full-stack components
- [andrewdwallo/filament-companies](https://github.com/andrewdwallo/filament-companies) - A complete authentication system kit based on companies built for Filament
- [leandrocfe/filament-apex-charts](https://github.com/leandrocfe/filament-apex-charts) - Apex Charts integration for Filament
- [squirephp/squire](https://github.com/squirephp/squire) - A library of static Eloquent models for common fixture data

***Note*** : It is recommended to read the documentation for all dependencies to get yourself familiar with how the application works.
