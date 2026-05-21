<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>AP Invoice {{ $invoice->documentno }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .container {
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* Header */
        .header-table {
            margin-bottom: 10px;
            border: 2px solid #000;
        }
        .header-table td {
            vertical-align: middle;
            padding: 8px;
        }
        .logo-cell {
            width: 80px;
            text-align: center;
            border-right: 2px solid #000;
        }
        .logo-box {
            width: 60px;
            height: 60px;
            background-color: #FACC15;
            display: inline-block;
            color: white;
            font-weight: bold;
            font-size: 40px;
            line-height: 60px;
            text-align: center;
        }
        .title-cell {
            text-align: center;
        }
        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-name {
            font-size: 12pt;
            font-weight: bold;
        }
        .doc-info-cell {
            width: 200px;
            border-left: 2px solid #000;
            font-size: 9pt;
            line-height: 1.6;
        }
        .doc-info-cell table {
            width: 100%;
        }
        .doc-info-cell td {
            padding: 2px 4px;
        }

        /* Document Details */
        .details-section {
            margin-top: 15px;
        }
        .details-row {
            margin-bottom: 8px;
        }
        .details-label {
            display: inline-block;
            width: 150px;
            font-weight: normal;
        }
        .details-colon {
            display: inline-block;
            width: 15px;
        }
        .details-value {
            display: inline-block;
        }
        
        /* Status Section */
        .status-section {
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #000;
        }
        .status-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .checkbox-group {
            display: inline-block;
            margin-right: 40px;
        }
        .checkbox {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 1px solid #000;
            margin-right: 5px;
            vertical-align: middle;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 60px;
        }
        .signature-table {
            width: 100%;
        }
        .signature-table td {
            text-align: center;
            vertical-align: bottom;
            padding: 10px;
        }
        .sig-title {
            font-weight: bold;
            margin-bottom: 60px;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 150px;
            padding-bottom: 2px;
        }

        /* Utilities */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .border-top { border-top: 2px solid #000; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 60px; width: auto;">
                    @else
                        <div class="logo-box">D</div>
                    @endif
                </td>
                <td class="title-cell">
                    <div class="doc-title">TANDA TERIMA TAGIHAN</div>
                    <div class="company-name">PT. DHARMAMULIA PRIMA KARYA</div>
                </td>
                <td class="doc-info-cell">
                    <table>
                        <tr>
                            <td style="width: 60px;">No. Dokumen</td>
                            <td style="width: 5px;">:</td>
                            <td>FM/FAT/02-001</td>
                        </tr>
                        <tr>
                            <td>Revisi</td>
                            <td>:</td>
                            <td>00</td>
                        </tr>
                        <tr>
                            <td>Tgl Terbit</td>
                            <td>:</td>
                            <td>29 Oktober 2025</td>
                        </tr>
                        <tr>
                            <td>Halaman</td>
                            <td>:</td>
                            <td>01</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="text-align: right; font-size: 9pt; margin-bottom: 10px;">
            {{-- Page 1 / 1 --}}
        </div>

        <!-- Invoice Number and Date -->
        <div style="border: 1px solid #000; padding: 10px; margin-bottom: 10px;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50px; font-weight: bold;">No</td>
                    <td style="width: 5px;">:</td>
                    <td>{{ $invoice->documentno }}</td>
                    <td style="width: 150px; text-align: right;">Page 1 / 1</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tanggal</td>
                    <td>:</td>
                    <td colspan="2">{{ \Carbon\Carbon::parse($invoice->dateinvoiced)->format('d M Y') }}</td>
                </tr>
            </table>
        </div>

        <!-- Status Checkboxes -->
        <div class="status-section">
            <div class="status-title">Status</div>
            <div>
                <span class="checkbox-group">
                    <span class="checkbox"></span> Diterima
                </span>
                <span class="checkbox-group">
                    <span class="checkbox"></span> Dikembalikan
                </span>
                <span style="margin-left: 100px;">*Coret yang tidak perlu</span>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="details-section">
            <div class="details-row">
                <span class="details-label">Vendor/supplier</span>
                <span class="details-colon">:</span>
                <span class="details-value font-bold">{{ $vendor->vendor_name ?? '-' }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">No Invoice</span>
                <span class="details-colon">:</span>
                <span class="details-value">{{ $invoice->documentno }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">No PO</span>
                <span class="details-colon">:</span>
                <span class="details-value">{{ $poDocumentNo ?? '-' }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">Tanggal Invoice</span>
                <span class="details-colon">:</span>
                <span class="details-value">{{ \Carbon\Carbon::parse($invoice->dateinvoiced)->format('d M Y') }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">Jatuh Tempo</span>
                <span class="details-colon">:</span>
                <span class="details-value">{{ $invoice->duedate ? \Carbon\Carbon::parse($invoice->duedate)->format('d M Y') : '-' }}</span>
            </div>
        </div>

        <div style="border-top: 1px solid #000; margin: 20px 0;"></div>

        <!-- Financial Details -->
        <div class="details-section">
            <div class="details-row">
                <span class="details-label">Nominal Invoice</span>
                <span class="details-colon">:</span>
                <span class="details-value">{{ number_format($totalLines ?? 0, 2, '.', ',') }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">Total Tax Amount</span>
                <span class="details-colon">:</span>
                <span class="details-value">{{ number_format($taxAmount ?? 0, 2, '.', ',') }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">Total PPh23</span>
                <span class="details-colon">:</span>
                <span class="details-value">({{ number_format($withholdingTotal ?? 0, 2, '.', ',') }})</span>
            </div>
            <div class="details-row">
                <span class="details-label font-bold">Grand Total</span>
                <span class="details-colon">:</span>
                <span class="details-value font-bold">{{ number_format($grandTotalNet ?? 0, 2, '.', ',') }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">Term Of Payment</span>
                <span class="details-colon">:</span>
                <span class="details-value">{{ $paymentTerm ?? '-' }}</span>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div style="margin-bottom: 10px; font-style: italic;">
                Bekasi, {{ \Carbon\Carbon::parse($invoice->dateinvoiced)->format('d F Y') }}
            </div>
            <table class="signature-table">
                <tr>
                    <td style="width: 33%;">
                        <div class="sig-title">Prepared By</div>
                        <div class="sig-line">{{ $preparedBy ?? 'Super User with Access' }}</div>
                    </td>
                    <td style="width: 33%;">
                        <div class="sig-title">Approved By</div>
                        <div class="sig-line">-</div>
                    </td>
                    <td style="width: 33%;">
                        <div class="sig-title">Approved By</div>
                        <div class="sig-line">-</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
