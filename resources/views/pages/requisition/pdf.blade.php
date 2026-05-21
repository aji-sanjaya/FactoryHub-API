<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Requisition {{ $requisition->documentno }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
        }
        table { 
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: 1px solid black;
            padding: 5px;
            vertical-align: middle;
        }
        .logo-cell {
            width: 15%;
            text-align: center;
        }
        .title-cell {
            width: 55%;
            text-align: center;
        }
        .info-cell {
            width: 30%;
        }
        .logo-box {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: #FFD700; /* Yellow */
            color: white;
            font-size: 24px;
            font-weight: bold;
            line-height: 40px;
            border: 1px solid #ccc;
        }
        h1 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
        }
        h2 {
            margin: 5px 0 0;
            font-size: 12pt;
            font-weight: bold;
        }
        .info-row {
            margin-bottom: 2px;
        }
        .info-label {
            display: inline-block;
            width: 80px;
        }
        
        /* Sub Header */
        .sub-header {
            margin-top: 10px;
            border: 1px solid black;
            padding: 5px;
        }
        .sub-header td {
            border: none;
            padding: 2px;
        }
        
        /* Items Table */
        .items-table {
            margin-top: 10px;
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            border-top: 2px solid black;
            border-bottom: 2px solid black;
            padding: 5px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 5px;
            border-bottom: 1px solid #ccc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .total-row td {
            border-top: 2px solid black;
            font-weight: bold;
            padding: 10px 5px;
        }
        .items-table .last-row td {
            border-bottom: 2px solid black;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
        }
        .footer-note {
            margin-bottom: 20px;
            font-size: 9pt;
        }
        .disclaimer {
            font-size: 8pt;
            margin-bottom: 20px;
        }
        
        /* Signatures */
        .signature-table {
            width: 100%;
            margin-left: 0;
            border: none;
            margin-top: 30px;
        }
        .signature-table td {
            border: none;
            text-align: center;
            vertical-align: top;
            padding: 10px;
        }
        .sig-label {
            margin-bottom: 15px;
            font-size: 10pt;
        }
        .sig-qr {
            margin-bottom: 15px;
            height: 80px;
            display: flex;
            justify-content: center;
        }
        .sig-qr img {
            display: block;
            margin: 0 auto;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            font-style: italic;
            margin-bottom: 2px;
            font-size: 10pt;
        }
        .sig-date {
            font-size: 9pt;
            font-family: 'Courier New', Courier, monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <table style="width: 100%; border-collapse: collapse;">
            <!-- Row 1: Company Address | Logo + Title -->
            <tr>
                <td style="border-bottom: 1px solid black; padding: 8px; width: 60%; vertical-align: top;">
                    <strong style="font-size: 13pt;">{{ $clientName ?? '' }}</strong><br>
                    @if(!empty($orgInfo))
                        @if(!empty($orgInfo->address1))<span style="font-size: 9pt;">{{ $orgInfo->address1 }}</span><br>@endif
                        @if(!empty($orgInfo->address2))<span style="font-size: 9pt;">{{ $orgInfo->address2 }}</span><br>@endif
                        @if(!empty($orgInfo->address3))<span style="font-size: 9pt;">{{ $orgInfo->address3 }}</span>@endif
                    @endif
                </td>
                <td style="border-bottom: 1px solid black; padding: 8px; width: 40%; text-align: right; vertical-align: top;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px; width: auto;"><br>
                    @endif
                    <strong style="font-size: 13pt;">Purchase Requisition IT</strong>
                </td>
            </tr>
            <!-- Row 2: Doc Info | Prepared by | Legalized By | Approved by -->
            <tr>
                <td colspan="2" style="border: 1px solid black; border-top: none; padding: 0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border-right: 1px solid black; padding: 5px; width: 46%; vertical-align: top; font-size: 10pt;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="width: 32%; padding: 0; border: none; vertical-align: top;">Document</td>
                                        <td style="width: 4%; padding: 0; border: none; vertical-align: top;">:</td>
                                        <td style="padding: 0; border: none; vertical-align: top;"><strong>{{ $requisition->documentno }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0; border: none; vertical-align: top;">Date</td>
                                        <td style="padding: 0; border: none; vertical-align: top;">:</td>
                                        <td style="padding: 0; border: none; vertical-align: top;"><strong>{{ date('d M Y', strtotime($requisition->datedoc)) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0; border: none; vertical-align: top;">Status</td>
                                        <td style="padding: 0; border: none; vertical-align: top;">:</td>
                                        <td style="padding: 0; border: none; vertical-align: top;">{{ $requisition->status_label }}</td>
                                    </tr>
                                </table>
                            </td>
                            <td style="border-right: 1px solid black; padding: 5px; width: 18%; text-align: center; vertical-align: top; font-size: 10pt;">
                                <em>Prepared by</em><br>
                                @if($preparedQr)
                                    <img src="{{ $preparedQr }}" alt="QR" style="height: 60px; width: 60px; margin: 4px auto; display: block;">
                                @else
                                    <div style="height: 68px;"></div>
                                @endif
                                <div style="font-weight: bold; font-style: italic; text-decoration: underline; font-size: 9pt; margin-top: 2px;">{{ $preparedBy ?? '' }}</div>
                                <div style="font-size: 8pt;">{{ $preparedDate }}</div>
                            </td>
                            <td style="border-right: 1px solid black; padding: 5px; width: 18%; text-align: center; vertical-align: top; font-size: 10pt;">
                                <em>Legalized By</em><br>
                                @if($checkedQr)
                                    <img src="{{ $checkedQr }}" alt="QR" style="height: 60px; width: 60px; margin: 4px auto; display: block;">
                                @else
                                    <div style="height: 68px;"></div>
                                @endif
                                <div style="font-weight: bold; font-style: italic; text-decoration: underline; font-size: 9pt; margin-top: 2px;">{{ $checkedBy ?? '' }}</div>
                                <div style="font-size: 8pt;">{{ $checkedDate }}</div>
                            </td>
                            <td style="padding: 5px; width: 18%; text-align: center; vertical-align: top; font-size: 10pt;">
                                <em>Approved by</em><br>
                                @if($approvedQr)
                                    <img src="{{ $approvedQr }}" alt="QR" style="height: 60px; width: 60px; margin: 4px auto; display: block;">
                                @else
                                    <div style="height: 68px;"></div>
                                @endif
                                <div style="font-weight: bold; font-style: italic; text-decoration: underline; font-size: 9pt; margin-top: 2px;">{{ $approvedBy ?? '' }}</div>
                                <div style="font-size: 8pt;">{{ $approvedDate }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table" cellspacing="0">
            <thead>
                <tr>
                    <th width="7%">Line</th>
                    <th width="43%">Product No / Name / SO Ref.</th>
                    <th width="6%" class="text-right">Qty</th>
                    <th width="8%">UoM</th>
                    <th width="13%">Required</th>
                    <th width="23%">Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                    <tr>
                        <td class="text-center">{{ $line->line }}</td>
                        <td>{{ $line->product_code }}</td>
                        <td class="text-right">{{ number_format($line->qty, 0) }}</td>
                        <td>{{ $line->uom_name }}</td>
                        <td>{{ !empty($line->daterequired) ? date('d/m/Y', strtotime($line->daterequired)) : '' }}</td>
                        <td style="font-size: 9pt;">{{ $line->description }}</td>
                    </tr>
                    <tr @if($loop->last) class="last-row" @endif>
                        <td></td>
                        <td style="font-size: 9pt; padding-top: 0;">{{ $line->product_name }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforeach 
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-note">
                <strong>Note :</strong><br>
                {{ $requisition->description ?: '-' }}
            </div> 


        </div>
    </div>
</body>
</html>
