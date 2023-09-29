# ERPSAAS

![Screenshot 2023-09-17 at 5 31 58 PM](https://github.com/andrewdwallo/erpsaas/assets/104294090/647333c0-978c-4e24-92a8-30ee4932c092)

![Screenshot 2023-09-17 at 5 29 08 PM](https://github.com/andrewdwallo/erpsaas/assets/104294090/121326e6-8e43-4c4c-8f5b-650fc159b2d0)

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

While your application is running, run the database seeder

    php artisan db:seed

***Note*** : It's recommended to have a clean database before seeding. You can refresh your migrations at any point to clean the database by running the following command

    php artisan migrate:refresh

## Dependencies

- [filamentphp/filament](https://github.com/filamentphp/filament) - A collection of beautiful full-stack components
- [andrewdwallo/filament-companies](https://github.com/andrewdwallo/filament-companies) - A complete authentication system kit based on companies built for Filament
- [akaunting/laravel-money](https://github.com/akaunting/laravel-money) - Currency formatting and conversion package for Laravel
- [rinvex/countries](https://github.com/rinvex/countries) - A simple and lightweight package for retrieving country details with flexibility.

***Note*** : It is recommended to read the documentation for all dependencies to get yourself familiar with how the application works.
