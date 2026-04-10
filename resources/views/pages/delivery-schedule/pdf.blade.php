<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Delivery Schedule {{ $deliverySchedule->documentno }}</title>
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
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px; width: auto;">
                    @else
                        <div class="logo-box">D</div>
                    @endif
                </td>
                <td class="title-cell">
                    <h1>SALES ORDER</h1>
                    <h2>PT. DHARMAMULIA PRIMA KARYA</h2>
                </td>
                <td class="info-cell">
                    <div class="info-row"><span class="info-label">Doc. No</span>: {{ $deliverySchedule->documentno }}</div>
                    <div class="info-row"><span class="info-label">Eff. Date</span>: {{ date('d M Y', strtotime($deliverySchedule->datedoc)) }}</div>
                    <div class="info-row"><span class="info-label">Revision</span>: 0</div>
                    <div class="info-row"><span class="info-label">Page</span>: 1</div>
                </td>
            </tr>
        </table>

        <!-- Sub Header -->
        <div class="sub-header">
            <table>
                <tr>
                    <td width="15%">Order No</td>
                    <td width="35%">: {{ $deliverySchedule->documentno }}</td>
                    <td width="20%">Expected Delivery</td>
                    <td width="30%">: {{ date('d M Y', strtotime($deliverySchedule->datepromised)) }}</td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td>: {{ date('d M Y', strtotime($deliverySchedule->dateordered)) }}</td>
                    <td>PO Number</td>
                    <td>: {{ $deliverySchedule->poreference }}</td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table" cellspacing="0">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="15%">Item</th>
                    <th width="35%">Specification</th>
                    <th width="15%" class="text-right">Qty</th>
                    <th width="15%" class="text-right">Price</th>
                    <th width="15%" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach($lines as $index => $line)
                    @php 
                        $subtotal = $line->qty * $line->priceactual; 
                        $total += $subtotal;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $line->product_code }}</td>
                        <td>
                            {{ $line->product_name }}<br>
                            <span style="font-size: 8pt; color: #666;">{{ $line->description }}</span>
                        </td>
                        <td class="text-right">{{ number_format($line->qty, 0) }} {{ $line->uom_name }}</td>
                        <td class="text-right">{{ number_format($line->priceactual, 2) }}</td>
                        <td class="text-right">{{ number_format($subtotal, 2) }}</td>
                    </tr>
                @endforeach
                
                <!-- Spacer rows if needed -->
                @for($i = count($lines); $i < 5; $i++)
                    <tr>
                        <td colspan="6" style="height: 20px;">&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right">Total :</td>
                    <td class="text-right">{{ number_format($total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-note">
                <strong>Note :</strong><br>
                {{ $deliverySchedule->description ?: '-' }}
            </div>

            <div class="disclaimer">
                - Form ini bukan permintaan pembayaran atau order pembelian ke supplier, hanya digunakan untuk internal perusahaan<br>
                - Form ini harus diisi lengkap dan benar disertai dengan supporting dokumen yang diperlukan<br>
                - Purchase Request harus sudah mendapatkan approval dari authorized person sebelum disampaikan ke Purchasing<br>
                - Apabila pengisian form ini tidak lengkap, maka akan kami kembalikan ke user untuk dilengkapi
            </div>

            <!-- Signatures -->
            <table class="signature-table">
                <tr>
                    <td width="33%">
                        <div class="sig-label">Prepared By</div>
                        @if($preparedQr) 
                            <div class="sig-qr"><img src="{{ $preparedQr }}" alt="QR" style="height: 80px; width: 80px;"></div>
                        @else
                            <div class="sig-qr" style="height: 80px;"></div>
                        @endif
                        <div class="sig-name">{{ $preparedBy ?? 'Mulyadi' }}</div>
                        <div class="sig-date">{{ $preparedDate }}</div>
                    </td>
                    <td width="33%">
                        <div class="sig-label"></div>
                        @if($checkedQr) 
                            <div class="sig-qr"><img src="{{ $checkedQr }}" alt="QR" style="height: 80px; width: 80px;"></div>
                        @else
                            <div class="sig-qr" style="height: 80px;"></div>
                        @endif
                        <div class="sig-name">{{ $checkedBy ?? '' }}</div>
                         <div class="sig-date">{{ $checkedDate ?? '' }}</div>
                    </td>
                    <td width="33%">
                        <div class="sig-label"></div>
                        @if($approvedQr) 
                            <div class="sig-qr"><img src="{{ $approvedQr }}" alt="QR" style="height: 80px; width: 80px;"></div>
                        @else
                            <div class="sig-qr" style="height: 80px;"></div>
                        @endif
                        <div class="sig-name">{{ $approvedBy ?? '' }}</div>
                         <div class="sig-date">{{ $approvedDate ?? '' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
