<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>AR Invoice {{ $invoice->documentno }}</title>
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
            background-color: #FACC15;
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
            color: #EAB308;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 5px;
        }

        .company-address {
            text-align: center;
            font-size: 8pt;
            line-height: 1.2;
        }

        .doc-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            border-bottom: none;
        }

        .info-container {
            border: none;
            padding: 10px;
            margin-bottom: 15px;
        }

        .info-table td {
            vertical-align: top;
            padding: 3px 5px;
        }

        .info-label {
            font-weight: bold;
            width: 100px;
        }

        .colon {
            width: 10px;
            text-align: center;
        }

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

        .items-table td.qty,
        .items-table td.price,
        .items-table td.amount {
            text-align: right;
        }

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

        .provision-section {
            margin-top: 10px;
            font-size: 9pt;
            font-style: italic;
        }

        .provision-list {
            list-style-type: decimal;
            margin: 5px 0;
            padding-left: 20px;
        }

        .provision-list li {
            margin-bottom: 2px;
            padding-left: 5px;
        }

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

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .border-top {
            border-top: 2px solid #000;
        }

        .border-bottom {
            border-bottom: 2px solid #000;
        }

        /* Terbilang Table */
        .terbilang-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            margin-top: 15px;
        }

        .terbilang-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-weight: bold;
        }

        .terbilang-value {
            font-style: italic;
        }
    </style>
</head>

<body>
@php
    // Bridge data from AR Controller to AP Layout expectations
    $lines = [];
    if(isset($invoice->c_invoice_id)) {
        try {
            $lines = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_invoiceline as il')
                ->leftJoin('m_product as p', 'il.m_product_id', '=', 'p.m_product_id')
                ->leftJoin('c_uom as u', 'il.c_uom_id', '=', 'u.c_uom_id')
                ->where('il.c_invoice_id', $invoice->c_invoice_id)
                ->select(
                    'il.qtyinvoiced as qtyentered', 
                    'p.value as product_value',
                    'p.name as product_name', 
                    'il.description as description', 
                    'il.priceentered as priceentered', 
                    'il.linenetamt as amount',
                    'u.uomsymbol'
                )
                ->orderBy('il.line')
                ->get();
        } catch (\Exception $e) { }
    }

    $address1 = '';
    $address2 = '';
    $city = '';
    if(isset($invoice->c_bpartner_location_id)) {
        try {
            $loc = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_bpartner_location as bpl')
                ->join('c_location as l', 'bpl.c_location_id', '=', 'l.c_location_id')
                ->leftJoin('c_region as r', 'l.c_region_id', '=', 'r.c_region_id')
                ->leftJoin('c_city as c', 'l.c_city_id', '=', 'c.c_city_id')
                ->where('bpl.c_bpartner_location_id', $invoice->c_bpartner_location_id)
                ->select('l.address1', 'l.address2', 'l.city as l_city', 'c.name as city_name')
                ->first();
            if($loc) {
                $address1 = $loc->address1;
                $address2 = $loc->address2;
                $city = $loc->city_name ?: $loc->l_city;
            }
        } catch (\Exception $e) { }
    }

    $vendor = (object)[
        'vendor_name' => $customer->customer_name ?? '-',
        'address1' => $address1,
        'address2' => $address2,
        'city' => $city,
        'contact_name' => $contactName ?? '-'
    ];

    // (clientName and orgInfo are now passed directly from the Controller)

    $subTotal = $invoice->totallines ?? 0;
    $grandTotal = $invoice->grandtotal ?? 0;
    if ($subTotal == 0 && $grandTotal > 0) {
        $subTotal = $grandTotal;
    }
    // Tax Diff for PPH 23
    $taxAmt = abs($grandTotal - $subTotal);
    $withholdingTotal = $taxAmt;
    $taxAmount = 0; // if there's regular tax
    $invoice->supplierref = $invoice->poreference ?? '-';
