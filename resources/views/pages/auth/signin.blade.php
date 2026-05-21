@extends('layouts.fullscreen-layout')

@section('content')
    <style>
        :root {
            --navy-950: #070f24; --navy-900: #0d1e40; --navy-800: #162d60;
            --navy-700: #1e3d7f; --navy-600: #264e93; --navy-500: #3263a8;
            --orange-500: #f97316; --orange-400: #fb923c;
        } 
        /* Left panel */
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
        /* Inputs */
        .auth-input {
            transition: border-color .2s, box-shadow .2s, background-color .2s;
            caret-color: var(--orange-500);
        }
        .auth-input:focus {
            outline: none;
            border-color: var(--orange-400) !important;
            background-color: #fff !important;
            box-shadow: 0 0 0 4px rgba(249,115,22,.12);
        }
        /* Button */
        .auth-btn {
            background-color: var(--navy-900);
            transition: background-color .2s, transform .15s, box-shadow .2s;
        }
        .auth-btn:hover:not(:disabled)  { background-color: var(--navy-800); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(13,30,64,.35); }
        .auth-btn:active:not(:disabled) { background-color: var(--navy-950); transform: translateY(0); }
        .auth-btn:disabled { opacity: .7; cursor: not-allowed; }
        /* Spinner */
        @keyframes auth-spin { to { transform: rotate(360deg); } }
        .auth-spinner { animation: auth-spin .75s linear infinite; }
        /* Right panel transition */
        .auth-panel-fade { transition: opacity .25s ease; }
    </style>

    <div class="flex min-h-screen flex-col lg:flex-row">

        {{-- ── MOBILE TOP NAVBAR (hidden on lg+) ──────────────────── --}}
        <div class="auth-left lg:hidden flex items-center gap-3 px-6 py-4 relative overflow-hidden flex-shrink-0">
            <div class="auth-dots"></div>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500 shadow-lg relative z-10">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M13 2L4.09 12.11a1 1 0 0 0 .77 1.64H11l-1 8 8.91-10.11a1 1 0 0 0-.77-1.64H13l1-8Z"
                        fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <span class="text-lg font-bold tracking-wide text-white relative z-10">FactoryHub</span>
        </div>

        {{-- ── LEFT: Branding Panel (desktop only) ────────────────── --}}
        <div class="auth-left relative hidden lg:flex lg:w-1/2 flex-col justify-between overflow-hidden p-12">
            <div class="auth-dots"></div>
            <div class="auth-orb-top"></div>
            <div class="auth-orb-bottom"></div>

            {{-- Logo --}}
            <div class="relative z-10 flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-500 shadow-lg">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M13 2L4.09 12.11a1 1 0 0 0 .77 1.64H11l-1 8 8.91-10.11a1 1 0 0 0-.77-1.64H13l1-8Z"
                            fill="white" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-wide text-white">FactoryHub</span>
            </div>

            {{-- Headline & features --}}
            <div class="relative z-10 flex-1 flex flex-col justify-center py-12">
                <h1 class="mb-4 text-4xl font-bold leading-tight text-white">
                    Manage Your<br>
                    <span class="text-orange-400">Factory</span> Operations<br>
                    More Efficiently
                </h1>
                <p class="mb-10 max-w-sm text-base leading-relaxed text-slate-300">
                    A centralized platform for production monitoring, inventory management,
                    Equipment and real-time reporting.
                </p>
                <ul class="space-y-4">
                    @foreach(['Real-time production monitoring', 'Automated inventory management', 'Integrated reports & analytics', 'Access from anywhere, anytime'] as $feature)
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-orange-500">
                            <svg width="12" height="12" viewBox="0 0 14 14" fill="none">
                                <path d="M11.667 3.5L5.25 9.917 2.333 7" stroke="white" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="text-sm text-slate-200">{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Footer --}}
            <div class="relative z-10">
                <p class="text-xs text-slate-500">© {{ date('Y') }} Adyawinsa Group. All rights reserved.</p>
            </div>
        </div>

        {{-- ── RIGHT: Form Panel ────────────────────────────────────── --}}
        <div class="flex flex-1 w-full items-center justify-center bg-white px-6 py-10 lg:w-1/2 lg:py-12">
            <div id="auth-right-content" class="auth-panel-fade w-full max-w-md">

                {{-- Heading --}}
                <div class="mb-8">
                    <h2 class="mb-2 text-3xl font-bold text-gray-900">Welcome back</h2>
                    <p class="text-sm text-gray-500">Sign in to your FactoryHub account to continue.</p>
                </div>

                <form id="signin-form" method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="space-y-5">

                        {{-- Username --}}
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Username</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <path d="M20 21a8 8 0 1 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"
                                            stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input type="text" id="username" name="username" value="{{ old('username') }}"
                                    placeholder="Enter your username"
                                    class="auth-input h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-800 placeholder:text-gray-400" />
                            </div>
                            @error('username')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Password</label>
                            <div x-data="{ show: false }" class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <rect x="3" y="11" width="18" height="11" rx="2"
                                            stroke="currentColor" stroke-width="1.75"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"
                                            stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <input :type="show ? 'text' : 'password'" name="password"
                                    placeholder="········"
                                    class="auth-input h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-12 text-sm text-gray-800 placeholder:text-gray-400" />
                                <button type="button" @click="show = !show"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg x-show="!show" width="18" height="18" viewBox="0 0 20 20" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"
                                            fill="#98A2B3"/>
                                    </svg>
                                    <svg x-show="show" width="18" height="18" viewBox="0 0 20 20" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z"
                                            fill="#98A2B3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Error message --}}
                        <p id="signin-error" class="hidden rounded-lg bg-red-50 px-4 py-2.5 text-sm text-red-600"></p>

                        {{-- Remember me --}}
                        <label class="flex items-center gap-3 text-sm text-gray-600 select-none">
                            <input type="checkbox" name="remember" value="1"
                                @checked(old('remember'))
                                class="h-4 w-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400" />
                            <span>Remember me</span>
                        </label>

                        {{-- Sign In button --}}
                        <div class="pt-1">
                            <button type="submit" id="signin-btn"
                                class="auth-btn flex h-12 w-full items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold text-white">
                                <span id="signin-btn-default" class="flex items-center gap-2">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 12h14M13 6l6 6-6 6"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Sign In
                                </span>
                                <span id="signin-btn-loading" class="hidden items-center gap-2">
                                    <svg class="auth-spinner" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="3" stroke-dasharray="40 20" stroke-linecap="round"/>
                                    </svg>
                                    Signing in...
                                </span>
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- ── MOBILE FOOTER (hidden on lg+) ──────────────────────── --}}
        <div class="auth-left lg:hidden px-6 py-4 text-center flex-shrink-0">
            <p class="text-xs text-slate-400">© {{ date('Y') }} Adyawinsa Group. All rights reserved.</p>
        </div>

    </div>
