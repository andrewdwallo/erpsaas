<span>Are you sure you want to delete your {{ $institution->name }} connection? Deleting this bank connection will remove the following connected accounts:</span>
<ul class="list-disc list-inside p-2">
    @foreach($institution->connectedBankAccounts as $connectedBankAccount)
        <li>{{ $connectedBankAccount->name }}</li>
    @endforeach
</ul>
