<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeliveryDashboardController extends Controller
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
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth()->toDateTimeString();
        $endOfLastMonth   = Carbon::now()->subMonth()->endOfMonth()->toDateTimeString();

        // Total Shipments This Month (Customer Shipments = issotrx Y, movementtype C-)
        $shipmentThisMonth = DB::connection('idempiere')
            ->table('m_inout')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'Y')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('movementdate', [$startOfMonth, $endOfMonth]);
        if ($orgId > 0) $shipmentThisMonth->where('ad_org_id', $orgId);
        $shipmentsCurrentMonth = $shipmentThisMonth->count();

        // Total Shipments Last Month
        $shipmentLastMonth = DB::connection('idempiere')
            ->table('m_inout')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'Y')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereBetween('movementdate', [$startOfLastMonth, $endOfLastMonth]);
        if ($orgId > 0) $shipmentLastMonth->where('ad_org_id', $orgId);
        $shipmentsLastMonth = $shipmentLastMonth->count();

        $shipmentChange = $shipmentsLastMonth > 0
            ? (($shipmentsCurrentMonth - $shipmentsLastMonth) / $shipmentsLastMonth) * 100
            : 0;

        // Total All Shipments
        $totalQuery = DB::connection('idempiere')
            ->table('m_inout')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'Y')
            ->select('docstatus', DB::raw('count(*) as total'))
            ->groupBy('docstatus');
        if ($orgId > 0) $totalQuery->where('ad_org_id', $orgId);
        $shipmentStats = $totalQuery->get()->pluck('total', 'docstatus');

        $totalShipments = $shipmentStats->sum();
        $completedShipments = ($shipmentStats['CO'] ?? 0) + ($shipmentStats['CL'] ?? 0);

        // Pending Deliveries (Draft + In Progress)
        $pendingDeliveries = ($shipmentStats['DR'] ?? 0) + ($shipmentStats['IP'] ?? 0);

        // On-time Delivery Rate: shipments where movementdate <= shipdate (when shipdate is set)
        $onTimeQuery = DB::connection('idempiere')
            ->table('m_inout')
            ->where('ad_client_id', $clientId)
            ->where('issotrx', 'Y')
            ->whereIn('docstatus', ['CO', 'CL'])
            ->whereNotNull('shipdate')
            ->whereRaw('shipdate > ?', ['2000-01-01']);
        if ($orgId > 0) $onTimeQuery->where('ad_org_id', $orgId);

        $totalWithShipDate = (clone $onTimeQuery)->count();
        $onTimeCount = (clone $onTimeQuery)->whereRaw('movementdate <= shipdate')->count();
        $onTimeRate = $totalWithShipDate > 0 ? round(($onTimeCount / $totalWithShipDate) * 100, 1) : 100;

        // ── 2. Charts ────────────────────────────────────────────

        // Monthly Shipment Volume Trend (Last 6 months)
        $months = [];
        $shipmentTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');

            $q = DB::connection('idempiere')
                ->table('m_inout')
                ->where('ad_client_id', $clientId)
                ->where('issotrx', 'Y')
                ->whereIn('docstatus', ['CO', 'CL', 'IP'])
                ->whereBetween('movementdate', [
                    $month->startOfMonth()->toDateTimeString(),
                    $month->endOfMonth()->toDateTimeString()
                ]);
            if ($orgId > 0) $q->where('ad_org_id', $orgId);
            $shipmentTrend[] = (int) $q->count();
        }

        // Top 5 Customers by Shipment Count
        $topCustomers = DB::connection('idempiere')
            ->table('m_inout as io')
            ->join('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'io.c_bpartner_id')
            ->where('io.ad_client_id', $clientId)
            ->where('io.issotrx', 'Y')
            ->whereIn('io.docstatus', ['CO', 'CL'])
            ->select('bp.name', DB::raw('COUNT(*) as total_shipments'))
            ->groupBy('bp.name')
            ->orderBy('total_shipments', 'desc')
            ->limit(5);
        if ($orgId > 0) $topCustomers->where('io.ad_org_id', $orgId);
        $topCustomersData = $topCustomers->get();

        // ── 3. Recent Activities ─────────────────────────────────

        $recentDocs = DB::connection('idempiere')
            ->table('m_inout as io')
            ->leftJoin('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'io.c_bpartner_id')
            ->where('io.ad_client_id', $clientId)
            ->where('io.issotrx', 'Y')
            ->select(
                'io.documentno',
                'io.movementdate as date',
                'io.docstatus',
                'bp.name as customer',
                'io.trackingno'
            )
            ->orderBy('io.created', 'desc')
            ->limit(10);
        if ($orgId > 0) $recentDocs->where('io.ad_org_id', $orgId);
        $recentActivities = $recentDocs->get();

        return view('pages.dashboard.delivery', [
            'title' => 'Delivery Achievement Dashboard',
            'kpis' => [
                'shipmentsCurrentMonth' => $shipmentsCurrentMonth,
                'shipmentChange'        => $shipmentChange,
                'totalShipments'        => $totalShipments,
                'completedShipments'    => $completedShipments,
                'pendingDeliveries'     => $pendingDeliveries,
                'onTimeRate'            => $onTimeRate,
            ],
            'charts' => [
                'months'         => $months,
                'shipmentTrend'  => $shipmentTrend,
                'customerNames'  => $topCustomersData->pluck('name'),
                'customerValues' => $topCustomersData->pluck('total_shipments'),
            ],
            'recentActivities' => $recentActivities,
        ]);
    }
}
