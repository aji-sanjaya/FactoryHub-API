<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Delivery Order {{ $shipment->documentno }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            margin: 0 10px;
            margin-top: 245px;
            margin-bottom: 180px;
            padding: 0;
        }

        .company-name {
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
        }

        .company-address {
            font-size: 8pt;
            margin: 2px 0 5px 0;
            line-height: 1.6;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* ===== FIXED HEADER (repeats every page) ===== */
        #header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 5px 10px 0;
            background: white;
        }

        /* ===== FIXED FOOTER (repeats every page) ===== */
        #footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 10px 20px;
            background: white;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead tr th {
            border-top: 1.5px solid black;
            border-bottom: 1.5px solid black;
            padding: 3px 5px;
            font-size: 9pt;
            font-weight: bold;
        }

        .items-table tbody tr td {
            padding: 3px 5px;
            font-size: 9pt;
            vertical-align: top;
        }

        .items-table tfoot tr td {
            border-top: 1.5px solid black;
        }

        /* Signature section */
        .sig-outer {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-outer>tbody>tr>td {
            vertical-align: top;
            padding: 0;
        }

        .sig-title {
            font-style: italic;
            font-size: 9pt;
            margin-bottom: 4px;
        }

        .sig-boxes {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-boxes td {
            border: 1px solid black;
            padding: 5px 8px;
            font-size: 8pt;
            font-style: italic;
            vertical-align: top;
            width: 33.3%;
            height: 110px;
        }

        .buyer-time {
            font-size: 9pt;
            margin-bottom: 4px;
        }

        .buyer-box {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
        }

        .buyer-box td {
            padding: 3px 6px;
            font-size: 8.5pt;
            border: none;
        }

        .buyer-header td {
            border-bottom: 1px solid black;
            font-style: italic;
        }

        .buyer-spacer td {
            height: 35px;
        }
    </style>
</head>

<body>

    <!-- ===== FIXED HEADER ===== -->
    <div id="header">
        <p class="company-name">PT. {{ strtoupper($clientName) }}</p>
        <p class="company-address">
            {{ $orgAddress }}<br>
            Phone : {{ $orgPhone }}
        </p>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td
                    style="width:50%; border:1px solid black; text-align:center; font-size:14pt; font-weight:bold; padding:10px 8px; vertical-align:middle;">
                    DELIVERY ORDER
                </td>
                <td style="width:28%; border:1px solid black; padding:4px 6px; vertical-align:top; border-right: none;">
                    <span style="font-size:8pt;">Reg No.</span><br>
                    <strong style="font-size:11pt;">{{ $shipment->documentno }}</strong>
                </td>
                <td style="width:22%; border:1px solid black; padding:4px 6px; vertical-align:top; border-left: none;">
                    <span style="font-size:8pt;">Date (DD/MM/YY)</span><br>
                    <strong style="font-size:11pt;">{{ date('d/m/Y', strtotime($shipment->movementdate)) }}</strong>
                </td>
            </tr>
            <tr>
                <td rowspan="3" style="border:1px solid black; padding:6px 8px; vertical-align:top;">
                    Ship To :<br><br>
                    <strong>{{ $customerName }}</strong><br>
                    {{ $customerAddress }}<br><br>
                    {{ $contactPerson !== '-' ? $contactPerson : '' }}
                </td>
                <td
                    style="border:1px solid black; padding:4px 6px; height:30px; vertical-align:top; border-right: none;">
                    <span style="font-size:8pt;">SO No.</span>
                    <br> <strong>{{ $soDocumentNo }}</strong>
                </td>
                <td style="border:1px solid black; padding:4px 6px; vertical-align:top; border-left: none;">
                    <span style="font-size:8pt;">PO No. :</span>
                    <br> <strong>{{ $shipment->poreference ?? '-' }}</strong>
                </td>
            </tr>
            <tr>
                <td
                    style="border:1px solid black; padding:4px 6px; height:30px; vertical-align:top; border-right: none;">
                    <span style="font-size:8pt;">TimeOut :</span>
                </td>
                <td style="border:1px solid black; padding:4px 6px; vertical-align:top; border-left: none;">
                    <span style="font-size:8pt;">Cheked by :</span>
                </td>
            </tr>
            <tr>
                <td
                    style="border:1px solid black; padding:4px 6px; height:30px; vertical-align:top; border-right: none;">
                    <span style="font-size:8pt;">Car Police No :</span>
                    <br> <strong>{{ $shipperName }}</strong>
                </td>
                <td style="border:1px solid black; padding:4px 6px; vertical-align:top; border-left: none;">
                    <span style="font-size:8pt;">Name :</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- ===== FIXED FOOTER ===== -->
    <div id="footer">
        <table class="sig-outer">
            <tr>
                <td style="width:55%; padding-right:12px;">
                    <div class="sig-title">Transfered Legalization (Supplier Area)</div>
                    <table class="sig-boxes">
                        <tr>
                            <td class="text-center">
                                TransferedBy,<br><br><br><br><br><br><br>
                                <span
                                    style="border-top:1px solid #000; display:block; padding-top:2px;">PPIC/Warehouse</span>
                            </td>
                            <td class="text-center">
                                ApprovedBy,<br><br><br><br><br><br><br>
                                <span style="border-top:1px solid #000; display:block; padding-top:2px;">Dept/Div.
                                    Head</span>
                            </td>
                            <td class="text-center">
                                DeliveredBy<br><br><br><br><br><br><br>
                                <span style="border-top:1px solid #000; display:block; padding-top:2px;">&nbsp;</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:45%;">
                    <div class="sig-title">Transfered Legalization (Buyer Area)</div>
                    <div class="buyer-time">
                        Time In :
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        Time Out :
                    </div>
                    <table class="buyer-box">
                        <tr class="buyer-header">
                            <td style="width:55%;">ReceivedBy</td>
                            <td style="width:45%; text-align:right;">Signature</td>
                        </tr>
                        <tr class="buyer-spacer">
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <td colspan="2">Name :</td>
                        </tr>
                        <tr>
                            <td colspan="2">Occupation :</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- ===== CONTENT: Items Table ===== -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:7%;" class="text-center">No</th>
                <th style="width:18%;" class="text-left">Product ID</th>
                <th style="width:46%;" class="text-left">Name</th>
                <th style="width:11%;" class="text-right">Qty</th>
                <th style="width:18%;" class="text-left">UoM</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $index => $line)
                <tr>
                    <td class="text-center">{{ $line->line ?? ($index + 1) }}</td>
                    <td class="text-left">{{ $line->product_code ?? '-' }}</td>
                    <td class="text-left">{{ $line->product_name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($line->qty, 0) }}</td>
                    <td class="text-left">{{ $line->uom_name ?? '-' }}</td>
                </tr>
            @endforeach
            @for($i = count($lines); $i < 10; $i++)
                <tr>
                    <td style="height:18px;">&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
        {{-- <tfoot>
            <tr>
                <td colspan="5" style="height:4px;"></td>
            </tr>
        </tfoot> --}}
    </table>

</body>

</html>