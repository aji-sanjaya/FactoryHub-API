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
            margin-bottom: 2px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
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
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td width="15%" align="center">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 60px; width: auto;">
                @else
                    <!-- CSS Logo representing the yellow D -->
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

    <div class="doc-title">Purchase Order</div>

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
                                <strong>Dharmamulia Prima Karya</strong><br>
                                Jl. Jababeka IV-E No.81b
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label" style="padding-top: 15px;">Payment Term</td>
                            <td class="colon" style="padding-top: 15px;">:</td>
                            <td style="padding-top: 15px;">{{ $paymentTerm }}</td>
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
                <th width="45%">Description</th>
                <th width="10%" class="text-right">Qty</th>
                <th width="10%">UOM</th>
                <th width="15%" class="text-right">Price</th>
                <th width="15%" class="text-right">Amount</th>
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
                        {{ $line->product_name }} {{ $line->product_value ? '- ' . $line->product_value : '' }}
                        @if($line->description)
                            <br><span style="color: #555;">{{ $line->description }}</span>
                        @endif
                    </td>
                    <td class="qty">{{ number_format($line->qtyentered, 0) }}</td>
                    <td>{{ $line->uomsymbol ?? $line->uom_name }}</td>
                    <td class="price">{{ number_format($line->priceentered, 2) }}</td>
                    <td class="amount">{{ number_format($lineAmount, 2) }}</td>
                </tr>
            @endforeach
            <!-- Spacer to separate content from totals if needed -->
             <tr><td colspan="6" class="border-top"></td></tr>
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td width="70%"></td>
            <td width="15%" class="totals-label">Sub Total :</td>
            <td width="15%">{{ number_format($subTotal, 2) }}</td>
        </tr>
        <tr>
            <td></td>
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
        <tr>
            <td></td>
            <td class="totals-label">Total :</td>
            <td>{{ number_format($subTotal + ($taxAmount ?? 0), 2) }}</td>
        </tr>
    </table>

    <!-- Notes -->
    <div class="notes-section">
        <strong>Note :</strong>
        <div class="notes-content">
            {{ $order->description }}
        </div>
    </div>

    <!-- General Provision -->
    <div class="provision-section">
        <div>General Provision:</div>
        <ol class="provision-list" style="margin-top: 5px; padding-left: 20px;">
            <li>Payment will be due every 10 & 20 day of the month</li>
            <li>Goods that do not meet the requirements (specifications and conditions) will be returned</li>
            <li>The Service must be thoroughly checked by the vendor</li>
            <li>Please fill in this purchase order number on your delivery order</li>
            <li>All purchases of services will be subject to tax in accordance with the applicable regulations (PPh 23)</li>
        </ol>
    </div>

    <!-- Signatures -->
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

</body>
</html>
