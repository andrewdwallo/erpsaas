<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        <meta name="application-name" content="{{ config('app.name') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        <!-- Styles -->
        <style>[x-cloak] { display: none !important; }</style>
        @livewireStyles
        @vite('resources/css/app.css')
        @vite('resources/css/filament.css')

        <!-- Scripts -->
        @livewireScripts
        @vite('resources/js/app.js')
        @stack('scripts')
    </head>

    <body class="antialiased">
        {{ $slot }}

        @livewire('notifications')
    </body>
</html>
