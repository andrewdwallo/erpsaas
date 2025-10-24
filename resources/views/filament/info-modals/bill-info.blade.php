<div class="space-y-4">
    <div class="bg-blue-50 p-4 rounded-lg">
        <h3 class="font-semibold text-blue-800 mb-2">Was sind Rechnungsinformationen?</h3>
        <p class="text-blue-700">
            Strukturierte Daten zur automatischen Buchung in ERP-Systemen. Diese werden nicht mit der Zahlung übertragen, 
            sondern dienen der internen Verarbeitung und können als QR-Code gescannt werden.
        </p>
    </div>

    <div class="space-y-4">
        <h4 class="font-semibold">🏗️ Aufbau der strukturierten Daten:</h4>
        
        <div class="bg-gray-50 p-4 rounded-lg font-mono text-xs">
            <div class="mb-2 text-gray-600">Format: //S1/Feld/Wert/Feld/Wert...</div>
            <div class="text-sm">
                <span class="bg-blue-200 px-1 rounded">//S1</span>/
                <span class="bg-green-200 px-1 rounded">10</span>/
                <span class="bg-yellow-200 px-1 rounded">10201409</span>/
                <span class="bg-green-200 px-1 rounded">11</span>/
                <span class="bg-yellow-200 px-1 rounded">190512</span>/
                <span class="bg-green-200 px-1 rounded">20</span>/
                <span class="bg-yellow-200 px-1 rounded">1400.000-53</span>
            </div>
            <ul class="mt-2 text-xs text-gray-600 space-y-1">
                <li><span class="bg-blue-200 px-1 rounded">S1</span> = Syntax Version 1</li>
                <li><span class="bg-green-200 px-1 rounded">10</span> = Rechnungsnummer</li>
                <li><span class="bg-green-200 px-1 rounded">11</span> = Rechnungsdatum (YYMMDD)</li>
                <li><span class="bg-green-200 px-1 rounded">20</span> = Kundennummer</li>
            </ul>
        </div>
    </div>

    <div class="space-y-3">
        <h4 class="font-semibold">📋 Häufige Felder und Beispiele:</h4>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border border-gray-300 px-3 py-2 text-left">Feld</th>
                        <th class="border border-gray-300 px-3 py-2 text-left">Beschreibung</th>
                        <th class="border border-gray-300 px-3 py-2 text-left">Beispiel</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 font-mono">10</td>
                        <td class="border border-gray-300 px-3 py-2">Rechnungsnummer</td>
                        <td class="border border-gray-300 px-3 py-2 font-mono">INV-2023-001</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 font-mono">11</td>
                        <td class="border border-gray-300 px-3 py-2">Rechnungsdatum (YYMMDD)</td>
                        <td class="border border-gray-300 px-3 py-2 font-mono">231013</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 font-mono">20</td>
                        <td class="border border-gray-300 px-3 py-2">Kundennummer</td>
                        <td class="border border-gray-300 px-3 py-2 font-mono">K-12345</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 font-mono">30</td>
                        <td class="border border-gray-300 px-3 py-2">Debitorennummer</td>
                        <td class="border border-gray-300 px-3 py-2 font-mono">106017086</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 font-mono">31</td>
                        <td class="border border-gray-300 px-3 py-2">Fälligkeitsdatum (YYMMDD)</td>
                        <td class="border border-gray-300 px-3 py-2 font-mono">231113</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 font-mono">32</td>
                        <td class="border border-gray-300 px-3 py-2">MwSt-Satz</td>
                        <td class="border border-gray-300 px-3 py-2 font-mono">7.7</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 font-mono">40</td>
                        <td class="border border-gray-300 px-3 py-2">Zahlungskonditionen</td>
                        <td class="border border-gray-300 px-3 py-2 font-mono">2:10;0:30</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-3">
        <h4 class="font-semibold">✨ Vollständige Beispiele:</h4>
        
        <div class="space-y-3">
            <div class="bg-green-50 p-3 rounded border-l-4 border-green-400">
                <h5 class="font-semibold text-green-800 text-sm">Einfache Rechnung:</h5>
                <div class="font-mono text-xs bg-white p-2 rounded mt-1 break-all">
                    //S1/10/INV-2023-001/11/231013/20/K-12345
                </div>
            </div>
            
            <div class="bg-green-50 p-3 rounded border-l-4 border-green-400">
                <h5 class="font-semibold text-green-800 text-sm">Mit MwSt und Fälligkeit:</h5>
                <div class="font-mono text-xs bg-white p-2 rounded mt-1 break-all">
                    //S1/10/R-2023-0456/11/231013/20/1400.000-53/31/231113/32/7.7
                </div>
            </div>
            
            <div class="bg-green-50 p-3 rounded border-l-4 border-green-400">
                <h5 class="font-semibold text-green-800 text-sm">Komplett mit Zahlungskonditionen:</h5>
                <div class="font-mono text-xs bg-white p-2 rounded mt-1 break-all">
                    //S1/10/10201409/11/190512/20/1400.000-53/30/106017086/31/180508/32/7.7/40/2:10;0:30
                </div>
            </div>
        </div>
    </div>

    <div class="bg-yellow-50 p-4 rounded-lg">
        <h4 class="font-semibold text-yellow-800 mb-2">⚠️ Wichtige Hinweise:</h4>
        <ul class="text-yellow-700 text-sm space-y-1">
            <li>• Maximal 1000 Zeichen</li>
            <li>• Wird NICHT mit der Zahlung übertragen</li>
            <li>• Nur für interne ERP-Systeme</li>
            <li>• Format: //S1/Feld/Wert/Feld/Wert...</li>
            <li>• Keine Leerzeichen in den Werten</li>
        </ul>
    </div>

    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-800 mb-2">🎯 Anwendungsfälle:</h4>
        <ul class="text-gray-700 text-sm space-y-1">
            <li>• Automatische Buchung in Buchhaltungssoftware</li>
            <li>• QR-Code Scanner für Rechnungsverarbeitung</li>
            <li>• Debitorenverwaltung</li>
            <li>• Workflow-Automatisierung</li>
        </ul>
    </div>
</div>