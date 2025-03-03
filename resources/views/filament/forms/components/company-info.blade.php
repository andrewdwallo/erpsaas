<div class="text-xs text-right">
    <h2 class="text-base font-semibold">{{ $company_name }}</h2>
    @if($company_address && $company_city && $company_state && $company_zip)
        <p>{{ $company_address }}</p>
        <p>{{ $company_city }}, {{ $company_state }} {{ $company_zip }}</p>
        <p>{{ $company_country }}</p>
    @endif
</div>