@endphp
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <td style="padding: 0;">
                    <!-- Header -->
                    <table class="header-table">
                        <tr>
                            <td style="border-bottom: none; padding: 8px; width: 60%; vertical-align: top;">
                                <strong style="font-size: 13pt;">{{ $clientName ?? '' }}</strong><br>
                                @if(!empty($orgInfo))
                                    @if(!empty($orgInfo->address1))<span
                                    style="font-size: 9pt;">{{ $orgInfo->address1 }}</span><br>@endif
                                    @if(!empty($orgInfo->address2))<span
                                    style="font-size: 9pt;">{{ $orgInfo->address2 }}</span><br>@endif
                                    @if(!empty($orgInfo->address3))<span
                                    style="font-size: 9pt;">{{ $orgInfo->address3 }}</span>@endif
                                @endif
                            </td>
                            <td
                                style="border-bottom: none; padding: 8px; width: 40%; text-align: right; vertical-align: top;">
                                @if(!empty($logoBase64))
                                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px; width: auto;"><br>
                                @endif
                                <span class="doc-title">AR Invoice</span>
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
                                            <td class="info-label">Customer</td>
                                            <td class="colon">:</td>
                                            <td><strong>{{ $vendor->vendor_name ?? '-' }}</strong></td>
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
                                    </table>
                                </td>
                                <!-- Right Column -->
                                <td width="50%" style="border-left: 1px solid #000; padding-left: 10px;">
                                    <table>
                                        <tr>
                                            <td class="info-label">Invoice No</td>
                                            <td class="colon">:</td>
                                            <td>{{ $invoice->documentno }}</td>
                                        </tr>
                                        <tr>
                                            <td class="info-label">Date</td>
                                            <td class="colon">:</td>
                                            <td>{{ \Carbon\Carbon::parse($invoice->dateinvoiced)->format('d M Y') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="info-label">PO No</td>
                                            <td class="colon">:</td>
                                            <td>{{ $poDocumentNo ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="info-label">Payment Term</td>
                                            <td class="colon">:</td>
                                            <td>{{ $paymentTerm ?? '-' }}</td>
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
                                <th width="48%">Description</th>
                                <th width="8%" style="text-align: right;">Qty</th>
                                <th width="8%">UOM</th>
                                <th width="15%" style="text-align: right;">Price</th>
                                <th width="16%" style="text-align: right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $subTotalIter = 0; @endphp
                            @foreach($lines as $index => $line)
                                @php
                                    $lineAmount = $line->qtyentered * $line->priceentered;
                                    $subTotalIter += $lineAmount;
                                @endphp
                                <tr>
                                    <td style="padding-bottom: 0;">{{ $index + 1 }}</td>
                                    <td style="padding-bottom: 0; font-weight: bold;">
                                        {{ $line->product_value ?? '' }}
                                    </td>
                                    <td class="qty" style="padding-bottom: 0;">{{ number_format($line->qtyentered, 0) }}
                                    </td>
                                    <td style="padding-bottom: 0;">{{ $line->uomsymbol ?? ($line->uom_name ?? '') }}</td>
                                    <td class="price" style="padding-bottom: 0;">{{ number_format($line->priceentered, 2) }}
                                    </td>
                                    <td class="amount" style="padding-bottom: 0;">{{ number_format($lineAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 1px;"></td>
                                    <td colspan="5" style="padding-top: 1px; font-size: 8pt; color: #444;">
                                        {{ $line->product_name ?? ($line->description ?? '-') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="7" class="border-top"></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Totals + Notes -->
                    <table style="width: 100%; margin-top: 5px;" cellspacing="0">
                        <tr>
                            <!-- Notes (left 60%) -->
                            <td width="60%" style="vertical-align: top; padding-right: 10px;">
                                <div class="notes-section" style="margin-top: 0;">
                                    <strong>Note :</strong>
                                    <div class="notes-content">{{ trim($invoice->description ?? '') }}</div>
                                </div>
                            </td>
                            <!-- Totals (right 40%) -->
                            <td width="40%" style="vertical-align: top;">
                                <table class="totals-table" style="width: 100%;">
                                    <tr>
                                        <td class="totals-label">Total Amount :</td>
                                        <td>{{ number_format($subTotal, 2) }}</td>
                                    </tr>
                                    @if($withholdingTotal > 0)
                                        <tr>
                                            <td class="totals-label">PPh23 :</td>
                                            <td style="color: #c05621;">({{ number_format($withholdingTotal, 2) }})</td>
                                        </tr>
                                    @endif
                                    <tr class="totals-grand-total">
                                        <td class="totals-label">Grand Total :</td>
                                        <td><strong>{{ number_format($subTotal + ($taxAmount ?? 0) - $withholdingTotal, 2) }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- Terbilang Text -->
                    <table class="terbilang-table">
                        <tr>
                            <td style="width: 100%; font-style: italic; padding: 10px;">
                                Say# {{ $grandTotalWords }}#
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 15px; font-weight: bold; margin-bottom: 25px;">
                        PO Ref : {{ $poDocumentNo ?? '-' }}
                    </div>

                    @if(count($lines) > 5)
                        <div style="page-break-before: always;"></div>
                    @endif

                    <!-- Footer & Signature -->
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 65%; vertical-align: top; font-size: 9pt; line-height: 1.4;">
                                Pembayaran tagihan mohon ditransferkan ke rekening:<br>
                                PT. ADYAWINSA PLASTICS INDUSTRY<br>
                                PT Bank SMBC Indonesia Tbk., A/C No : 1011772001 (IDR)<br>
                                Piutang dianggap lunas bila dana sudah masuk ke rekening kami.
                            </td>
                            <td style="width: 35%; vertical-align: top; text-align: center;">
                                <div style="margin-bottom: 80px;">Your faithfully,</div>
                                
                                <div style="font-weight: bold; border-bottom: 1px solid #000; display: inline-block; min-width: 180px; padding-bottom: 2px;">Abraham Sulaeman</div>
                                <div style="font-size: 9pt; margin-top: 2px;">Admin Director</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>