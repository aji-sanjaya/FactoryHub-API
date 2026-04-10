<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProcurementReportController extends Controller
{
    public function index()
    {
        return view('pages.procurement-report.index');
    }

    public function getData(Request $request)
    {
        // Require date filter or some criteria before processing
        // Validation handled in buildQuery implicitly via optional checks, 
        // but explicit validation is good practice if mandatory.

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

        $filename = 'procurement_report_' . date('Ymd_His') . '.xls';

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
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Req Num</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Req Date</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Product Code</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Product Name</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Req Qty</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>PO Num</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>PO Date</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Order Qty</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Receipt Num</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Receipt Date</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Receipt Qty</th>
                    </tr>
                  </thead><tbody>";

            foreach ($data as $index => $row) {
                // Ensure plain text for cells
                echo "<tr>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>" . ($row->req_num ?? '') . "</td>";
                echo "<td>" . ($row->req_date ?? '') . "</td>";
                echo "<td>" . ($row->product_code ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row->product_name ?? '') . "</td>";
                echo "<td>" . ($row->req_qty ?? 0) . "</td>";
                echo "<td>" . ($row->po_num ?? '') . "</td>";
                echo "<td>" . ($row->po_date ?? '') . "</td>";
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
        // Start from Requisition Line as the base transaction unit
        $query = DB::connection('idempiere')
            ->table('m_requisitionline as rl')
            ->join('m_requisition as r', 'r.m_requisition_id', '=', 'rl.m_requisition_id')
            ->leftJoin('m_product as p', 'p.m_product_id', '=', 'rl.m_product_id')

            // Link to PO Line via m_requisitionline_id reference in c_orderline
            ->leftJoin('c_orderline as ol', function ($join) {
                $join->on('ol.m_requisitionline_id', '=', 'rl.m_requisitionline_id');
            })
            ->leftJoin('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')

            // Link to Receipt Line via c_orderline_id reference in m_inoutline
            ->leftJoin('m_inoutline as iol', function ($join) {
                $join->on('iol.c_orderline_id', '=', 'ol.c_orderline_id');
            })
            ->leftJoin('m_inout as io', 'io.m_inout_id', '=', 'iol.m_inout_id')

            ->select([
                'r.documentno as req_num',
                'p.value as product_code',
                'p.name as product_name',
                'r.daterequired as req_date', // Added date
                'rl.qty as req_qty',

                'o.documentno as po_num',
                'o.dateordered as po_date', // Added date
                'ol.qtyentered as order_qty',

                'io.documentno as receipt_num',
                'io.movementdate as receipt_date', // Added date
                'iol.movementqty as receipt_qty',

                // Helper fields for ordering/filtering
                'r.daterequired',
                DB::raw('ROW_NUMBER() OVER (ORDER BY r.documentno DESC, rl.line ASC) as row_num')
            ]);

        // Apply Filters
        if ($request->filled('start_date')) {
            $query->whereDate('r.daterequired', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('r.daterequired', '<=', $request->end_date);
        }

        // Search Filter (Document Numbers or Product)
        if ($request->filled('search_query')) {
            $search = $request->search_query;
            $query->where(function ($q) use ($search) {
                $q->where('r.documentno', 'ilike', "%{$search}%")
                    ->orWhere('o.documentno', 'ilike', "%{$search}%")
                    ->orWhere('io.documentno', 'ilike', "%{$search}%")
                    ->orWhere('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }
}
