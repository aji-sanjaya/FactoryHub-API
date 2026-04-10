@extends('layouts.app')

@section('content')


    <div class="space-y-6">
        <!-- Dashboard Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Petty Cash Dashboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Overview of petty cash requests, closings, and balances.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('petty-cash-dashboard') }}" class="m-0">
                    <input type="month" name="month" id="monthPicker" value="{{ $selectedMonth }}"
                        onchange="this.form.submit()"
                        class="p-2 px-5 rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                </form>
                <span class="text-xs text-gray-400 dark:text-gray-500">Last updated: {{ now()->format('H:i') }}</span>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Requested -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Requested</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">IDR
                        {{ number_format($kpis['totalRequest'] / 1000000, 1) }}M
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Sum of active requests</p>
                </div>
            </div>

            <!-- Total Closed -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Closed</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">IDR
                        {{ number_format($kpis['totalClosing'] / 1000000, 1) }}M
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Sum of completed closings</p>
                </div>
            </div>

            <!-- Current Balance -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
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
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Floating Balance</h3>
                    <p
                        class="text-2xl font-bold {{ $kpis['currentBalance'] > 0 ? 'text-red-500' : 'text-green-500' }} mt-1">
                        IDR
                        {{ number_format($kpis['currentBalance'] / 1000000, 1) }}M
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Requested - Closed</p>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Requests</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $kpis['pendingRequests'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">Awaiting closing</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div id="trendChartContainer" class="grid grid-cols-1 gap-6">
            <!-- Spend Trend -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Transaction Trend</h3>
                        <p class="text-sm text-gray-500 font-medium">Last 6 months Requests vs Closings</p>
                    </div>
                </div>
                <!-- Loader -->
                <div id="chartLoader" class="flex items-center justify-center h-[320px]">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
                </div>
                <div id="trendChart" class="w-full"></div>
            </div>
        </div>

        <!-- Recent Activities Table -->
        <div
            class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Transactions</h3>
                <a href="{{ route('petty-cash-dashboard.export', ['month' => $selectedMonth]) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export .xls
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="bg-gray-50/50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                            <th class="px-6 py-4">Transaction Type</th>
                            <th class="px-6 py-4">Document No</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($recentActivities as $doc)
                            <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $doc->type == 'Request' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $doc->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $doc->documentno }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ date('d M Y', strtotime($doc->date)) }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @php
                                        $statusClass = match ($doc->docstatus) {
                                            'CO' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            'IP' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'DR' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                            'CL' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                            default => 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                        };
                                        $statusLabel = match ($doc->docstatus) {
                                            'CO' => 'Completed',
                                            'IP' => 'In Progress',
                                            'DR' => 'Draft',
                                            'CL' => 'Closed',
                                            default => $doc->docstatus
                                        };
                                    @endphp
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-mono font-bold text-gray-900 dark:text-white">
                                    {{ number_format($doc->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $recentActivities->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const loader = document.getElementById('chartLoader');
                const chartDiv = document.getElementById('trendChart');

                setTimeout(function () {

                    loader.style.display = "none";
                    chartDiv.style.display = "block";

                    const trendOptions = {
                        series: [{
                            name: 'Requests',
                            data: @json($charts['requestTrend'])
                        }, {
                            name: 'Closings',
                            data: @json($charts['closingTrend'])
                        }],
                        chart: {
                            width: '100%',
                            height: 320,
                            type: 'area',
                            toolbar: { show: false },
                            fontFamily: 'inherit',
                            sparkline: { enabled: false }
                        },
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 3 },
                        xaxis: {
                            categories: @json($charts['months']),
                            axisBorder: { show: false },
                            axisTicks: { show: false }
                        },
                        yaxis: {
                            labels: {
                                formatter: function (val) {
                                    return "IDR " + (val / 1000000).toFixed(1) + "M";
                                }
                            }
                        },
                        colors: ['#3b82f6', '#10b981'],
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
                            strokeDashArray: 4,
                            padding: {
                                right: 10,
                                left: 10
                            }
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'center'
                        }
                    };

                    const trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
                    trendChart.render();

                }, 2000); // delay 3 detik
            });
        </script>
    @endpush
@endsection