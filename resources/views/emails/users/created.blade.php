{{-- blade-formatter-disable --}}
@component('mail::message')
# Welcome, {{ $name }}

You've been registered as a new user on {{ config('app.name') }}.

@component('mail::button', ['url' => $link])
    Get Started
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
{{-- blade-formatter-enable --}}
