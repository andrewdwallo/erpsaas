<div class="space-y-4">
    <div>
        <h3 class="text-lg font-semibold mb-2">Verfügbare Platzhalter</h3>
        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
            <div><code class="bg-gray-200 px-2 py-1 rounded">{invoice_id}</code> - Eindeutige ID der Rechnung (z.B. 123)</div>
            <div><code class="bg-gray-200 px-2 py-1 rounded">{invoice_number}</code> - Rechnungsnummer (z.B. INV-1001)</div>
            <div><code class="bg-gray-200 px-2 py-1 rounded">{account_number}</code> - Konto-/Kundennummer (z.B. K-12345)</div>
            <div><code class="bg-gray-200 px-2 py-1 rounded">{client_name}</code> - ❌ Nicht für QR-Referenz geeignet (nur Zahlen erlaubt)</div>
            <div><code class="bg-gray-200 px-2 py-1 rounded">{date_y}</code> - Jahr der Rechnung (z.B. 2023)</div>
            <div><code class="bg-gray-200 px-2 py-1 rounded">{date_m}</code> - Monat der Rechnung (z.B. 10)</div>
            <div><code class="bg-gray-200 px-2 py-1 rounded">{date_d}</code> - Tag der Rechnung (z.B. 15)</div>
        </div>
    </div>
    
    <div>
        <h3 class="text-lg font-semibold mb-2">Beispiele</h3>
        <div class="bg-blue-50 p-4 rounded-lg space-y-2">
            <div><strong>{invoice_id}</strong> → 123</div>
            <div><strong>{invoice_number}</strong> → 1001 (nur Zahlen extrahiert)</div>
            <div><strong>{account_number}{invoice_id}</strong> → 1212123 (nur Zahlen)</div>
            <div><strong>{date_y}{date_m}{invoice_id}</strong> → 202310123</div>
            <div><strong>{date_y}{date_m}{date_d}{invoice_id}</strong> → 20231015123</div>
        </div>
    </div>
    
    <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
        <p class="text-sm text-yellow-800">
            <strong>Hinweis:</strong> Das Schema wird für jede Rechnung automatisch ausgewertet und eine QR-Referenz mit Prüfziffer generiert.
        </p>
    </div>
    
    <div class="bg-orange-50 border border-orange-200 p-4 rounded-lg">
        <p class="text-sm text-orange-800">
            <strong>Wichtig:</strong> {account_number} und {client_name} sind nur verfügbar, wenn diese Felder beim Kunden gepflegt sind. Leere Werte werden ignoriert.
        </p>
    </div>
</div>