<!DOCTYPE html>
<html>
<head>
    <title>QR Payment Slip - Invoice #{{ $invoice->invoice_number }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            background-color: white;
            color: black;
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
        }

        .qr-slip-container {
            max-width: 210mm;
            margin: 0 auto;
        }

        @media print {
            body {
                print-color-adjust: exact !important;
                -webkit-print-color-adjust: exact !important;
                margin: 0;
                padding: 0;
            }

            @page {
                size: A4;
                margin: 10mm;
            }

            .no-print {
                display: none !important;
            }
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }

        .print-button {
            background: #007cba;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px;
        }

        .print-button:hover {
            background: #005a8a;
        }
    </style>
</head>
<body>
    <div class="qr-slip-container">
        <div class="header no-print">
            <h1>Swiss QR Payment Slip</h1>
            <p>Invoice #{{ $invoice->invoice_number }} | {{ $invoice->client?->name }}</p>
            <button class="print-button" onclick="window.print()">🖨️ Print</button>
            <button class="print-button" onclick="window.close()">❌ Close</button>
        </div>

        <div class="qr-payment-part">
            {!! $qrBillHtml !!}
        </div>
    </div>

    <script>
        // Auto-focus for better print experience
        window.addEventListener('load', function() {
            // Optional: Auto-print when opened
            // window.print();
        });
    </script>
</body>
</html>