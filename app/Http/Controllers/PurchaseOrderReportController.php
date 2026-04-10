<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderReportController extends Controller
{
    public function index()
    {
        // Get Suppliers for filter
        $suppliers = DB::connection('idempiere')
            ->table('c_bpartner')
            ->where('isvendor', 'Y')
            ->where('isactive', 'Y')
            ->select('c_bpartner_id as id', 'name as text')
            ->orderBy('name')
            ->get();

        return view('pages.purchase-order-report.index', compact('suppliers'));
    }

    public function getData(Request $request)
    {
        $query = $this->buildQuery($request);

        // Pagination
        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        return response()->json($data);
    }

    public function export(Request $request)
    {
        $query = $this->buildQuery($request);
        $data = $query->get();

        $filename = 'po_report_' . date('Ymd_His') . '.xls';

        $headers = [
            "Content-Type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        return response()->stream(function () use ($data) {
            echo "<html><head><meta charset='UTF-8'></head><body>";
            echo "<table border='1'>";
            echo "<thead>
                    <tr>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>No</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>PO Num</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>PO Date</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Supplier</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Product Code</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Product Name</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Order Qty</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Receipt Num</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Receipt Date</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Receipt Qty</th>
                    </tr>
                  </thead><tbody>";

            foreach ($data as $index => $row) {
                echo "<tr>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>" . ($row->po_num ?? '') . "</td>";
                echo "<td>" . ($row->po_date ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row->supplier_name ?? '') . "</td>";
                echo "<td>" . ($row->product_code ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row->product_name ?? '') . "</td>";
                echo "<td>" . ($row->order_qty ?? 0) . "</td>";
                echo "<td>" . ($row->receipt_num ?? '') . "</td>";
                echo "<td>" . ($row->receipt_date ?? '') . "</td>";
                echo "<td>" . ($row->receipt_qty ?? 0) . "</td>";
                echo "</tr>";
            }

            echo "</tbody></table></body></html>";
        }, 200, $headers);
    }

    private function buildQuery(Request $request)
    {
        // Start from Order Line
        $query = DB::connection('idempiere')
            ->table('c_orderline as ol')
            ->join('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
            ->leftJoin('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'o.c_bpartner_id')
            ->leftJoin('m_product as p', 'p.m_product_id', '=', 'ol.m_product_id')

            // Link to Receipt Line via c_orderline_id
            ->leftJoin('m_inoutline as iol', function ($join) {
                $join->on('iol.c_orderline_id', '=', 'ol.c_orderline_id');
            })
            ->leftJoin('m_inout as io', 'io.m_inout_id', '=', 'iol.m_inout_id')

            ->select([
                'o.documentno as po_num',
                'o.dateordered as po_date',
                'bp.name as supplier_name',

                'p.value as product_code',
                'p.name as product_name',
                'ol.qtyentered as order_qty',

                'io.documentno as receipt_num',
                'io.movementdate as receipt_date',
                'iol.movementqty as receipt_qty',

                // Helper fields
                'o.dateordered',
                DB::raw('ROW_NUMBER() OVER (ORDER BY o.dateordered DESC, o.documentno DESC, ol.line ASC) as row_num')
            ]);

        // Filter: Date Ordered (PO Date)
        if ($request->filled('start_date')) {
            $query->whereDate('o.dateordered', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('o.dateordered', '<=', $request->end_date);
        }

        // Filter: Supplier
        if ($request->filled('c_bpartner_id')) {
            $query->where('o.c_bpartner_id', $request->c_bpartner_id);
        }

        // Search Filter
        if ($request->filled('search_query')) {
            $search = $request->search_query;
            $query->where(function ($q) use ($search) {
                $q->where('o.documentno', 'ilike', "%{$search}%")
                    ->orWhere('io.documentno', 'ilike', "%{$search}%")
                    ->orWhere('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%")
                    ->orWhere('bp.name', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }
}
