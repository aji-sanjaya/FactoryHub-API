@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Dashboard Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Warehouse Management Dashboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Overview of your inventory, stock levels, and
                    warehouse activities.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center rounded-md bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-700/10 dark:bg-brand-400/10 dark:text-brand-400 dark:ring-brand-400/20">
                    Real-time Data
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-500">Last updated: {{ now()->format('H:i') }}</span>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Active Warehouses -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        Active
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Warehouses</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $kpis['totalWarehouses'] }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Registered locations</p>
                </div>
            </div>

            <!-- Products in Stock -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                        {{ number_format($kpis['totalStockQty'], 0, ',', '.') }} qty
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Products in Stock</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $kpis['productsInStock'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">Distinct products with stock > 0</p>
                </div>
            </div>

            <!-- Inbound This Month -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        Receipts
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Inbound This Month</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $kpis['inboundThisMonth'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">Material receipts</p>
                </div>
            </div>

            <!-- Outbound This Month -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                        Shipments
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Outbound This Month</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $kpis['outboundThisMonth'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">Customer shipments</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Inbound vs Outbound Trend -->
            <div id="trendChartContainer"
                class="col-span-12 lg:col-span-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">

                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Inbound vs Outbound Trend</h3>
                        <p class="text-sm font-medium text-gray-500">Last 6 months movement count</p>
                    </div>
                </div>

                <!-- Loader -->
                <div id="chartLoader" class="flex h-[320px] items-center justify-center">
                    <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-blue-500"></div>
                </div>

                <!-- Chart -->
                <div id="movementTrendChart" class="hidden w-full"></div>

            </div>

            <!-- Top Products by Stock -->
            <div
                class="col-span-12 lg:col-span-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Top Products</h3>
                        <p class="text-sm font-medium text-gray-500">By stock on hand quantity</p>
                    </div>
                </div>
                <div id="productChart" class="w-full"></div>
            </div>
        </div>

        <!-- Recent Activities Table -->
        <div
            class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 p-6 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Warehouse Movements</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="bg-gray-50/50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                            <th class="px-6 py-4">Document No</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Partner</th>
                            <th class="px-6 py-4">Warehouse</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($recentActivities as $doc)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $doc->documentno }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($doc->issotrx === 'Y')
                                        <span
                                            class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                                            Outbound
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            Inbound
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $doc->partner ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $doc->warehouse ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @php
                                        $statusClass = match ($doc->docstatus) {
                                            'CO'
                                                => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            'IP'
                                                => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'DR'
                                                => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                            default
                                                => 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                        };
                                        $statusLabel = match ($doc->docstatus) {
                                            'CO' => 'Completed',
                                            'IP' => 'In Progress',
                                            'DR' => 'Draft',
                                            'CL' => 'Closed',
                                            default => $doc->docstatus,
                                        };
                                    @endphp
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ date('d M Y', strtotime($doc->date)) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const loader = document.getElementById("chartLoader");
                const chart = document.getElementById("movementTrendChart");

                setTimeout(function() {

                    loader.classList.add("hidden");
                    chart.classList.remove("hidden");

                    // Inbound vs Outbound Area Chart
                    const trendOptions = {
                        series: [{
                            name: 'Inbound (Receipts)',
                            data: @json($charts['inboundTrend'])
                        }, {
                            name: 'Outbound (Shipments)',
                            data: @json($charts['outboundTrend'])
                        }],
                        chart: {
                            width: '100%',
                            height: 320,
                            type: 'area',
                            toolbar: {
                                show: false
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        xaxis: {
                            categories: @json($charts['months']),
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            }
                        },
                        yaxis: {
                            labels: {
                                formatter: function(val) {
                                    return Math.round(val);
                                }
                            }
                        },
                        colors: ['#10b981', '#f59e0b'],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.45,
                                opacityTo: 0.05,
                                stops: [20, 100, 100, 100]
                            }
                        },
                        grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return val + ' documents'
                                }
                            }
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'right'
                        }
                    };

                    const trendChart = new ApexCharts(document.querySelector("#movementTrendChart"),
                        trendOptions);
                    trendChart.render();

                    // Top Products Donut Chart
                    const productNames = @json($charts['productNames']);
                    const productQtys = @json($charts['productQtys']);

                    if (productQtys.length > 0 && productQtys.some(v => v > 0)) {
                        const productOptions = {
                            series: productQtys.map(v => Number(v)),
                            labels: productNames,
                            chart: {
                                type: 'donut',
                                height: 360
                            },
                            colors: ['#4f46e5', '#8b5cf6', '#10b981', '#f59e0b', '#f43f5e'],
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '70%',
                                        labels: {
                                            show: true,
                                            name: {
                                                fontSize: '12px'
                                            },
                                            value: {
                                                fontSize: '16px',
                                                fontWeight: 600,
                                                formatter: function(val) {
                                                    return Number(val).toLocaleString('id-ID') +
                                                        " qty";
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            dataLabels: {
                                enabled: false
                            },
                            legend: {
                                position: 'bottom',
                                horizontalAlign: 'center',
                                fontSize: '12px'
                            },
                            tooltip: {
                                y: {
                                    formatter: function(val) {
                                        return Number(val).toLocaleString('id-ID') + ' qty'
                                    }
                                }
                            }
                        };

                        const productChartObj = new ApexCharts(document.querySelector(
                            "#productChart"), productOptions);
                        productChartObj.render();
                    } else {
                        document.getElementById('productChart').innerHTML =
                            '<div class="flex items-center justify-center h-[300px] text-gray-400 text-sm">No data available</div>';
                    }

                }, 2000);

            });
        </script>
    @endpush
@endsection
