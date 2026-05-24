<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Good Receipt {{ $receipt->documentno }}</title>
    <style>
        @page {
            margin-top:    150px;
            margin-bottom: 1cm;
            margin-left:   20px;
            margin-right:  20px;
        }

        /* ── Fixed repeating header ── */
        #page-header {
            position: fixed;
            top:    -145px;
            left:   0px;
            right:  0px;
            background: white;
        }

        /* ── Signature footer (normal flow) ── */
        #page-footer {
            margin-top: 1em;
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

    <div id="page-header">
    {{-- ── Header ── --}}
    <table style="width: 100%; border-collapse: collapse;">
        {{-- Row 1: Company name + address | Logo + Title --}}
        <tr>
            <td style="border-bottom: 1px solid black; padding: 8px; width: 60%; vertical-align: top;">
                <strong style="font-size: 13pt;">{{ $clientName ?? '' }}</strong><br>
                @if(!empty($orgInfo))
                    @if(!empty($orgInfo->address1))<span style="font-size: 9pt;">{{ $orgInfo->address1 }}</span><br>@endif
                    @if(!empty($orgInfo->address2))<span style="font-size: 9pt;">{{ $orgInfo->address2 }}</span><br>@endif
                    @if(!empty($orgInfo->address3))<span style="font-size: 9pt;">{{ $orgInfo->address3 }}</span><br>@endif
                    @php
                        $cityPostal = trim(($orgInfo->city ?? '') . ($orgInfo->postal ? ',  ' . $orgInfo->postal : ''));
                    @endphp
                    @if($cityPostal)<span style="font-size: 9pt;">{{ $cityPostal }}</span>@endif
                @endif
            </td>
            <td style="border-bottom: 1px solid black; padding: 8px; width: 40%; text-align: right; vertical-align: top;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px; width: auto;"><br>
                @endif
                <strong style="font-size: 13pt;">Material Receipt</strong>
            </td>
        </tr>
        {{-- Row 2: Supplier info | Doc info --}}
        <tr>
            <td colspan="2" style="border: 1px solid black; border-top: none; padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border-right: 1px solid black; padding: 8px; width: 50%; vertical-align: top; font-size: 10pt;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 2px 4px; white-space: nowrap;">Supplier Name</td>
                                    <td style="padding: 2px 4px; width: 8px; text-align: center;">:</td>
                                    <td style="padding: 2px 4px;">{{ $vendorName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 4px; white-space: nowrap;">Vendor Code</td>
                                    <td style="padding: 2px 4px; text-align: center;">:</td>
                                    <td style="padding: 2px 4px;">{{ $vendorCode }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 4px; white-space: nowrap;">Supplier DN No.</td>
                                    <td style="padding: 2px 4px; text-align: center;">:</td>
                                    <td style="padding: 2px 4px;">{{ $supplierDNNo }}</td>
                                </tr>
                            </table>
                        </td>
                        <td style="padding: 8px; width: 50%; vertical-align: top; font-size: 10pt;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 2px 4px; white-space: nowrap;">No.</td>
                                    <td style="padding: 2px 4px; width: 8px;">:</td>
                                    <td style="padding: 2px 4px;"><strong>{{ $receipt->documentno }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 4px; white-space: nowrap;">Date</td>
                                    <td style="padding: 2px 4px;">:</td>
                                    <td style="padding: 2px 4px;"><strong>{{ $receipt->movementdate ? \Carbon\Carbon::parse($receipt->movementdate)->format('d/m/Y') : '-' }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 4px; white-space: nowrap;">Status</td>
                                    <td style="padding: 2px 4px;">:</td>
                                    <td style="padding: 2px 4px;">
                                        @php
                                            $statusMap = ['DR'=>'Draft','IP'=>'In Progress','CO'=>'Completed','VO'=>'Voided','RE'=>'Reversed','CL'=>'Closed'];
                                            echo $statusMap[$receipt->docstatus] ?? $receipt->docstatus;
                                        @endphp
                                    </td>
                                </tr>
                            </table>
                        </td>
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

    <div id="page-footer">
        {{-- LEGALIZATION Signature Table --}}
        <table style="width:100%; border-collapse:collapse;">
            <tbody>
                {{-- Row 1: blank left (tall, spans 3 rows) + LEGALIZATION header --}}
                <tr>
                    <td rowspan="3" style="width:76%; border:1px solid #000; vertical-align:top;">
                         <!-- Notes -->
                        <div class="notes-section" style="margin-left: 10px;">
                            <strong>Note :</strong>
                            <div class="notes-content">{{ $receipt->description ?? '' }}</div>
                        </div>
                    </td>
                    <td colspan="3" style="border:1px solid #000; text-align:center; font-weight:bold; padding:4px 6px; font-size:9pt;">LEGALIZATION</td>
                </tr>
                {{-- Row 2: QR Code --}}
                <tr style="height:65px;">
                    <td style="border:1px solid #000; width:18%; vertical-align:middle; text-align:center; padding:3px;">
                        @if(!empty($purchasingQr))
                            <img src="{{ $purchasingQr }}" alt="QR" style="height:58px; width:58px;">
                        @endif
                    </td>
                    <td style="border:1px solid #000; width:18%; vertical-align:middle; text-align:center; padding:3px;">
                        @if(!empty($qcIncomingQr))
                            <img src="{{ $qcIncomingQr }}" alt="QR" style="height:58px; width:58px;">
                        @endif
                    </td>
                    <td style="border:1px solid #000; width:18%; vertical-align:middle; text-align:center; padding:3px;">
                        @if(!empty($userQr))
                            <img src="{{ $userQr }}" alt="QR" style="height:58px; width:58px; margin-top:15px; margin-bottom:15px;">
                        @else 
                            <div style="margin:0 auto; height:58px; width:58px; margin-top:15px; font-weight:bold;">DRAFT</div>
                        @endif
                    </td>
                </tr>
                {{-- Row 3: name labels --}}
                <tr>
                    <td style="border:1px solid #000; text-align:center; vertical-align:top; padding:2px 4px; font-size:9pt; line-height:1;">{{ $approvedByLabel ?? 'Purchasing' }}</td>
                    <td style="border:1px solid #000; text-align:center; vertical-align:top; padding:2px 4px; font-size:9pt; line-height:1;">{{ $checkedByLabel ?? 'QC Incomming' }}</td>
                    <td style="border:1px solid #000; text-align:center; vertical-align:top; padding:2px 4px; font-size:9pt; line-height:1;">{{ $updatedByName ?: 'User' }}</td>
                </tr>
            </tbody>
        </table>
        {{-- Doc number + printed date --}}
        <table style="width:100%; border-collapse:collapse; margin-top:3px;">
            <tr>
                <td style="text-align:left; font-size:8pt;">API/RC/PURCH/01/03</td>
                <td style="text-align:right; font-size:8pt;">Printed : {{ now()->format('d-M-Y H:i:s') }}</td>
            </tr>
        </table>
    </div> 

</body>
</html>