@endsection

@push('scripts')
<script>
(function () {
    var loginUrl   = '{{ route("login") }}';
    var partialUrl = '{{ route("auth.roles.partial") }}';

    var form      = document.getElementById('signin-form');
    var btn       = document.getElementById('signin-btn');
    var btnDef    = document.getElementById('signin-btn-default');
    var btnLoad   = document.getElementById('signin-btn-loading');
    var errEl     = document.getElementById('signin-error');
    var container = document.getElementById('auth-right-content');

    function setLoading(on) {
        btn.disabled = on;
        btnDef.classList.toggle('hidden', on);
        btnLoad.classList.toggle('hidden', !on);
        btnLoad.classList.toggle('inline-flex', on);
    }

    function showError(msg) {
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
    }

    function hideError() {
        errEl.classList.add('hidden');
    }

    function runInjectedScripts(el) {
        el.querySelectorAll('script').forEach(function (old) {
            var s = document.createElement('script');
            s.textContent = old.textContent;
            document.body.appendChild(s);
            document.body.removeChild(s);
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideError();
        setLoading(true);

        try {
            // ── Step 1: login ────────────────────────────────────────
            var loginResp = await fetch(loginUrl, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!loginResp.ok) {
                var data = await loginResp.json();
                var msg  = (data.errors && data.errors.username && data.errors.username[0])
                            || data.message
                            || 'Username atau password salah.';
                showError(msg);
                setLoading(false);
                return;
            }

            // ── Step 2: fetch roles partial ───────────────────────────
            var partialResp = await fetch(partialUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!partialResp.ok) {
                showError('Gagal memuat halaman roles.');
                setLoading(false);
                return;
            }

            var html = await partialResp.text();

            // ── Step 3: swap with fade transition ────────────────────
            container.style.opacity = '0';
            await new Promise(function (r) { setTimeout(r, 250); });
            container.innerHTML = html;
            container.style.opacity = '1';
            runInjectedScripts(container);

        } catch (err) {
            showError('Terjadi kesalahan jaringan. Coba lagi.');
            setLoading(false);
        }
    });
})();
</script>
@endpush
