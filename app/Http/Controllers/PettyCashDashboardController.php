<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idempiere\DpkPettycashRequest;
use App\Models\Idempiere\DpkPettycashClosing;
use App\Models\Idempiere\ADOrg;
use App\Models\Idempiere\ADUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PettyCashDashboardController extends Controller
{
    /**
     * Display the petty cash dashboard view.
     */
    public function index(Request $request)
    {
        $clientId = Session::get('idempiere_client');

        // Capture Selected Month (Format: YYYY-MM), defaulting to Current Month
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $dateStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $dateEnd = Carbon::createFromFormat('Y-m', $selectedMonth)->endOfMonth();

        // Total request value
        $totalRequest = DB::connection('idempiere')
            ->table('dpk_pettycash_request')
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('datetrx', [$dateStart, $dateEnd])
            ->sum('totallines') ?? 0;

        // Total closing value
        $totalClosing = DB::connection('idempiere')
            ->table('dpk_pettycash_closing')
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('datetrx', [$dateStart, $dateEnd])
            ->sum('totallines') ?? 0;

        // Balance 
        $currentBalance = $totalRequest - $totalClosing;

        // Pending Requests (Not Closed)
        $pendingRequests = DB::connection('idempiere')
            ->table('dpk_pettycash_request')
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y')
            ->whereNotIn('docstatus', ['CL', 'VO', 'RE'])
            ->whereBetween('datetrx', [$dateStart, $dateEnd])
            ->count();

        $kpis = [
            'totalRequest' => $totalRequest,
            'totalClosing' => $totalClosing,
            'currentBalance' => $currentBalance,
            'pendingRequests' => $pendingRequests
        ];

        // Chart Data (Last 6 months)
        $months = collect();
        $requestTrend = collect();
        $closingTrend = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $months->push($date->format('M Y'));

            // Requests in that month
            $reqSum = DB::connection('idempiere')
                ->table('dpk_pettycash_request')
                ->where('ad_client_id', $clientId)
                ->where('isactive', 'Y')
                ->whereIn('docstatus', ['CO', 'CL'])
                ->whereBetween('datetrx', [$monthStart, $monthEnd])
                ->sum('totallines') ?? 0;
            $requestTrend->push((float) $reqSum);

            // Closings in that month
            $closeSum = DB::connection('idempiere')
                ->table('dpk_pettycash_closing')
                ->where('ad_client_id', $clientId)
                ->where('isactive', 'Y')
                ->whereIn('docstatus', ['CO', 'CL'])
                ->whereBetween('datetrx', [$monthStart, $monthEnd])
                ->sum('totallines') ?? 0;
            $closingTrend->push((float) $closeSum);
        }

        $charts = [
            'months' => $months->toArray(),
            'requestTrend' => $requestTrend->toArray(),
            'closingTrend' => $closingTrend->toArray()
        ];

        // Recent Activities (Combine requesting and closings) in the selected month
        $recentReqs = DB::connection('idempiere')
            ->table('dpk_pettycash_request')
            ->selectRaw("'Request' as type, documentno, datetrx as date, docstatus, totallines as amount")
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y')
            ->whereBetween('datetrx', [$dateStart, $dateEnd]);

        $recentActivities = DB::connection('idempiere')
            ->table('dpk_pettycash_closing')
            ->selectRaw("'Closing' as type, documentno, datetrx as date, docstatus, totallines as amount")
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y')
            ->whereBetween('datetrx', [$dateStart, $dateEnd])
            ->union($recentReqs)
            ->orderBy('date', 'desc')
            ->paginate(10)
            ->appends($request->query());

        return view('pages.dashboard.petty-cash', compact('kpis', 'charts', 'recentActivities', 'selectedMonth'));
    }

    public function export(Request $request)
    {
        $clientId = Session::get('idempiere_client');
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $dateStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $dateEnd = Carbon::createFromFormat('Y-m', $selectedMonth)->endOfMonth();

        $recentReqs = DB::connection('idempiere')
            ->table('dpk_pettycash_request')
            ->selectRaw("'Request' as type, documentno, datetrx as date, docstatus, totallines as amount")
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y')
            ->whereBetween('datetrx', [$dateStart, $dateEnd]);

        $data = DB::connection('idempiere')
            ->table('dpk_pettycash_closing')
            ->selectRaw("'Closing' as type, documentno, datetrx as date, docstatus, totallines as amount")
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y')
            ->whereBetween('datetrx', [$dateStart, $dateEnd])
            ->union($recentReqs)
            ->orderBy('date', 'desc')
            ->get();

        $filename = 'petty_cash_transactions_' . date('Ymd_His') . '.xls';

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
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Type</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Document No</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Date</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Status</th>
                        <th style='background-color: #f0f0f0; font-weight: bold;'>Amount</th>
                    </tr>
                  </thead><tbody>";

            foreach ($data as $row) {
                // Map status
                $statusLabel = match ($row->docstatus) {
                    'CO' => 'Completed',
                    'IP' => 'In Progress',
                    'DR' => 'Draft',
                    'CL' => 'Closed',
                    default => $row->docstatus
                };

                echo "<tr>";
                echo "<td>" . $row->type . "</td>";
                echo "<td>" . $row->documentno . "</td>";
                echo "<td>" . date('d M Y', strtotime($row->date)) . "</td>";
                echo "<td>" . $statusLabel . "</td>";
                echo "<td>" . ($row->amount ?? 0) . "</td>";
                echo "</tr>";
            }

            echo "</tbody></table></body></html>";
        }, 200, $headers);
    }
}
