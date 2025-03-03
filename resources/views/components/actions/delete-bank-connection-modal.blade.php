<span>Are you sure you want to delete your {{ $institution->name }} connection? Deleting this bank connection will remove the following connected accounts:</span>
<ul class="list-disc list-inside p-4">
    @foreach($institution->connectedBankAccounts as $connectedBankAccount)
        <li>{{ $connectedBankAccount->name }}</li>
    @endforeach
</ul>
<span>Deleting this bank connection will stop the import of transactions for all accounts associated with this bank. Existing transactions will remain unchanged.</span>
