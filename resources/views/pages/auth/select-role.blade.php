@extends('layouts.fullscreen-layout')

@section('content')
    <style>
        :root {
            --navy-950: #070f24; --navy-900: #0d1e40; --navy-800: #162d60;
            --navy-700: #1e3d7f; --navy-600: #264e93; --navy-500: #3263a8;
            --orange-500: #f97316; --orange-400: #fb923c;
        }
        .auth-left {
            background: linear-gradient(145deg, #070f24 0%, #0d1e40 55%, #162d60 100%);
        }
        .auth-dots {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.035) 1px, transparent 1px);
            background-size: 26px 26px; pointer-events: none;
        }
        .auth-orb-top {
            position: absolute; width: 500px; height: 500px; border-radius: 50%;
            background: radial-gradient(circle, rgba(249,115,22,.18) 0%, transparent 65%);
            top: -180px; right: -150px; pointer-events: none;
        }
        .auth-orb-bottom {
            position: absolute; width: 380px; height: 380px; border-radius: 50%;
            background: radial-gradient(circle, rgba(38,78,147,.5) 0%, transparent 65%);
            bottom: -100px; left: -130px; pointer-events: none;
        }
        @keyframes auth-spin { to { transform: rotate(360deg); } }
        .auth-spinner { animation: auth-spin .75s linear infinite; }
        .auth-panel-fade { transition: opacity .25s ease; }
        .auth-btn {
            background-color: var(--navy-900);
            transition: background-color .2s, transform .15s, box-shadow .2s;
        }
        .auth-btn:hover:not(:disabled) { background-color: var(--navy-800); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(13,30,64,.35); }
        .auth-btn:active:not(:disabled) { background-color: var(--navy-950); transform: translateY(0); }
        .auth-btn:disabled { opacity: .7; cursor: not-allowed; }
    </style>

    <div class="flex min-h-screen flex-col lg:flex-row">
        <div class="auth-left lg:hidden flex items-center gap-3 px-6 py-4 relative overflow-hidden flex-shrink-0">
            <div class="auth-dots"></div>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500 shadow-lg relative z-10">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M13 2L4.09 12.11a1 1 0 0 0 .77 1.64H11l-1 8 8.91-10.11a1 1 0 0 0-.77-1.64H13l1-8Z"
                        fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <span class="text-lg font-bold tracking-wide text-white relative z-10">FactoryHub</span>
        </div>

        <div class="auth-left relative hidden lg:flex lg:w-1/2 flex-col justify-between overflow-hidden p-12">
            <div class="auth-dots"></div>
            <div class="auth-orb-top"></div>
            <div class="auth-orb-bottom"></div>

            <div class="relative z-10 flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-500 shadow-lg">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M13 2L4.09 12.11a1 1 0 0 0 .77 1.64H11l-1 8 8.91-10.11a1 1 0 0 0-.77-1.64H13l1-8Z"
                            fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-wide text-white">FactoryHub</span>
            </div>

            <div class="relative z-10 flex-1 flex flex-col justify-center py-12">
                <h1 class="mb-4 text-4xl font-bold leading-tight text-white">
                    Manage Your<br>
                    <span class="text-orange-400">Factory</span> Operations<br>
                    More Efficiently
                </h1>
                <p class="mb-10 max-w-sm text-base leading-relaxed text-slate-300">
                    A centralized platform for production monitoring, inventory management,
                    equipment and real-time reporting.
                </p>
                <ul class="space-y-4">
                    @foreach (['Real-time production monitoring', 'Automated inventory management', 'Integrated reports & analytics', 'Access from anywhere, anytime'] as $feature)
                        <li class="flex items-center gap-3">
                            <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-orange-500">
                                <svg width="12" height="12" viewBox="0 0 14 14" fill="none">
                                    <path d="M11.667 3.5L5.25 9.917 2.333 7" stroke="white" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="text-sm text-slate-200">{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative z-10">
                <p class="text-xs text-slate-500">© {{ date('Y') }} Adyawinsa Group. All rights reserved.</p>
            </div>
        </div>

        <div class="flex flex-1 w-full items-center justify-center bg-white px-6 py-10 lg:w-1/2 lg:py-12">
            <div id="auth-right-content" class="auth-panel-fade w-full max-w-md">
                @include('pages.auth.select-role-partial')
            </div>
        </div>

        <div class="auth-left lg:hidden px-6 py-4 text-center flex-shrink-0">
            <p class="text-xs text-slate-400">© {{ date('Y') }} Adyawinsa Group. All rights reserved.</p>
        </div>
    </div>
@endsection