<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcurementDashboardController extends Controller
{
    public function index()
    {
        $clientId = session('idempiere_client');
        $orgId = session('idempiere_org');

        if (!$clientId) {
            return redirect()->route('signin');
        }

        // 1. KPIs Calculations
        $startOfMonth = Carbon::now()->startOfMonth()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateTimeString();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth()->toDateTimeString();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth()->toDateTimeString();

        // PO Total Spend (Current Month)
        $poSpend = DB::connection('idempiere')
            ->table('c_order')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'N')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('dateordered', [$startOfMonth, $endOfMonth]);

        if ($orgId > 0)
            $poSpend->where('ad_org_id', $orgId);
        $currentMonthSpend = $poSpend->sum('grandtotal');

        // PO Total Spend (Last Month)
        $poSpendLast = DB::connection('idempiere')
            ->table('c_order')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'N')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('dateordered', [$startOfLastMonth, $endOfLastMonth]);

        if ($orgId > 0)
            $poSpendLast->where('ad_org_id', $orgId);
        $lastMonthSpend = $poSpendLast->sum('grandtotal');

        // Percentage Change
        $spendChange = $lastMonthSpend > 0 ? (($currentMonthSpend - $lastMonthSpend) / $lastMonthSpend) * 100 : 0;

        // PR Status Counts
        $prCounts = DB::connection('idempiere')
            ->table('m_requisition')
            ->where('ad_client_id', $clientId)
            ->select('docstatus', DB::raw('count(*) as total'))
            ->groupBy('docstatus');

        if ($orgId > 0)
            $prCounts->where('ad_org_id', $orgId);
        $prStats = $prCounts->get()->pluck('total', 'docstatus');

        // PO Status Counts
        $poCounts = DB::connection('idempiere')
            ->table('c_order')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'N')
            ->select('docstatus', DB::raw('count(*) as total'))
            ->groupBy('docstatus');

        if ($orgId > 0)
            $poCounts->where('ad_org_id', $orgId);
        $poStats = $poCounts->get()->pluck('total', 'docstatus');

        // GR Pending Delivery: Count POs where at least one line is partially delivered
        $pendingQuery = DB::connection('idempiere')
            ->table('c_orderline as ol')
            ->join('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
            ->where('o.ad_client_id', $clientId)
            ->where('o.issotrx', 'N')
            ->where('o.docstatus', 'CO')
            ->whereRaw('ol.qtyordered > ol.qtydelivered');

        if ($orgId > 0)
            $pendingQuery->where('o.ad_org_id', $orgId);
        $pendingCount = $pendingQuery->distinct('o.c_order_id')->count();

        // 2. Charts Data

        // Monthly Spend Trend (Last 6 Months)
        $months = [];
        $spendTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');

            $q = DB::connection('idempiere')
                ->table('c_order')
                ->where('ad_client_id', $clientId)
                ->where('issotrx', 'N')
                ->whereIn('docstatus', ['CO', 'CL', 'IP'])
                ->whereBetween('dateordered', [$month->startOfMonth()->toDateTimeString(), $month->endOfMonth()->toDateTimeString()]);

            if ($orgId > 0)
                $q->where('ad_org_id', $orgId);
            $spendTrend[] = (float) $q->sum('grandtotal');
        }

        // Top 5 Suppliers
        $topSuppliers = DB::connection('idempiere')
            ->table('c_order as o')
            ->join('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'o.c_bpartner_id')
            ->where('o.ad_client_id', $clientId)
            ->where('o.issotrx', 'N')
            ->whereIn('o.docstatus', ['CO', 'CL'])
            ->select('bp.name', DB::raw('SUM(o.grandtotal) as total_spend'))
            ->groupBy('bp.name')
            ->orderBy('total_spend', 'desc')
            ->limit(5);

        if ($orgId > 0)
            $topSuppliers->where('o.ad_org_id', $orgId);
        $topSuppliersData = $topSuppliers->get();

        // 3. Recent Activities
        $recentDocs = DB::connection('idempiere')
            ->table('c_order as o')
            ->leftJoin('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'o.c_bpartner_id')
            ->where('o.ad_client_id', $clientId)
            ->where('o.issotrx', 'N')
            ->select('o.documentno', 'o.dateordered as date', 'o.grandtotal', 'o.docstatus', 'bp.name as supplier')
            ->orderBy('o.created', 'desc')
            ->limit(5);

        if ($orgId > 0)
            $recentDocs->where('o.ad_org_id', $orgId);
        $recentActivities = $recentDocs->get();

        return view('pages.dashboard.procurement', [
            'title' => 'Procurement Dashboard',
            'kpis' => [
                'currentMonthSpend' => $currentMonthSpend,
                'spendChange' => $spendChange,
                'totalPRs' => $prStats->sum(),
                'pendingPRs' => ($prStats['DR'] ?? 0) + ($prStats['IP'] ?? 0),
                'totalPOs' => $poStats->sum(),
                'activePOs' => ($poStats['IP'] ?? 0) + ($poStats['CO'] ?? 0),
                'pendingReceipts' => $pendingCount
            ],
            'charts' => [
                'months' => $months,
                'spendTrend' => $spendTrend,
                'supplierNames' => $topSuppliersData->pluck('name'),
                'supplierValues' => $topSuppliersData->pluck('total_spend')
            ],
            'recentActivities' => $recentActivities
        ]);
    }
}
