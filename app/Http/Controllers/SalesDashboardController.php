<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesDashboardController extends Controller
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

        // SO Total Revenue (Current Month)
        $soRevenue = DB::connection('idempiere')
            ->table('c_order')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'Y')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('dateordered', [$startOfMonth, $endOfMonth]);

        if ($orgId > 0)
            $soRevenue->where('ad_org_id', $orgId);
        $currentMonthRevenue = $soRevenue->sum('grandtotal');

        // SO Total Revenue (Last Month)
        $soRevenueLast = DB::connection('idempiere')
            ->table('c_order')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'Y')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('dateordered', [$startOfLastMonth, $endOfLastMonth]);

        if ($orgId > 0)
            $soRevenueLast->where('ad_org_id', $orgId);
        $lastMonthRevenue = $soRevenueLast->sum('grandtotal');

        // Percentage Change
        $revenueChange = $lastMonthRevenue > 0 ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        // SO Status Counts
        $soCounts = DB::connection('idempiere')
            ->table('c_order')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'Y')
            ->select('docstatus', DB::raw('count(*) as total'))
            ->groupBy('docstatus');

        if ($orgId > 0)
            $soCounts->where('ad_org_id', $orgId);
        $soStats = $soCounts->get()->pluck('total', 'docstatus');

        // Shipment Pending: Count SOs where at least one line is partially delivered
        $pendingQuery = DB::connection('idempiere')
            ->table('c_orderline as ol')
            ->join('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
            ->where('o.ad_client_id', $clientId)
            ->where('o.issotrx', 'Y')
            ->where('o.docstatus', 'CO')
            ->whereRaw('ol.qtyordered > ol.qtydelivered');

        if ($orgId > 0)
            $pendingQuery->where('o.ad_org_id', $orgId);
        $pendingCount = $pendingQuery->distinct('o.c_order_id')->count();

        // 2. Charts Data

        // Monthly Revenue Trend (Last 6 Months)
        $months = [];
        $revenueTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');

            $q = DB::connection('idempiere')
                ->table('c_order')
                ->where('ad_client_id', $clientId)
                ->where('issotrx', 'Y')
                ->whereIn('docstatus', ['CO', 'CL', 'IP'])
                ->whereBetween('dateordered', [$month->startOfMonth()->toDateTimeString(), $month->endOfMonth()->toDateTimeString()]);
            if ($orgId > 0)
                $q->where('ad_org_id', $orgId);
            $revenueTrend[] = (float) $q->sum('grandtotal');
        }

        // Top 5 Customers
        $topCustomers = DB::connection('idempiere')
            ->table('c_order as o')
            ->join('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'o.c_bpartner_id')
            ->where('o.ad_client_id', $clientId)
            ->where('o.issotrx', 'Y')
            ->whereIn('o.docstatus', ['CO', 'CL'])
            ->select('bp.name', DB::raw('SUM(o.grandtotal) as total_revenue'))
            ->groupBy('bp.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5);

        if ($orgId > 0)
            $topCustomers->where('o.ad_org_id', $orgId);
        $topCustomersData = $topCustomers->get();

        // 3. Recent Activities
        $recentDocs = DB::connection('idempiere')
            ->table('c_order as o')
            ->leftJoin('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'o.c_bpartner_id')
            ->where('o.ad_client_id', $clientId)
            ->where('o.issotrx', 'Y')
            ->select('o.documentno', 'o.dateordered as date', 'o.grandtotal', 'o.docstatus', 'bp.name as customer')
            ->orderBy('o.created', 'desc')
            ->limit(5);

        if ($orgId > 0)
            $recentDocs->where('o.ad_org_id', $orgId);
        $recentActivities = $recentDocs->get();

        return view('pages.dashboard.sales', [
            'title' => 'Sales Dashboard',
            'kpis' => [
                'currentMonthRevenue' => $currentMonthRevenue,
                'revenueChange' => $revenueChange,
                'totalSOs' => $soStats->sum(),
                'activeSOs' => ($soStats['IP'] ?? 0) + ($soStats['CO'] ?? 0),
                'pendingShipments' => $pendingCount
            ],
            'charts' => [
                'months' => array_reverse($months),
                'revenueTrend' => array_reverse($revenueTrend),
                'customerNames' => $topCustomersData->pluck('name'),
                'customerValues' => $topCustomersData->pluck('total_revenue')
            ],
            'recentActivities' => $recentActivities
        ]);
    }
}
