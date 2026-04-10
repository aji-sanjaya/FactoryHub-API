<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>AR Invoice {{ $invoice->documentno }}</title>
    <style>
        @page {
            size: A4 portrait; /* Ukuran kertas A4 dan portrait */
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
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
            width: 100%;
            margin-bottom: 5px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name-title {
            color: #EAB308;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .company-addr {
            font-size: 8pt;
            line-height: 1.3;
        }
        .double-line {
            border-top: 3px double #333;
            margin-top: 8px;
            margin-bottom: 20px;
        }
        
        /* Logo */
        .logo-box {
            width: 50px;
            height: 50px;
            border: 2px solid #FACC15;
            padding: 3px;
        }
        .logo-box-inner {
            background-color: #FACC15;
            width: 100%;
            height: 100%;
            color: white;
            font-weight: bold;
            font-size: 35px;
            line-height: 50px;
            text-align: center;
        }

        /* Invoice Main Details (Bill to & Invoice Info) */
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table td {
            vertical-align: top;
            line-height: 1.4;
        }

        /* Invoice Title */
        .invoice-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
            margin-top: 10px;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        .items-table th {
            font-weight: bold;
            text-align: center;
        }
        
        /* Totals / Summary Table */
        .totals-table {
            width: 350px;
            float: right;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .totals-table th, .totals-table td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        .totals-table th {
            font-weight: bold;
            text-align: center;
        }
        
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        
        /* Terbilang Table */
        .terbilang-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .terbilang-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-weight: bold;
        }
        .terbilang-value {
            font-style: italic;
        }
        
        /* Signatures & Bank Info */
        .signature-name {
            font-weight: bold;
            margin-bottom: 60px;
        }
        .signature-line {
            font-weight: bold;
            text-decoration: underline;
        }
        .signature-title {
            font-weight: bold;
        }
        
        .bank-info {
            margin-top: 30px;
            font-weight: bold;
            font-size: 8pt;
            line-height: 1.4;
        }
        
        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
    </style>
</head>
<body>

@php
    function penyebut($nilai) {
        $nilai = abs($nilai);
        $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " ". $huruf[$nilai];
        } else if ($nilai < 20) {
            $temp = penyebut($nilai - 10). " belas";
        } else if ($nilai < 100) {
            $temp = penyebut($nilai/10)." puluh". penyebut($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " seratus" . penyebut($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = penyebut($nilai/100) . " ratus" . penyebut($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " seribu" . penyebut($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = penyebut($nilai/1000) . " ribu" . penyebut($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = penyebut($nilai/1000000) . " juta" . penyebut($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = penyebut($nilai/1000000000) . " milyar" . penyebut(fmod($nilai,1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = penyebut($nilai/1000000000000) . " trilyun" . penyebut(fmod($nilai,1000000000000));
        }
        return $temp;
    }

    function terbilang($nilai) {
        if($nilai<0) {
            $hasil = "minus ". trim(penyebut($nilai));
        } else {
            $hasil = trim(penyebut($nilai));
        }
        return ucwords($hasil) . " Rupiah";
    }

    // Invoice Lines Definition
    $invoiceLines = [];
    if(isset($invoice->c_invoice_id)) {
        try {
            $invoiceLines = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_invoiceline as il')
                ->leftJoin('m_product as p', 'il.m_product_id', '=', 'p.m_product_id')
                ->leftJoin('c_orderline as ol', 'il.c_orderline_id', '=', 'ol.c_orderline_id')
                ->leftJoin('c_order as o', 'ol.c_order_id', '=', 'o.c_order_id')
                ->where('il.c_invoice_id', $invoice->c_invoice_id)
                ->select(
                    'il.qtyinvoiced as qty', 
                    'p.name as description', 
                    'il.description as line_desc', 
                    'o.documentno as po_number', 
                    'il.priceentered as unit_price', 
                    'il.linenetamt as amount'
                )
                ->orderBy('il.line')
                ->get();
        } catch (\Exception $e) { }
    }

    // Customer Location String Definition
    $customerLocation = [];
    if(isset($invoice->c_bpartner_location_id)) {
        try {
            $loc = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_bpartner_location as bpl')
                ->join('c_location as l', 'bpl.c_location_id', '=', 'l.c_location_id')
                ->leftJoin('c_region as r', 'l.c_region_id', '=', 'r.c_region_id')
                ->leftJoin('c_city as c', 'l.c_city_id', '=', 'c.c_city_id')
                ->where('bpl.c_bpartner_location_id', $invoice->c_bpartner_location_id)
                ->select('l.address1', 'l.address2', 'l.address3', 'l.city as l_city', 'c.name as city_name', 'r.name as region_name', 'l.postal')
                ->first();
            if($loc) {
                if($loc->address1) $customerLocation[] = $loc->address1;
                if($loc->address2) $customerLocation[] = $loc->address2;
                if($loc->address3) $customerLocation[] = $loc->address3;
                $cityTxt = $loc->city_name ?: $loc->l_city;
                $regionTxt = [];
                if($cityTxt) $regionTxt[] = $cityTxt;
                if($loc->region_name) $regionTxt[] = $loc->region_name;
                if(!empty($regionTxt)) {
                    $txt = implode(' - ', $regionTxt);
                    if($loc->postal) {
                        $txt .= ', ' . $loc->postal;
                    }
                    $customerLocation[] = $txt;
                }
            }
        } catch (\Exception $e) { }
    }

    // Calculate totals
    $subtotal = $invoice->totallines ?? 0;
    $grandTotal = $invoice->grandtotal ?? 0;
    
    if ($subtotal == 0 && $grandTotal > 0) {
        $subtotal = $grandTotal;
    }
    // Tax Diff for PPH 23
    $taxAmt = abs($grandTotal - $subtotal);

    // Number formatter to spaces like Image
    function formatRp($num) {
        return 'Rp ' . number_format($num, 0, ',', ' ');
    }
@endphp

    <div class="container">
        <!-- Logo & Header -->
        <table class="header-table">
            <tr>
                <td style="width: 12%;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px; max-width: 80px;">
                    @else
                        <div class="logo-box">
                            <div class="logo-box-inner">D</div>
                        </div>
                    @endif
                </td>
                <td style="width: 88%; text-align: center;">
                    <div class="company-name-title">PT. DHARMAMULIA PRIMA KARYA</div>
                    <div class="company-addr">
                        Jalan Jogja-Solo KM 12,5, Padukuhan Karang Kalasan, RT 001/RW 006<br>
                        Kel. Tirtomartani, Kec. Kalasan, Kab. Sleman, Daerah Istimewa Yogyakarta<br>
                        <b>Telp. 0274 &ndash; 2850888, Fax. 0274 &ndash; 497468</b>
                    </div>
                </td>
            </tr>
        </table>
        
        <div class="double-line"></div>

        <!-- Bill To Section -->
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <div>Bill to :</div>
                    <div style="font-weight: bold;">{{ $customer->customer_name ?? '-' }}</div>
                    @if(count($customerLocation) > 0)
                        @foreach($customerLocation as $locLine)
                            <div>{{ $locLine }}</div>
                        @endforeach
                    @else
                        <div>-</div>
                    @endif
                </td>
                <td style="width: 50%; padding-left: 20px;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="text-right" style="width: 45%; padding-right: 5px;">Invoice No :</td>
                            <td class="text-left" style="width: 55%;">{{ $invoice->documentno }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="padding-right: 5px;">Date :</td>
                            <td class="text-left">{{ $invoice->dateinvoiced ? \Carbon\Carbon::parse($invoice->dateinvoiced)->format('d-M-y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="padding-right: 5px;">Term Payment :</td>
                            <td class="text-left">{{ $paymentTerm ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="padding-right: 5px;">Periode :</td>
                            <td class="text-left">{{ $invoice->description ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Document Invoice Title -->
        <div class="invoice-title">INVOICE</div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 10%;">QTY Trip</th>
                    <th style="width: 40%;">Keterangan</th>
                    <th style="width: 15%;">No. PO</th>
                    <th style="width: 15%;">Unit Price</th>
                    <th style="width: 15%;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @if(count($invoiceLines) > 0)
                    @php 
                        $totalQty = 0; 
                    @endphp
                    @foreach($invoiceLines as $index => $line)
                        @php 
                            $totalQty += $line->qty;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center">{{ (float)$line->qty }}</td>
                            <td>{{ $line->description ?: $line->line_desc }}</td>
                            <td class="text-center">{{ $line->po_number ?? $poDocumentNo ?? '-' }}</td>
                            <td class="text-center">{{ formatRp($line->unit_price) }}</td>
                            <td class="text-center">{{ formatRp($line->amount) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td></td>
                        <td class="text-center" style="font-weight: bold;">{{ (float)$totalQty }}</td>
                        <td colspan="4"></td>
                    </tr>
                @else
                    <tr>
                        <td class="text-center">1</td>
                        <td class="text-center">1</td>
                        <td>Biaya Tagihan Jasa</td>
                        <td class="text-center">{{ $poDocumentNo ?? '-' }}</td>
                        <td class="text-center">{{ formatRp($subtotal) }}</td>
                        <td class="text-center">{{ formatRp($subtotal) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="text-center" style="font-weight: bold;">1</td>
                        <td colspan="4"></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Totals Table -->
        <div class="clearfix">
            <table class="totals-table">
                <tr>
                    <th class="text-center" style="width: 50%;">SUBTOTAL</th>
                    <th class="text-center" style="width: 50%;">{{ formatRp($subtotal) }}</th>
                </tr>
                <tr>
                    <th class="text-center">PPH 23 (2%)</th>
                    <th class="text-center">{{ formatRp($taxAmt) }}</th>
                </tr>
                <tr>
                    <th class="text-center">Total</th>
                    <th class="text-center">{{ formatRp($grandTotal) }}</th>
                </tr>
            </table>
        </div>

        <!-- Terbilang Text -->
        <table class="terbilang-table">
            <tr>
                <td style="width: 15%; text-align: left;">TERBILANG</td>
                <td style="width: 85%;" class="terbilang-value">{{ terbilang($grandTotal) }}</td>
            </tr>
        </table>

        <!-- Signature Section -->
        <div class="signature-name">PT.DHARMAMULIA PRIMA KARYA</div>
        <div class="signature-line">{{ $preparedBy ?? 'Heru Muskita' }}</div>
        <div class="signature-title">Direktur Administrasi</div>

        <!-- Bank Information Section -->
        <div class="bank-info">
            * Pembayaran di transfer ke :<br>
            Bank Mega<br>
            Rekening No : 00701466365<br>
            an. PT. Dharmamulia Prima Karya
        </div>

    </div>
</body>
</html>