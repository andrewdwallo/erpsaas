<!DOCTYPE html>
<html>
<head>
    <title>Invoice #{{ $document->number }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Include Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    {!! $document->getFontHtml() !!}

    <style>
        body {
            background-color: white;
            color: black;
        }

        .doc-template-paper {
            font-family: '{{ $document->font->getLabel() }}', sans-serif;
        }

        .page-break-before { page-break-before: always; }
        
        /* QR Payment Slip positioning at bottom of page */
        .qr-payment-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        
        .qr-payment-part {
            width: 210mm;
            max-width: 100%;
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
                margin: 7.5mm 0;
            }

            @page:first {
                margin-top: 0;
            }
            
            /* QR payment slip page styling */
            .qr-payment-page {
                min-height: calc(100vh - 15mm); /* Account for page margins */
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                page-break-inside: avoid;
            }
            
            .qr-payment-part {
                width: 210mm;
                max-width: 100%;
                margin: 0 auto;
                page-break-inside: avoid;
            }

            .doc-template-container {
                padding: 0 !important;
                margin: 0 !important;

                > div {
                    overflow: hidden !important;
                    max-height: none !important;
                    max-width: none !important;
                    box-shadow: none !important;
                    border-radius: 0 !important;
                }
            }

            .doc-template-paper {
                overflow: hidden !important;
                max-height: none !important;
                max-width: none !important;
                height: auto !important;
                width: auto !important;
            }

            .doc-template-line-items .summary-section {
                display: table-row-group;
                page-break-inside: avoid;
            }

            .doc-template-line-items tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .doc-template-footer {
                page-break-inside: avoid;
                page-break-before: auto;
            }
        }
    </style>
</head>
<body>
    @include("filament.company.components.document-templates.{$template->value}", [
        'document' => $document,
        'preview' => false,
    ])

    @if(!empty($qrBillHtml))
        <div class="page-break-before qr-payment-page">
            <div class="qr-payment-part">
                {!! $qrBillHtml !!}
            </div>
        </div>
    @endif
</body>
</html>
