<div class="space-y-4">
    <div class="bg-blue-50 p-4 rounded-lg">
        <h3 class="font-semibold text-blue-800 mb-2">IBAN vs QR-IBAN Modi</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <h4 class="font-medium text-blue-800">IBAN-Modus</h4>
                <p class="text-sm text-blue-700">Normale IBAN ohne automatische Referenz. Details werden pro Rechnung individuell eingegeben.</p>
            </div>
            <div>
                <h4 class="font-medium text-blue-800">QR-IBAN-Modus</h4>
                <p class="text-sm text-blue-700">QR-IBAN mit automatischer Referenz-Generierung nach konfigurierbarem Schema.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="border rounded-lg p-4">
            <h4 class="font-semibold text-green-700 mb-2">✅ QR-IBAN Beispiele</h4>
            <div class="space-y-2 font-mono text-sm">
                <div class="bg-green-50 p-2 rounded">CH44 3199 9123 0008 8901 2</div>
                <div class="bg-green-50 p-2 rounded">CH93 3080 8001 2345 6789 0</div>
                <div class="bg-green-50 p-2 rounded">CH09 3000 0001 6505 1234 7</div>
            </div>
            <p class="text-sm text-green-600 mt-2">
                → Erzeugt automatisch QR-Referenzen (27-stellig)
            </p>
        </div>

        <div class="border rounded-lg p-4">
            <h4 class="font-semibold text-blue-700 mb-2">📄 Normale IBAN Beispiele</h4>
            <div class="space-y-2 font-mono text-sm">
                <div class="bg-blue-50 p-2 rounded">CH93 0076 2011 6238 5295 7</div>
                <div class="bg-blue-50 p-2 rounded">CH35 0900 0000 3066 3817 2</div>
                <div class="bg-blue-50 p-2 rounded">CH18 0077 4010 3947 4200 0</div>
            </div>
            <p class="text-sm text-blue-600 mt-2">
                → Erzeugt ISO11649 SCOR-Referenzen (RF...)
            </p>
        </div>
    </div>

    <div class="bg-yellow-50 p-4 rounded-lg">
        <h4 class="font-semibold text-yellow-800 mb-2">🔍 Wie erkenne ich eine QR-IBAN?</h4>
        <p class="text-yellow-700">
            Schauen Sie sich die Stellen 5-9 Ihrer IBAN an (nach dem Ländercode CH):
        </p>
        <div class="mt-2 font-mono text-sm">
            <span class="text-gray-500">CH44</span> 
            <span class="bg-yellow-200 px-1 rounded font-bold">31999</span>
            <span class="text-gray-500">123 0008 8901 2</span>
        </div>
        <p class="text-yellow-700 text-sm mt-1">
            Wenn diese 5 Ziffern zwischen 30000-31999 liegen, ist es eine QR-IBAN.
        </p>
    </div>

    <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-600">
        <p><strong>Tipp:</strong> Sie können Leerzeichen in der IBAN eingeben - diese werden automatisch entfernt.</p>
        <p><strong>Hinweis:</strong> Fragen Sie Ihre Bank nach einer QR-IBAN, wenn Sie strukturierte Zahlungsreferenzen benötigen.</p>
    </div>
</div>