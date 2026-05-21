@extends('layouts.fullscreen-layout')

@php
    use App\Helpers\MenuHelper;
@endphp

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 md:px-6">
        <header class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900/80">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-orange-500 shadow-sm">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M13 2L4.09 12.11a1 1 0 0 0 .77 1.64H11l-1 8 8.91-10.11a1 1 0 0 0-.77-1.64H13l1-8Z"
                            fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">Portal</p>
                    <h1 class="text-lg font-bold tracking-wide text-slate-900 dark:text-white">FactoryHub</h1>
                </div>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                 <a
                    href="{{ route('dashboard') }}"
                    aria-label="Home"
                    class="relative flex h-11 w-11 items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.707 1.293a1 1 0 00-1.414 0l-7 7A1 1 0 003 10h1v6a1 1 0 001 1h3.5a.5.5 0 00.5-.5V13a1 1 0 011-1h0a1 1 0 011 1v3.5a.5.5 0 00.5.5H15a1 1 0 001-1v-6h1a1 1 0 00.707-1.707l-7-7z" />
                    </svg>
                </a>

                <button
                    class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                    @click="$store.theme.toggle()">
                    <svg class="hidden dark:block" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M9.99998 1.5415C10.4142 1.5415 10.75 1.87729 10.75 2.2915V3.5415C10.75 3.95572 10.4142 4.2915 9.99998 4.2915C9.58577 4.2915 9.24998 3.95572 9.24998 3.5415V2.2915C9.24998 1.87729 9.58577 1.5415 9.99998 1.5415ZM10.0009 6.79327C8.22978 6.79327 6.79402 8.22904 6.79402 10.0001C6.79402 11.7712 8.22978 13.207 10.0009 13.207C11.772 13.207 13.2078 11.7712 13.2078 10.0001C13.2078 8.22904 11.772 6.79327 10.0009 6.79327ZM5.29402 10.0001C5.29402 7.40061 7.40135 5.29327 10.0009 5.29327C12.6004 5.29327 14.7078 7.40061 14.7078 10.0001C14.7078 12.5997 12.6004 14.707 10.0009 14.707C7.40135 14.707 5.29402 12.5997 5.29402 10.0001ZM15.9813 5.08035C16.2742 4.78746 16.2742 4.31258 15.9813 4.01969C15.6884 3.7268 15.2135 3.7268 14.9207 4.01969L14.0368 4.90357C13.7439 5.19647 13.7439 5.67134 14.0368 5.96423C14.3297 6.25713 14.8045 6.25713 15.0974 5.96423L15.9813 5.08035ZM18.4577 10.0001C18.4577 10.4143 18.1219 10.7501 17.7077 10.7501H16.4577C16.0435 10.7501 15.7077 10.4143 15.7077 10.0001C15.7077 9.58592 16.0435 9.25013 16.4577 9.25013H17.7077C18.1219 9.25013 18.4577 9.58592 18.4577 10.0001ZM14.9207 15.9806C15.2135 16.2735 15.6884 16.2735 15.9813 15.9806C16.2742 15.6877 16.2742 15.2128 15.9813 14.9199L15.0974 14.036C14.8045 13.7431 14.3297 13.7431 14.0368 14.036C13.7439 14.3289 13.7439 14.8038 14.0368 15.0967L14.9207 15.9806ZM9.99998 15.7088C10.4142 15.7088 10.75 16.0445 10.75 16.4588V17.7088C10.75 18.123 10.4142 18.4588 9.99998 18.4588C9.58577 18.4588 9.24998 18.123 9.24998 17.7088V16.4588C9.24998 16.0445 9.58577 15.7088 9.99998 15.7088ZM5.96356 15.0972C6.25646 14.8043 6.25646 14.3295 5.96356 14.0366C5.67067 13.7437 5.1958 13.7437 4.9029 14.0366L4.01902 14.9204C3.72613 15.2133 3.72613 15.6882 4.01902 15.9811C4.31191 16.274 4.78679 16.274 5.07968 15.9811L5.96356 15.0972ZM4.29224 10.0001C4.29224 10.4143 3.95645 10.7501 3.54224 10.7501H2.29224C1.87802 10.7501 1.54224 10.4143 1.54224 10.0001C1.54224 9.58592 1.87802 9.25013 2.29224 9.25013H3.54224C3.95645 9.25013 4.29224 9.58592 4.29224 10.0001ZM4.9029 5.9637C5.1958 6.25659 5.67067 6.25659 5.96356 5.9637C6.25646 5.6708 6.25646 5.19593 5.96356 4.90303L5.07968 4.01915C4.78679 3.72626 4.31191 3.72626 4.01902 4.01915C3.72613 4.31204 3.72613 4.78692 4.01902 5.07981L4.9029 5.9637Z"
                            fill="currentColor" />
                    </svg>
                    <svg class="dark:hidden" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M17.4547 11.97L18.1799 12.1611C18.265 11.8383 18.1265 11.4982 17.8401 11.3266C17.5538 11.1551 17.1885 11.1934 16.944 11.4207L17.4547 11.97ZM8.0306 2.5459L8.57989 3.05657C8.80718 2.81209 8.84554 2.44682 8.67398 2.16046C8.50243 1.8741 8.16227 1.73559 7.83948 1.82066L8.0306 2.5459ZM12.9154 13.0035C9.64678 13.0035 6.99707 10.3538 6.99707 7.08524H5.49707C5.49707 11.1823 8.81835 14.5035 12.9154 14.5035V13.0035ZM16.944 11.4207C15.8869 12.4035 14.4721 13.0035 12.9154 13.0035V14.5035C14.8657 14.5035 16.6418 13.7499 17.9654 12.5193L16.944 11.4207ZM16.7295 11.7789C15.9437 14.7607 13.2277 16.9586 10.0003 16.9586V18.4586C13.9257 18.4586 17.2249 15.7853 18.1799 12.1611L16.7295 11.7789ZM10.0003 16.9586C6.15734 16.9586 3.04199 13.8433 3.04199 10.0003H1.54199C1.54199 14.6717 5.32892 18.4586 10.0003 18.4586V16.9586ZM3.04199 10.0003C3.04199 6.77289 5.23988 4.05695 8.22173 3.27114L7.83948 1.82066C4.21532 2.77574 1.54199 6.07486 1.54199 10.0003H3.04199ZM6.99707 7.08524C6.99707 5.52854 7.5971 4.11366 8.57989 3.05657L7.48132 2.03522C6.25073 3.35885 5.49707 5.13487 5.49707 7.08524H6.99707Z"
                            fill="currentColor" />
                    </svg>
                </button>

                <x-header.notification-dropdown />
                <x-header.user-dropdown />
            </div>
        </header>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-sky-50 via-white to-blue-100 shadow-sm dark:border-gray-800 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900">
            <div class="grid gap-6 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-center lg:px-10">
                <div class="space-y-4">
                    <div class="inline-flex items-center rounded-full border border-blue-200 bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                        FactoryHub
                    </div>
                    <div class="space-y-2">
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                            Welcome to FactoryHub
                        </h1>
                        <p class="max-w-2xl text-sm text-slate-600 dark:text-gray-300 sm:text-base">
                            Hello, {{ $displayName }}. Choose the main module available for your current role permissions. Only top-level menus with active access are shown on this page.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500 dark:text-gray-400">
                        <span class="rounded-full bg-white/70 px-3 py-1 dark:bg-white/5">{{ $headMenus->count() }} modules available</span> 
                    </div>
                </div>
                <div class="hidden justify-self-end lg:block">
                    <div class="relative h-44 w-64 overflow-hidden rounded-[2rem] bg-white/50 ring-1 ring-white/70 dark:bg-white/5 dark:ring-white/10">
                        <div class="absolute -left-8 top-6 h-28 w-28 rounded-full bg-sky-200/70 blur-2xl dark:bg-sky-500/20"></div>
                        <div class="absolute bottom-4 right-4 left-4 rounded-3xl border border-sky-100 bg-white/90 p-4 shadow-sm dark:border-white/10 dark:bg-slate-900/80">
                            <div class="grid grid-cols-3 gap-3">
                                <div class="h-14 rounded-2xl bg-blue-100 dark:bg-blue-500/20"></div>
                                <div class="h-14 rounded-2xl bg-slate-100 dark:bg-white/10"></div>
                                <div class="h-14 rounded-2xl bg-cyan-100 dark:bg-cyan-500/20"></div>
                            </div>
                            <div class="mt-3 h-3 w-3/4 rounded-full bg-slate-200 dark:bg-white/10"></div>
                            <div class="mt-2 h-3 w-1/2 rounded-full bg-slate-100 dark:bg-white/5"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($headMenus as $menu)
                    @php
                        $cardClasses = $menu['is_clickable']
                            ? 'group hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl dark:hover:border-blue-500/40'
                            : 'opacity-70';
                    @endphp

                    <a
                        @if ($menu['is_clickable']) href="{{ url($menu['path']) }}" @else aria-disabled="true" @endif
                        class="{{ $cardClasses }} relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 dark:border-gray-800 dark:bg-gray-900/80">
                        <div class="absolute right-0 top-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-blue-100/80 blur-2xl transition-transform duration-200 group-hover:scale-110 dark:bg-blue-500/10"></div>

                        <div class="relative flex items-start gap-4">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20">
                                {!! MenuHelper::getIconSvg($menu['icon']) !!}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ $menu['name'] }}
                                    </h2>
                                    @if ($menu['sub_items_count'] > 0)
                                        <span class="inline-flex flex-shrink-0 items-center rounded-full bg-orange-500 px-2 py-1 text-xs font-semibold text-white">
                                            {{ $menu['sub_items_count'] }}
                                        </span>
                                    @endif
                                </div>
                                @if (filled($menu['description']))
                                    <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-gray-400">
                                        {{ $menu['description'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="relative mt-5 flex items-center justify-between text-sm">
                            <span class="font-medium text-blue-600 dark:text-blue-300">
                                {{ $menu['is_clickable'] ? 'Open Module' : '' }}
                            </span>
                            <span class="text-slate-400 transition-transform duration-200 group-hover:translate-x-1 dark:text-gray-500">
                                @if ($menu['is_clickable'])
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M3.25 10a.75.75 0 01.75-.75h10.19l-3.22-3.22a.75.75 0 111.06-1.06l4.5 4.5a.75.75 0 010 1.06l-4.5 4.5a.75.75 0 01-1.06-1.06l3.22-3.22H4A.75.75 0 013.25 10z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900/80">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">No menu available yet</h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">
                            The active role does not have any top-level menu assigned in DPK_Web_Portal_Menus.
                        </p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
