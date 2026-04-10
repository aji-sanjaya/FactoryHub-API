@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Dashboard Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales Dashboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Overview of your sales performance and activities.
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
            <!-- Monthly Revenue -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    @if ($kpis['revenueChange'] != 0)
                        <div
                            class="flex items-center gap-1 {{ $kpis['revenueChange'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                            <span
                                class="text-xs font-medium">{{ number_format(abs($kpis['revenueChange']), 1) }}%</span>
                            <svg class="h-3 w-3 {{ $kpis['revenueChange'] < 0 ? 'rotate-180' : 'rotate-0' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Revenue (SO)</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">IDR
                        {{ number_format($kpis['currentMonthRevenue'] / 1000000, 1) }}M
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Current Month Gross</p>
                </div>
            </div>

            <!-- Total SOs -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                        {{ $kpis['totalSOs'] }} Total
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sales Orders</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['totalSOs'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">All time records</p>
                </div>
            </div>

            <!-- Active SOs -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                        {{ $kpis['activeSOs'] }} Active
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Sales Orders</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['activeSOs'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">In progress or completed</p>
                </div>
            </div>

            <!-- Pending Shipments -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Shipments</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['pendingShipments'] }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Awaiting delivery</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Revenue Trend -->
            <div id="trendChartContainer"
                class="col-span-12 lg:col-span-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">

                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Revenue Trend</h3>
                        <p class="text-sm font-medium text-gray-500">Last 6 months sales value</p>
                    </div>
                </div>

                <!-- Loader -->
                <div id="chartLoader" class="flex h-[320px] items-center justify-center">
                    <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-blue-500"></div>
                </div>

                <!-- Chart -->
                <div id="revenueTrendChart" class="hidden w-full"></div>

            </div>

            <!-- Top Customers -->
            <div
                class="col-span-12 lg:col-span-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Top Customers</h3>
                        <p class="text-sm font-medium text-gray-500">By total sales value</p>
                    </div>
                </div>
                <div id="customerChart" class="w-full"></div>
            </div>
        </div>

        <!-- Recent Activities Table -->
        <div
            class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 p-6 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Sales Orders</h3>
                <a href="{{ route('sales-order.index') }}"
                    class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="bg-gray-50/50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                            <th class="px-6 py-4">Order No</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4 text-right">Amount</th>
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
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $doc->customer }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-sm text-gray-900 dark:text-white">
                                    {{ number_format($doc->grandtotal, 0, ',', '.') }}
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
                const chart = document.getElementById("revenueTrendChart");

                setTimeout(function() {

                    loader.classList.add("hidden");
                    chart.classList.remove("hidden");

                    const trendOptions = {
                        series: [{
                            name: 'Monthly Revenue',
                            data: @json($charts['revenueTrend'])
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
                                    return "IDR " + (val / 1000000).toFixed(1) + "M";
                                }
                            }
                        },
                        colors: ['#4f46e5'],
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
                                    return 'IDR ' + new Intl.NumberFormat('id-ID').format(val)
                                }
                            }
                        }
                    };

                    const trendChart = new ApexCharts(document.querySelector("#revenueTrendChart"),
                        trendOptions);
                    trendChart.render();

                    // Customer Donut Chart
                    const customerNames = @json($charts['customerNames']);
                    const customerValues = @json($charts['customerValues']);

                    if (customerValues.length > 0 && customerValues.some(v => v > 0)) {
                        const customerOptions = {
                            series: customerValues.map(v => Number(v)),
                            labels: customerNames,
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
                                                    return "IDR " + (Number(val) /
                                                        1000000).toFixed(1) + "M";
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
                                        return 'IDR ' + new Intl.NumberFormat('id-ID')
                                            .format(val)
                                    }
                                }
                            }
                        };

                        const customerChartObj = new ApexCharts(document.querySelector(
                            "#customerChart"), customerOptions);
                        customerChartObj.render();
                    } else {
                        document.getElementById('customerChart').innerHTML =
                            '<div class="flex items-center justify-center h-[300px] text-gray-400 text-sm">No data available</div>';
                    }

                }, 2000);

            });
        </script>
    @endpush
@endsection
