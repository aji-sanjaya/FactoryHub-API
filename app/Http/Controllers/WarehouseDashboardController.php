<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WarehouseDashboardController extends Controller
{
    public function index()
    {
        $clientId = session('idempiere_client');
        $orgId = session('idempiere_org');

        if (!$clientId) {
            return redirect()->route('signin');
        }

        // ── 1. KPIs ──────────────────────────────────────────────

        $startOfMonth = Carbon::now()->startOfMonth()->toDateTimeString();
        $endOfMonth   = Carbon::now()->endOfMonth()->toDateTimeString();

        // Total Active Warehouses
        $whQuery = DB::connection('idempiere')
            ->table('m_warehouse')
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y');
        if ($orgId > 0) $whQuery->where('ad_org_id', $orgId);
        $totalWarehouses = $whQuery->count();

        // Products with Stock On Hand (distinct products in m_storageonhand with qty > 0)
        $stockQuery = DB::connection('idempiere')
            ->table('m_storageonhand as soh')
            ->join('m_locator as l', 'l.m_locator_id', '=', 'soh.m_locator_id')
            ->join('m_warehouse as w', 'w.m_warehouse_id', '=', 'l.m_warehouse_id')
            ->where('w.ad_client_id', $clientId)
            ->where('soh.qtyonhand', '>', 0);
        if ($orgId > 0) $stockQuery->where('w.ad_org_id', $orgId);
        $productsInStock = (clone $stockQuery)->distinct('soh.m_product_id')->count('soh.m_product_id');
        $totalStockQty = (clone $stockQuery)->sum('soh.qtyonhand');

        // Inbound This Month (Material Receipts – issotrx = 'N', completed)
        $inboundQuery = DB::connection('idempiere')
            ->table('m_inout')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'N')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('movementdate', [$startOfMonth, $endOfMonth]);
        if ($orgId > 0) $inboundQuery->where('ad_org_id', $orgId);
        $inboundThisMonth = $inboundQuery->count();

        // Outbound This Month (Customer Shipments – issotrx = 'Y', completed)
        $outboundQuery = DB::connection('idempiere')
            ->table('m_inout')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'Y')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('movementdate', [$startOfMonth, $endOfMonth]);
        if ($orgId > 0) $outboundQuery->where('ad_org_id', $orgId);
        $outboundThisMonth = $outboundQuery->count();

        // ── 2. Charts ────────────────────────────────────────────

        // Monthly Inbound vs Outbound Trend (Last 6 months)
        $months = [];
        $inboundTrend = [];
        $outboundTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');

            $start = $month->copy()->startOfMonth()->toDateTimeString();
            $end   = $month->copy()->endOfMonth()->toDateTimeString();

            // Inbound (Material Receipts)
            $qIn = DB::connection('idempiere')
                ->table('m_inout')
                ->where('ad_client_id', $clientId)
                ->where('issotrx', 'N')
                ->whereIn('docstatus', ['CO', 'CL', 'IP'])
                ->whereBetween('movementdate', [$start, $end]);
            if ($orgId > 0) $qIn->where('ad_org_id', $orgId);
            $inboundTrend[] = (int) $qIn->count();

            // Outbound (Customer Shipments)
            $qOut = DB::connection('idempiere')
                ->table('m_inout')
                ->where('ad_client_id', $clientId)
                ->where('issotrx', 'Y')
                ->whereIn('docstatus', ['CO', 'CL', 'IP'])
                ->whereBetween('movementdate', [$start, $end]);
            if ($orgId > 0) $qOut->where('ad_org_id', $orgId);
            $outboundTrend[] = (int) $qOut->count();
        }

        // Top 5 Products by Stock Quantity
        $topProducts = DB::connection('idempiere')
            ->table('m_storageonhand as soh')
            ->join('m_locator as l', 'l.m_locator_id', '=', 'soh.m_locator_id')
            ->join('m_warehouse as w', 'w.m_warehouse_id', '=', 'l.m_warehouse_id')
            ->join('m_product as p', 'p.m_product_id', '=', 'soh.m_product_id')
            ->where('w.ad_client_id', $clientId)
            ->where('soh.qtyonhand', '>', 0)
            ->select('p.name', DB::raw('SUM(soh.qtyonhand) as total_qty'))
            ->groupBy('p.name')
            ->orderBy('total_qty', 'desc')
            ->limit(5);
        if ($orgId > 0) $topProducts->where('w.ad_org_id', $orgId);
        $topProductsData = $topProducts->get();

        // Stock per Warehouse (for horizontal bar chart)
        $stockPerWarehouse = DB::connection('idempiere')
            ->table('m_storageonhand as soh')
            ->join('m_locator as l', 'l.m_locator_id', '=', 'soh.m_locator_id')
            ->join('m_warehouse as w', 'w.m_warehouse_id', '=', 'l.m_warehouse_id')
            ->where('w.ad_client_id', $clientId)
            ->where('soh.qtyonhand', '>', 0)
            ->select('w.name', DB::raw('COUNT(DISTINCT soh.m_product_id) as product_count'), DB::raw('SUM(soh.qtyonhand) as total_qty'))
            ->groupBy('w.name')
            ->orderBy('total_qty', 'desc');
        if ($orgId > 0) $stockPerWarehouse->where('w.ad_org_id', $orgId);
        $warehouseStockData = $stockPerWarehouse->get();

        // ── 3. Recent Activities ─────────────────────────────────

        $recentDocs = DB::connection('idempiere')
            ->table('m_inout as io')
            ->leftJoin('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'io.c_bpartner_id')
            ->leftJoin('m_warehouse as w', 'w.m_warehouse_id', '=', 'io.m_warehouse_id')
            ->where('io.ad_client_id', $clientId)
            ->select(
                'io.documentno',
                'io.movementdate as date',
                'io.docstatus',
                'io.issotrx',
                'bp.name as partner',
                'w.name as warehouse'
            )
            ->orderBy('io.created', 'desc')
            ->limit(10);
        if ($orgId > 0) $recentDocs->where('io.ad_org_id', $orgId);
        $recentActivities = $recentDocs->get();

        return view('pages.dashboard.warehouse', [
            'title' => 'Warehouse Management Dashboard',
            'kpis' => [
                'totalWarehouses'   => $totalWarehouses,
                'productsInStock'   => $productsInStock,
                'totalStockQty'     => $totalStockQty,
                'inboundThisMonth'  => $inboundThisMonth,
                'outboundThisMonth' => $outboundThisMonth,
            ],
            'charts' => [
                'months'        => $months,
                'inboundTrend'  => $inboundTrend,
                'outboundTrend' => $outboundTrend,
                'productNames'  => $topProductsData->pluck('name'),
                'productQtys'   => $topProductsData->pluck('total_qty'),
                'warehouseNames'  => $warehouseStockData->pluck('name'),
                'warehouseQtys'   => $warehouseStockData->pluck('total_qty'),
                'warehouseProducts' => $warehouseStockData->pluck('product_count'),
            ],
            'recentActivities' => $recentActivities,
        ]);
    }
}
