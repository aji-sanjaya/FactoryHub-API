<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order {{ $order->documentno }}</title>
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
        .container {
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* Header */
        .header-table { 
            border-bottom: none; 
        }
        .header-table td {
            vertical-align: top;
            padding: 2px;
        }
        .logo-box {
            width: 60px;
            height: 60px;
            background-color: #FACC15; /* Yellow-400 equivalent */
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 40px;
            border: 1px solid #EAB308;
            text-align: center;
            line-height: 60px;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #EAB308; /* Yellow-500 equivalent */
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 5px;
        }
        .company-address {
            text-align: center;
            font-size: 8pt;
            line-height: 1.2;
        }
        .page-number {
            text-align: right;
            font-size: 8pt;
            vertical-align: bottom;
        }

        /* Title */
        .doc-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            border-bottom: none;
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
            font-weight: bold; /* Optional: make labels bold matches image? Image labels are normal weight but keys like "Order to" seem standard */
            width: 100px;
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
        .items-table td.qty, .items-table td.price, .items-table td.amount {
            text-align: right;
        }
        
        /* Totals */
        .totals-table {
            width: 100%;
            margin-top: 5px;
        }
        .totals-table td {
            padding: 5px;
            text-align: right;
        }
        .totals-label {
            font-weight: bold;
        }
        .totals-grand-total td {
            border-top: 0px solid #000;
            padding-top: 6px;
        }
        
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
            text-align: left;
            border: none;
            padding: 10px;
        }
        
        /* General Provision */
        .provision-section {
            margin-top: 10px;
            font-size: 9pt;
            font-style: italic;
        }
        .provision-list {
            list-style-type: decimal;
            margin: 5px 0;
            padding-left: 20px; /* Ensure space for numbers */
        }
        .provision-list li {
            margin-bottom: 2px;
            padding-left: 5px; /* slight gap from number */
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
    {{-- Wrapper table: thead repeats on every page (dompdf behavior) --}}
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <td style="padding: 0;">
                    <!-- Header -->
                    <table class="header-table">
                        <!-- Row 1: Company Address | Logo + Title -->
                            <tr>
                                <td style="border-bottom: none; padding: 8px; width: 60%; vertical-align: top;">
                                    <strong style="font-size: 13pt;">{{ $clientName ?? '' }}</strong><br>
                                    @if(!empty($orgInfo))
                                        @if(!empty($orgInfo->address1))<span style="font-size: 9pt;">{{ $orgInfo->address1 }}</span><br>@endif
                                        @if(!empty($orgInfo->address2))<span style="font-size: 9pt;">{{ $orgInfo->address2 }}</span><br>@endif
                                        @if(!empty($orgInfo->address3))<span style="font-size: 9pt;">{{ $orgInfo->address3 }}</span>@endif
                                    @endif
                                </td>
                                <td style="border-bottom: none; padding: 8px; width: 40%; text-align: right; vertical-align: top;">
                                    @if(!empty($logoBase64))
                                        <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px; width: auto;"><br>
                                    @endif  
                                     <span class="doc-title">Purchase Order</span>
                                </td>
                            </tr>
                    </table> 

                    <!-- Info Box -->
                    <div class="info-container">
                        <table class="info-table">
                            <tr>
                                <!-- Left Column -->
                                <td width="50%">
                                    <table>
                                        <tr>
                                            <td class="info-label">Order to</td>
                                            <td class="colon">:</td>
                                            <td><strong>{{ $vendor->vendor_name }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="info-label">Address</td>
                                            <td class="colon">:</td>
                                            <td>
                                                {{ $vendor->address1 }}<br>
                                                {{ $vendor->address2 }} {{ $vendor->city }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="info-label">Contact Person</td>
                                            <td class="colon">:</td>
                                            <td>{{ $vendor->contact_name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="info-label">Phone</td>
                                            <td class="colon">:</td>
                                            <td>{{ $vendor->phone ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </td>
                                <!-- Right Column -->
                                <td width="50%" style="border-left: 1px solid #000; padding-left: 10px;">
                                    <table>
                                        <tr>
                                            <td class="info-label">PO Number</td>
                                            <td class="colon">:</td>
                                            <td>{{ $order->documentno }}</td>
                                        </tr>
                                        <tr>
                                            <td class="info-label">Date</td>
                                            <td class="colon">:</td>
                                            <td>{{ \Carbon\Carbon::parse($order->dateordered)->format('d M Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="info-label">Invoice To</td>
                                            <td class="colon">:</td>
                                            <td>
                                                <strong>{{ $clientName ?? '' }}</strong><br>
                                                @if(!empty($orgInfo))
                                                    @if(!empty($orgInfo->address1)){{ $orgInfo->address1 }}<br>@endif
                                                    @if(!empty($orgInfo->address2)){{ $orgInfo->address2 }}<br>@endif
                                                    @if(!empty($orgInfo->address3)){{ $orgInfo->address3 }}@endif
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="info-label" style="padding-top: 15px;">NPWP</td>
                                            <td class="colon" style="padding-top: 15px;">:</td>
                                            <td style="padding-top: 15px;">{{ $orgInfo->taxid ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 0;">
                    <!-- Items Table -->
                    <table class="items-table" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%">NO</th>
                                <th width="35%">Product</th>
                                <th width="13%">Delivery</th>
                                <th width="8%" style="text-align: right;">Qty</th>
                                <th width="8%">UOM</th>
                                <th width="15%" style="text-align: right;">Price</th>
                                <th width="16%" style="text-align: right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $subTotal = 0; @endphp
                            @foreach($lines as $index => $line)
                                @php
                                    $lineAmount = $line->qtyentered * $line->priceentered;
                                    $subTotal += $lineAmount;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        {{ $line->product_value }}<br>
                                        <span style="font-size: 8pt;">{{ $line->product_name }}</span>
                                        @if($line->description)
                                            <br><span style="font-size: 8pt; color: #555;">{{ $line->description }}</span>
                                        @endif
                                    </td>
                                    <td>{{ !empty($line->datepromised) ? \Carbon\Carbon::parse($line->datepromised)->format('d M Y') : '' }}</td>
                                    <td class="qty">{{ number_format($line->qtyentered, 0) }}</td>
                                    <td>{{ $line->uomsymbol ?? $line->uom_name }}</td>
                                    <td class="price">{{ number_format($line->priceentered, 2) }}</td>
                                    <td class="amount">{{ number_format($lineAmount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr><td colspan="7" class="border-top"></td></tr>
                        </tbody>
                    </table>

                    <!-- Totals + Notes -->
                    <table style="width: 100%; margin-top: 5px;" cellspacing="0">
                        <tr>
                            <!-- Notes (left 60%) -->
                            <td width="60%" style="vertical-align: top; padding-right: 10px;">
                                <div class="notes-section" style="margin-top: 0;">
                                    <strong>Note :</strong>
                                    <div class="notes-content">{{ trim($order->description) }}</div>
                                </div>
                            </td>
                            <!-- Totals (right 40%) -->
                            <td width="40%" style="vertical-align: top;">
                                <table class="totals-table" style="width: 100%;">
                                    <tr>
                                        <td class="totals-label">Total Amount :</td>
                                        <td>{{ number_format($subTotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="totals-label">
                                            @if (isset($taxName) && $taxName)
                                                @if (strtolower($taxName) == 'standard')
                                                    PPN ({{ $taxRate }}%)
                                                @else
                                                    {{ $taxName }}
                                                @endif
                                            @else
                                                Non PPN
                                            @endif
                                            :</td>
                                        <td>{{ number_format($taxAmount ?? 0, 2) }}</td>
                                    </tr>
                                    @php $withholdingTotal = $withholdingTotal ?? 0; @endphp
                                    @if($withholdingTotal > 0)
                                    <tr>
                                        <td class="totals-label">PPh23 :</td>
                                        <td style="color: #c05621;">({{ number_format($withholdingTotal, 2) }})</td>
                                    </tr>
                                    @endif
                                    <tr class="totals-grand-total">
                                        <td class="totals-label">Grand Total :</td>
                                        <td><strong>{{ number_format($subTotal + ($taxAmount ?? 0) - $withholdingTotal, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- General Provision + Signatures (page break if items > 5) -->
                    @if(count($lines) > 5)
                        <div style="page-break-before: always;"></div>
                    @endif

                    <div class="provision-section">
                        <div>General Provision:</div>
                        <ol class="provision-list" style="margin-top: 5px; padding-left: 20px;">
                            <li>Payments will be scheduled every 28th after due.</li>
                            <li>Good which are not meet with the requirement (specification and condition), will be returned.</li>
                            <li>For payment purpose, please attach this purchase order in your invoice.</li>
                            <li>Field this Purchase Order number in your delivery order.</li>
                            <li>Invoice will be received at 10:00 - 15:00 PM every Monday &amp; Thursday.</li>
                            <li>Delivery product will be received under 15:00 PM every work day.</li>
                        </ol>
                    </div>

                    <!-- Signatures -->
                    <table class="signature-table">
                        <tr>
                            <td>
                                <div class="sig-title">Prepared By</div>
                                @if(isset($preparedQr) && $preparedQr)
                                    <div style="margin-bottom: 5px;"><img src="{{ $preparedQr }}" alt="QR" style="height: 60px; width: 60px;"></div>
                                @else
                                    <div style="height: 65px;"></div>
                                @endif
                                <div class="sig-name">{{ $preparedBy ?? '..................' }}</div>
                                <div class="sig-role" style="font-weight: bold;">Purchasing</div>
                                <div style="font-size: 7pt; font-style: italic;">({{ $preparedDate ?? '' }})</div>
                            </td>
                            <td>
                                <div class="sig-title">Checked By</div>
                                @if(isset($checkedQr) && $checkedQr)
                                    <div style="margin-bottom: 5px;"><img src="{{ $checkedQr }}" alt="QR" style="height: 60px; width: 60px;"></div>
                                @else
                                    <div style="height: 65px;"></div>
                                @endif
                                <div class="sig-name">{{ $checkedBy ?? '..................' }}</div>
                                <div class="sig-role" style="font-weight: bold;">FAT</div>
                                <div style="font-size: 7pt; font-style: italic;">({{ $checkedDate ?? '' }})</div>
                            </td>
                            <td>
                                <div class="sig-title">Approved By</div>
                                @if(isset($approvedQr) && $approvedQr)
                                    <div style="margin-bottom: 5px;"><img src="{{ $approvedQr }}" alt="QR" style="height: 60px; width: 60px;"></div>
                                @else
                                    <div style="height: 65px;"></div>
                                @endif
                                <div class="sig-name">{{ $approvedBy ?? '..................' }}</div>
                                <div class="sig-role" style="font-weight: bold;">Director</div>
                                <div style="font-size: 7pt; font-style: italic;">({{ $approvedDate ?? '' }})</div>
                            </td>
                            <td>
                                <div class="sig-title">&nbsp;</div>
                                <div style="height: 65px;"></div>
                                <div class="sig-name">&nbsp;</div>
                                <div class="sig-role" style="font-weight: bold;">Supplier</div>
                                <div style="font-size: 7pt; font-style: italic;">&nbsp;</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

</body>
</html>
