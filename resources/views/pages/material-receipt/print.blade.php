<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Good Receipt {{ $receipt->documentno }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* ── Header ── */
        .header-table {
            margin-bottom: 2px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 2px;
        }
        .logo-box {
            width: 60px;
            height: 60px;
            background-color: #FACC15;
            text-align: center;
            line-height: 60px;
            font-size: 40px;
            font-weight: bold;
            color: #fff;
            border: 1px solid #EAB308;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #EAB308;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 5px;
        }
        .company-address {
            text-align: center;
            font-size: 8pt;
            line-height: 1.3;
        }

        /* ── Title ── */
        .doc-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }

        /* Info Section */
        .info-container {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 15px;
        }
        .info-table td {
            vertical-align: top;
            padding: 3px 5px;
        }
        .info-label {
            font-weight: normal;
            width: 110px;
        }
        .colon {
            width: 10px;
            text-align: center;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .items-table th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
        }
        .items-table td {
            padding: 8px 5px;
            vertical-align: top;
        }
        .items-table td.qty { text-align: right; }
        .items-table th.text-right { text-align: right; }
        .items-table th.text-left { text-align: left; }

        /* Notes */
        .notes-section {
            margin-top: 20px;
            font-size: 9pt;
        }
        .notes-section strong {
            display: block;
            margin-bottom: 5px;
        }
        .notes-content {
            margin-bottom: 15px;
            white-space: pre-wrap;
        }

        /* Signatures */
        .signature-section {
            margin-top: 30px;
            width: 100%;
        }
        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 25%;
            padding: 10px;
        }
        .sig-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .sig-name {
            font-weight: bold;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
            padding-bottom: 2px;
        }
        .sig-role {
            font-size: 8pt;
            margin-top: 2px;
        }

        /* Utilities */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .border-top { border-top: 2px solid #000; }
        .border-bottom { border-bottom: 2px solid #000; }
    </style>
</head>
<body>

    {{-- ── Header ── --}}
    <table class="header-table">
        <tr>
            <td width="15%" align="center">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 60px; width: auto;">
                @else
                    <div class="logo-box">D</div>
                @endif
            </td>
            <td width="85%" align="center">
                <div class="company-name">PT DHARMAMULIA PRIMA KARYA</div>
                <div class="company-address">
                    Jalan Jogja-Solo KM 12,5, Padukuhan Karang Kalasan, RT 001/RW 006 Kelurahan Tirtomartani,<br>
                    Kecamatan Kalasan, Kabupaten Sleman, Daerah Istimewa Yogyakarta Telp. 0274 – 2850888, Fax. 0274 – 497468
                </div>
            </td>
        </tr>
    </table>
    <div style="text-align: right; font-size: 8pt; margin-top: 2px;">Page 1 / 1</div>

    {{-- ── Title ── --}}
    <div class="doc-title">Good Receipt</div>

    <!-- Info Box -->
    <div class="info-container">
        <table class="info-table">
            <tr>
                <!-- Left Column -->
                <td width="50%">
                    <table>
                        <tr>
                            <td class="info-label">No. Dokumen</td>
                            <td class="colon">:</td>
                            <td>FM/PCR/01-0023</td>
                        </tr>
                        <tr>
                            <td class="info-label">Revisi</td>
                            <td class="colon">:</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td class="info-label">Tanggal Terbit</td>
                            <td class="colon">:</td>
                            <td>{{ $receipt->dateacct ? \Carbon\Carbon::parse($receipt->dateacct)->format('d M Y') : \Carbon\Carbon::parse($receipt->created)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Halaman</td>
                            <td class="colon">:</td>
                            <td>1</td>
                        </tr>
                    </table>
                </td>
                <!-- Right Column -->
                <td width="50%" style="border-left: 1px solid #000; padding-left: 10px;">
                    <table>
                        <tr>
                            <td class="info-label">Tanggal GR</td>
                            <td class="colon">:</td>
                            <td>{{ $receipt->movementdate ? \Carbon\Carbon::parse($receipt->movementdate)->format('d M Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">No. GR</td>
                            <td class="colon">:</td>
                            <td>{{ $receipt->documentno }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Supplier</td>
                            <td class="colon">:</td>
                            <td><strong>{{ $vendorName }}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Site</td>
                            <td class="colon">:</td>
                            <td>{{ $warehouseAddress ?? $warehouseName }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <table class="items-table" cellspacing="0">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="35%">Item No</th>
                <th width="45%">Item Name</th>
                <th width="10%" class="text-right">Qty</th>
                <th width="5%" class="text-left">UoM</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->product_code ?? '-' }}</td>
                    <td>{{ $line->product_name ?? '' }}</td>
                    <td class="qty">{{ number_format($line->qty, 0) }}</td>
                    <td class="text-left">{{ $line->uom_name ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="color:#888; padding: 12px;">No lines</td>
                </tr>
            @endforelse
            <tr><td colspan="5" class="border-top"></td></tr>
        </tbody>
    </table>

    <!-- Notes -->
    <div class="notes-section">
        <strong>Note :</strong>
        <div class="notes-content">{{ $receipt->description ?? '' }}</div>
    </div>

    <!-- Signatures -->
    <table class="signature-section">
        <tr>
            <td style="text-align: left; vertical-align: top; width: 25%; padding: 10px;">
                <div class="sig-title">Receipt By</div>
                <div style="height: 65px;"></div>
                <div class="sig-name">{{ $receivedByName ?? '..................' }}</div>
                <div class="sig-role" style="font-style: italic; font-size: 7pt;">
                    {{ \Carbon\Carbon::parse($receipt->updated ?? $receipt->created)->format('d M Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
