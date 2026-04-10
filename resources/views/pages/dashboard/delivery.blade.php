@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Dashboard Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Delivery Achievement Dashboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Overview of your delivery performance and shipment
                    activities.
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
            <!-- Shipments This Month -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                    </div>
                    @if ($kpis['shipmentChange'] != 0)
                        <div
                            class="flex items-center gap-1 {{ $kpis['shipmentChange'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                            <span
                                class="text-xs font-medium">{{ number_format(abs($kpis['shipmentChange']), 1) }}%</span>
                            <svg class="h-3 w-3 {{ $kpis['shipmentChange'] < 0 ? 'rotate-180' : 'rotate-0' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Shipments This Month</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $kpis['shipmentsCurrentMonth'] }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Completed shipments</p>
                </div>
            </div>

            <!-- On-time Delivery Rate -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full {{ $kpis['onTimeRate'] >= 80 ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' }} px-2.5 py-0.5 text-xs font-medium">
                        {{ $kpis['onTimeRate'] }}%
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">On-time Delivery Rate</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $kpis['onTimeRate'] }}%</p>
                    <p class="mt-1 text-xs text-gray-400">Based on ship date target</p>
                </div>
            </div>

            <!-- Completed Shipments -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                        {{ $kpis['totalShipments'] }} Total
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Shipments</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $kpis['completedShipments'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">All time delivered</p>
                </div>
            </div>

            <!-- Pending Deliveries -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Deliveries</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $kpis['pendingDeliveries'] }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Draft or in progress</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Shipment Volume Trend -->
            <div id="trendChartContainer"
                class="col-span-12 lg:col-span-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">

                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Shipment Volume Trend</h3>
                        <p class="text-sm font-medium text-gray-500">Last 6 months delivery count</p>
                    </div>
                </div>

                <!-- Loader -->
                <div id="chartLoader" class="flex h-[320px] items-center justify-center">
                    <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-blue-500"></div>
                </div>

                <!-- Chart -->
                <div id="shipmentTrendChart" class="hidden w-full"></div>

            </div>

            <!-- Top Customers -->
            <div
                class="col-span-12 lg:col-span-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Top Customers</h3>
                        <p class="text-sm font-medium text-gray-500">By total shipment count</p>
                    </div>
                </div>
                <div id="customerChart" class="w-full"></div>
            </div>
        </div>

        <!-- Recent Activities Table -->
        <div
            class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 p-6 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Shipments</h3>
                <a href="{{ route('customer-shipment.index') }}"
                    class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="bg-gray-50/50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                            <th class="px-6 py-4">Shipment No</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Tracking No</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Movement Date</th>
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
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $doc->trackingno ?? '-' }}
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
                const chart = document.getElementById("shipmentTrendChart");

                setTimeout(function() {

                    loader.classList.add("hidden");
                    chart.classList.remove("hidden");

                    const trendOptions = {
                        series: [{
                            name: 'Shipments',
                            data: @json($charts['shipmentTrend'])
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
                                    return val + ' shipments'
                                }
                            }
                        }
                    };

                    const trendChart = new ApexCharts(document.querySelector("#shipmentTrendChart"),
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
                                                    return val + " shipments";
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
                                        return val + ' shipments'
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
