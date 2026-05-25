<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Price & Qty History</title>
    <style>
        @page {
            margin: 0.8cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8pt;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 5px;
        }
        .header-table td {
            vertical-align: bottom;
            padding: 2px 0;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-info {
            text-align: right;
            font-size: 7.5pt;
            line-height: 1.4;
        }
        .product-block {
            page-break-inside: avoid;
            margin-bottom: 30px;
        }
        .product-title {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 6px;
            color: #000;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .history-table th, .history-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }
        .history-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }
        .history-table td {
            text-align: right;
        }
        .history-table td.vendor-name {
            text-align: left;
            font-weight: bold;
            background-color: #ffffff;
            font-size: 8pt;
        }
        .history-table td.param-name {
            text-align: left;
            font-size: 7.5pt;
            background-color: #fafafa;
        }
        .history-table td.avg-val {
            font-weight: bold;
            background-color: #f9fafb;
        }
        .history-table tr.average-row td {
            font-weight: bold;
            background-color: #f3f4f6;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
        }
        .history-table tr.average-row td.avg-label {
            text-align: left;
            font-style: italic;
        }
        
        /* Helpers */
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .font-bold { font-weight: bold !important; }
    </style>
</head>
<body>

    {{-- Main Header --}}
    <table class="header-table">
        <tr>
            <td class="title">Price & Qty History</td>
            <td class="meta-info">
                <strong>PO#:</strong> {{ $order->documentno }} / <strong>Date:</strong> {{ \Carbon\Carbon::parse($order->dateordered)->format('d-M-y') }}<br>
                Printed : {{ $printedDate }} ~ Printed By: {{ $printedBy }}
            </td>
        </tr>
    </table>

    {{-- Product Blocks --}}
    @foreach($reportData as $data)
        <div class="product-block">
            {{-- Product Title Header --}}
            <div class="product-title">
                {{ $data['product_value'] }} [ {{ $data['product_name'] }} ]
                &nbsp;&nbsp;** Qty WH Finish Good : {{ number_format($data['qty_finish_good'], 0) }} PCS
            </div>

            {{-- History Table --}}
            @if(!empty($data['vendors']))
                <table class="history-table">
                    <thead>
                        <tr>
                            <th rowspan="2" colspan="2" style="width: 40%;"></th>
                            @if($y1 == $y2 && $y2 == $y3)
                                <th colspan="3">{{ $y1 }}</th>
                            @elseif($y1 == $y2)
                                <th colspan="2">{{ $y1 }}</th>
                                <th colspan="1">{{ $y3 }}</th>
                            @elseif($y2 == $y3)
                                <th colspan="1">{{ $y1 }}</th>
                                <th colspan="2">{{ $y3 }}</th>
                            @else
                                <th colspan="1">{{ $y1 }}</th>
                                <th colspan="1">{{ $y2 }}</th>
                                <th colspan="1">{{ $y3 }}</th>
                            @endif
                            <th rowspan="2" style="width: 15%;">Average</th>
                        </tr>
                        <tr>
                            <th style="width: 15%;">{{ $m1Name }}</th>
                            <th style="width: 15%;">{{ $m2Name }}</th>
                            <th style="width: 15%;">{{ $m3Name }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['vendors'] as $vendorId => $vData)
                            {{-- Row 1: Price IDR --}}
                            <tr>
                                <td rowspan="4" class="vendor-name">{{ $vData['vendor_name'] }}</td>
                                <td class="param-name">Price IDR</td>
                                <td>{{ $vData['buckets'][1]['price'] !== null ? number_format($vData['buckets'][1]['price'], 0) : '' }}</td>
                                <td>{{ $vData['buckets'][2]['price'] !== null ? number_format($vData['buckets'][2]['price'], 0) : '' }}</td>
                                <td>{{ $vData['buckets'][3]['price'] !== null ? number_format($vData['buckets'][3]['price'], 0) : '' }}</td>
                                <td class="avg-val">{{ $vData['row_average']['price'] !== null ? number_format($vData['row_average']['price'], 0) : '' }}</td>
                            </tr>
                            {{-- Row 2: Qty PO --}}
                            <tr>
                                <td class="param-name">Qty PO</td>
                                <td>{{ $vData['buckets'][1]['qty_po'] !== null ? number_format($vData['buckets'][1]['qty_po'], 0) : '' }}</td>
                                <td>{{ $vData['buckets'][2]['qty_po'] !== null ? number_format($vData['buckets'][2]['qty_po'], 0) : '' }}</td>
                                <td>{{ $vData['buckets'][3]['qty_po'] !== null ? number_format($vData['buckets'][3]['qty_po'], 0) : '' }}</td>
                                <td class="avg-val">{{ $vData['row_average']['qty_po'] !== null ? number_format($vData['row_average']['qty_po'], 0) : '' }}</td>
                            </tr>
                            {{-- Row 3: Qty RR --}}
                            <tr>
                                <td class="param-name">Qty RR</td>
                                <td>{{ $vData['buckets'][1]['qty_rr'] !== null ? number_format($vData['buckets'][1]['qty_rr'], 0) : '' }}</td>
                                <td>{{ $vData['buckets'][2]['qty_rr'] !== null ? number_format($vData['buckets'][2]['qty_rr'], 0) : '' }}</td>
                                <td>{{ $vData['buckets'][3]['qty_rr'] !== null ? number_format($vData['buckets'][3]['qty_rr'], 0) : '' }}</td>
                                <td class="avg-val">{{ $vData['row_average']['qty_rr'] !== null ? number_format($vData['row_average']['qty_rr'], 0) : '' }}</td>
                            </tr>
                            {{-- Row 4: TOP --}}
                            <tr>
                                <td class="param-name">TOP</td>
                                <td>{{ $vData['buckets'][1]['top'] !== null ? number_format($vData['buckets'][1]['top'], 0) : '' }}</td>
                                <td>{{ $vData['buckets'][2]['top'] !== null ? number_format($vData['buckets'][2]['top'], 0) : '' }}</td>
                                <td>{{ $vData['buckets'][3]['top'] !== null ? number_format($vData['buckets'][3]['top'], 0) : '' }}</td>
                                <td class="avg-val">{{ $vData['row_average']['top'] !== null ? number_format($vData['row_average']['top'], 0) : '' }}</td>
                            </tr>
                        @endforeach

                        {{-- Footer: Price IDR Average row --}}
                        <tr class="average-row">
                            <td colspan="2" class="avg-label">Price IDR Average</td>
                            <td>{{ $data['month_averages'][1] !== null ? number_format($data['month_averages'][1], 0) : '' }}</td>
                            <td>{{ $data['month_averages'][2] !== null ? number_format($data['month_averages'][2], 0) : '' }}</td>
                            <td>{{ $data['month_averages'][3] !== null ? number_format($data['month_averages'][3], 0) : '' }}</td>
                            <td>{{ $data['month_averages']['grand_average'] !== null ? number_format($data['month_averages']['grand_average'], 0) : '' }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <table class="history-table">
                    <tbody>
                        <tr>
                            <td class="text-center font-bold" style="padding: 15px; color: #555; background-color: #fafafa;">
                                No Purchase Price History available for this product in the last 3 months.
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

</body>
</html>
