<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Delivery Order {{ $shipment->documentno }} (Style 3)</title>
    <style>
        @page {
            margin: 20px 25px;
        }

        body {
            font-family: Georgia, Times, serif;
            font-size: 8.5pt;
            margin-top: 290px;
            margin-bottom: 165px;
            padding: 0;
            line-height: 1.25;
            color: #374151;
        }

        /* ===== FIXED HEADER ===== */
        #header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 285px;
            background: white;
        }

        /* ===== FIXED FOOTER ===== */
        #footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 155px;
            background: white;
        }

        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        .company-address {
            font-size: 8pt;
            margin: 2px 0 0 0;
            line-height: 1.3;
            color: #4b5563;
            font-family: Arial, sans-serif;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #374151;
        }

        .items-table thead tr th {
            border-top: 1.5px solid #374151;
            border-bottom: 1.5px solid #374151;
            padding: 5px 4px;
            font-size: 8.5pt;
            font-weight: bold;
            color: #111827;
        }

        .items-table tbody tr td {
            padding: 6px 4px;
            font-size: 8.5pt;
            vertical-align: top;
            border-bottom: 1px dashed #e5e7eb;
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
    </style>
</head>

<body>

    <!-- ===== FIXED HEADER ===== -->
    <div id="header">
        @php
            $displayName = $clientName;
            if (!preg_match('/^PT\.?/i', $displayName)) {
                $displayName = 'PT. ' . $displayName;
            }

            // Parse customer address
            $custParts = explode(', ', $customerAddress);
            $cust1 = '';
            $cust2 = '';
            $custCityPostal = '';
            if (count($custParts) >= 4) {
                $cust1 = $custParts[0];
                $cust2 = $custParts[1];
                $custCityPostal = $custParts[2] . ', ' . $custParts[3];
            } elseif (count($custParts) == 3) {
                $cust1 = $custParts[0];
                $cust2 = $custParts[1];
                $custCityPostal = $custParts[2];
            } elseif (count($custParts) == 2) {
                $cust1 = $custParts[0];
                $custCityPostal = $custParts[1];
            } else {
                $cust1 = $customerAddress;
            }
        @endphp

        <!-- Top Header Info & Logo -->
        <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 8px;">
            <tr>
                <td style="width: 65%; border: none; padding: 0; vertical-align: top;">
                    <span class="company-name">{{ $displayName }}</span><br>
                    <span class="company-address">
                        @if(!empty($orgAddress1)){{ $orgAddress1 }}<br>@endif
                        @if(!empty($orgAddress2)){{ $orgAddress2 }}<br>@endif
                        @if(!empty($orgAddress3)){{ $orgAddress3 }}<br>@endif
                        Indonesia
                    </span>
                </td>
                <td style="width: 35%; border: none; padding: 0; text-align: right; vertical-align: top;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 48px; width: auto; margin-top: -2px;">
                    @endif
                </td>
            </tr>
        </table>

        <!-- Shipment Info Grid -->
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #374151; table-layout: fixed; margin-top: 5px;">
            <!-- Row 1: Surat Jalan, Reg No., Date -->
            <tr>
                <td style="width: 58%; border: 1px solid #374151; text-align: center; font-size: 14pt; font-weight: bold; padding: 6px; vertical-align: middle;">
                    Surat Jalan
                </td>
                <td style="width: 21%; border: 1px solid #374151; padding: 3px 5px; vertical-align: top; font-family: Arial, sans-serif;">
                    <div style="font-size: 7.5pt; color: #6b7280;">Reg No.</div>
                    <div style="font-size: 11pt; font-weight: bold; text-align: center; margin-top: 2px;">{{ $shipment->documentno }}</div>
                </td>
                <td style="width: 21%; border: 1px solid #374151; padding: 3px 5px; vertical-align: top; font-family: Arial, sans-serif;">
                    <div style="font-size: 7.5pt; color: #6b7280;">Date (M/D/Y)</div>
                    <div style="font-size: 11pt; font-weight: bold; text-align: center; margin-top: 2px;">{{ date('m/d/Y', strtotime($shipment->movementdate)) }}</div>
                </td>
            </tr>
            <!-- Row 2: Ship To, SO Details -->
            <tr>
                <td rowspan="3" style="border: 1px solid #374151; padding: 6px 8px; vertical-align: top;">
                    <div style="font-size: 7.5pt; color: #6b7280; margin-bottom: 2px; font-family: Arial, sans-serif;">Ship To</div>
                    <table style="width: 100%; border-collapse: collapse; border: none;">
                        <tr>
                            <td style="width: 65%; border: none; padding: 0; vertical-align: top; font-size: 8pt; line-height: 1.3;">
                                <strong>{{ strtoupper($customerName) }}</strong><br>
                                <span style="font-family: Arial, sans-serif;">
                                    {{ $cust1 }}<br>
                                    @if(!empty($cust2))
                                        {{ $cust2 }}<br>
                                    @endif
                                    @if(!empty($custCityPostal))
                                        {{ $custCityPostal }}<br>
                                    @endif
                                    Indonesia
                                </span>
                            </td>
                            <td style="width: 35%; border: none; padding: 0; vertical-align: bottom; text-align: right; font-size: 7.5pt; line-height: 1.3; color: #6b7280; font-family: Arial, sans-serif;">
                                Status : Completed<br>
                                Admin : {{ $preparedBy }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td colspan="2" style="border: 1px solid #374151; padding: 4px 6px; vertical-align: top; font-size: 8pt; line-height: 1.35; font-family: Arial, sans-serif;">
                    Sales Order : {{ $soDocumentNo }} - {{ $soDate }}<br>
                    PO Cust : {{ $soPoReference }}<br>
                    DO No : {{ $shipment->poreference ?? '-' }}
                </td>
            </tr>
            <!-- Row 3: Security Header -->
            <tr>
                <td colspan="2" style="border: 1px solid #374151; padding: 2px; text-align: center; font-style: italic; font-size: 7.5pt; background-color: #fafafa; color: #6b7280;">
                    Transportation Data Filled by Security
                </td>
            </tr>
            <!-- Row 4: Timeout, Checked By -->
            <tr>
                <td style="border: 1px solid #374151; padding: 3px 5px; vertical-align: top; font-size: 8pt; height: 35px; font-family: Arial, sans-serif;">
                    Time Out :<br>
                    Car Police No : {{ $shipperName }}
                </td>
                <td style="border: 1px solid #374151; padding: 3px 5px; vertical-align: top; font-size: 8pt; height: 35px; font-family: Arial, sans-serif;">
                    <div style="font-style: italic; font-size: 7pt; text-align: right; color: #6b7280;">Cheked by</div>
                    <div style="margin-top: 6px;">Name:</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ===== FIXED FOOTER ===== -->
    <div id="footer">
        <table style="width: 100%; border-collapse: collapse; border: none; table-layout: fixed;">
            <tr>
                <!-- Left Side: Supplier Area -->
                <td style="width: 53%; border: none; padding: 0 10px 0 0; vertical-align: top;">
                    <div style="font-style: italic; font-size: 8.5pt; margin-bottom: 2px;">Transfered Legalization (Supplier Area)</div>
                    <table style="width: 100%; border-collapse: collapse; border: none; table-layout: fixed;">
                        <tr>
                            <!-- Box 1 -->
                            <td style="width: 32%; border: 1px solid #374151; padding: 0; vertical-align: top; font-family: Arial, sans-serif;">
                                <table style="width: 100%; height: 90px; border-collapse: collapse; border: none;">
                                    <tr>
                                        <td style="padding: 3px 4px; font-style: italic; font-size: 7.5pt; height: 14px; border: none; vertical-align: top; color: #6b7280;">Transfered by,</td>
                                    </tr>
                                    <tr>
                                        <td style="height: 52px; border: none;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 3px 4px; font-size: 8pt; text-align: center; height: 18px; border: none; vertical-align: bottom;">{{ $preparedBy }}</td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width: 2%; border: none;">&nbsp;</td>
                            <!-- Box 2 -->
                            <td style="width: 32%; border: 1px solid #374151; padding: 0; vertical-align: top; font-family: Arial, sans-serif;">
                                <table style="width: 100%; height: 90px; border-collapse: collapse; border: none;">
                                    <tr>
                                        <td style="padding: 3px 4px; font-style: italic; font-size: 7.5pt; height: 14px; border: none; vertical-align: top; color: #6b7280;">Approved by,</td>
                                    </tr>
                                    <tr>
                                        <td style="height: 52px; border: none;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 3px 4px; font-size: 8pt; text-align: center; height: 18px; border: none; vertical-align: bottom;">Dept/Div. Head</td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width: 2%; border: none;">&nbsp;</td>
                            <!-- Box 3 -->
                            <td style="width: 32%; border: 1px solid #374151; padding: 0; vertical-align: top; font-family: Arial, sans-serif;">
                                <table style="width: 100%; height: 90px; border-collapse: collapse; border: none;">
                                    <tr>
                                        <td style="padding: 3px 4px; font-style: italic; font-size: 7.5pt; height: 14px; border: none; vertical-align: top; color: #6b7280;">Delivered by,</td>
                                    </tr>
                                    <tr>
                                        <td style="height: 52px; border: none;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 3px 4px; font-size: 8pt; text-align: center; height: 18px; border: none; vertical-align: bottom;">Delivery</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Right Side: Buyer Area -->
                <td style="width: 47%; border: none; padding: 0; vertical-align: top;">
                    <div style="font-style: italic; font-size: 8.5pt; margin-bottom: 2px;">Transfered Legalization (Buyer Area)</div>
                    
                    <!-- Time In / Out details above the box -->
                    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 3px; font-family: Arial, sans-serif;">
                        <tr>
                            <td style="width: 50%; font-size: 7.5pt; font-style: italic; border: none; padding: 0;">Time in :</td>
                            <td style="width: 50%; font-size: 7.5pt; font-style: italic; border: none; padding: 0;">Time out :</td>
                        </tr>
                    </table>

                    <!-- Buyer Area border box -->
                    <table style="width: 100%; border: 1px solid #374151; border-collapse: collapse; table-layout: fixed; font-family: Arial, sans-serif;">
                        <tr>
                            <td style="padding: 0; border: none;">
                                <table style="width: 100%; height: 90px; border-collapse: collapse; border: none;">
                                    <tr>
                                        <td style="padding: 3px 4px; font-style: italic; font-size: 7.5pt; height: 14px; border: none; vertical-align: top; color: #6b7280;">Received by,</td>
                                        <td style="padding: 3px 4px; font-style: italic; font-size: 7.5pt; height: 14px; border: none; text-align: right; vertical-align: top; color: #6b7280;">Signature & Comp. Stamp</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="height: 40px; border: none;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="padding: 1px 4px; font-size: 8pt; height: 14px; border: none; vertical-align: bottom;">Name :</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="padding: 1px 4px; font-size: 8pt; height: 14px; border: none; vertical-align: bottom;">Occupation :</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Metadata Bottom Row -->
        <table style="width: 100%; border-collapse: collapse; border: none; margin-top: 5px; font-family: Arial, sans-serif;">
            <tr>
                <td style="width: 50%; font-size: 7.5pt; border: none; padding: 0; color: #6b7280;">API/RC/PPIC/03/02</td>
                <td style="width: 50%; font-size: 7.5pt; text-align: right; border: none; padding: 0; color: #6b7280;">
                    Printed Date : {{ date('d-M-Y H:i:s') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- ===== CONTENT: Items Table ===== -->
    @if(!empty($shipment->description))
        <div style="font-size: 8.5pt; margin-bottom: 8px; font-family: Arial, sans-serif;">
            {{ $shipment->description }}
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: left;">No</th>
                <th style="width: 53%; text-align: left;">Product ID / Name</th>
                <th style="width: 12%; text-align: right;">Quantity</th>
                <th style="width: 10%; text-align: left; padding-left: 10px;">UoM</th>
                <th style="width: 20%; text-align: left;">PO Ref./Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $index => $line)
                @php
                    $poRef = !empty($line->line_poref) ? $line->line_poref : (!empty($line->ol_poref) ? $line->ol_poref : '');
                @endphp
                <tr>
                    <td style="text-align: left; padding-top: 5px; padding-bottom: 5px;">{{ $line->line ?? ($index + 1) }}</td>
                    <td style="text-align: left; padding-top: 5px; padding-bottom: 5px;">
                        <strong>{{ $line->product_code }}</strong><br>
                        <span style="color: #6b7280; font-family: Arial, sans-serif;">{{ $line->product_name }}</span>
                    </td>
                    <td style="text-align: right; padding-top: 5px; padding-bottom: 5px;">{{ number_format($line->qty, 0) }}</td>
                    <td style="text-align: left; padding-left: 10px; padding-top: 5px; padding-bottom: 5px;">{{ $line->uom_name }}</td>
                    <td style="text-align: left; padding-top: 5px; padding-bottom: 5px; font-family: Arial, sans-serif;">
                        {{ $poRef }}<br>
                        <span style="color: #6b7280;">{{ $line->description }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
