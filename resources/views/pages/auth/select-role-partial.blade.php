{{--
    Partial view — rendered as plain HTML fragment (no layout).
    Injected into #auth-right-content via fetch() after a successful AJAX login.
--}}

{{-- Select2 CSS (loaded here only when partial is first injected) --}}
<link id="select2-css" rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />

<style>
    /* Align Select2 widget with the auth design */
    .sr-select2 .select2-selection--single {
        height: 48px !important;
        display: flex;
        align-items: center;
        border-radius: 0.75rem !important;
        border: 1px solid #e5e7eb !important;
        background-color: #f9fafb !important;
        transition: border-color .2s, box-shadow .2s, background-color .2s;
    }
    .sr-select2.select2-container--focus .select2-selection--single,
    .sr-select2.select2-container--open  .select2-selection--single {
        border-color: #fb923c !important;
        box-shadow: 0 0 0 4px rgba(249,115,22,.12) !important;
        background-color: #fff !important;
    }
    .sr-select2 .select2-selection__rendered {
        line-height: 46px !important;
        padding-left: 1rem !important;
        padding-right: 2rem !important;
        color: #111827 !important;
        font-size: 0.875rem !important;
    }
    .sr-select2 .select2-selection__placeholder { color: #9ca3af !important; }
    .sr-select2 .select2-selection__arrow       { height: 48px !important; }
    .sr-select2 .select2-selection__clear {
        position: absolute; right: 2rem; top: 50%;
        transform: translateY(-50%); color: #9ca3af;
    }
    /* Loading state — shimmer on selection */
    .sr-select2.sr-loading .select2-selection--single {
        background: linear-gradient(90deg, #f3f4f6 25%, #e9eaec 50%, #f3f4f6 75%) !important;
        background-size: 200% 100% !important;
        animation: sr-shimmer .9s infinite linear;
        border-color: #d1d5db !important;
        pointer-events: none;
    }
    @keyframes sr-shimmer { to { background-position: -200% 0; } }
</style>

<div class="mb-8">
    <h2 class="mb-2 text-3xl font-bold text-gray-900">Select Role</h2>
    <p class="text-sm text-gray-500">Please choose your role and context to proceed.</p>
</div>

@if(isset($debug_error) || (isset($tenants) && count($tenants) === 0))
<div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
    <p class="font-semibold">No tenants found.</p>
    @if(isset($debug_error))<p class="mt-1 text-xs text-red-500">{{ $debug_error }}</p>@endif
</div>
@endif

<form id="roles-form" method="POST" action="{{ route('auth.roles.store') }}">
    @csrf
    <div class="space-y-5">

        {{-- Tenant --}}
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Tenant</label>
            <select name="client_id" id="sr_client_id">
                <option value="">Select Tenant</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}"
                        {{ $tenant->id == 1000000 ? 'selected' : '' }}>
                        {{ $tenant->text }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Role --}}
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Role</label>
            <div class="relative">
                <select name="role_id" id="sr_role_id">
                    <option value="">Select Role</option>
                </select>
                {{-- Loading overlay, shown inside the field while fetching roles --}}
                <div id="sr-role-loading" class="pointer-events-none absolute inset-y-0 right-10 z-10 hidden items-center">
                    <svg class="auth-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="#9ca3af" stroke-width="3" stroke-dasharray="40 20" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Organization --}}
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Organization</label>
            <select name="org_id" id="sr_org_id">
                <option value="0">*</option>
            </select>
        </div>

        {{-- Warehouse --}}
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Warehouse</label>
            <select name="warehouse_id" id="sr_warehouse_id">
                <option value="">Select Warehouse</option>
            </select>
        </div>

        {{-- Actions --}}
        <div class="grid grid-cols-2 gap-4 pt-2">
            <button type="button" id="sr-back-btn"
                class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-[#0d1e40] text-sm font-semibold text-[#0d1e40] transition-colors hover:bg-[#0d1e40] hover:text-white disabled:opacity-60 disabled:cursor-not-allowed">
                <span id="sr-back-default" class="flex items-center gap-2">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                        <path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back
                </span>
                <span id="sr-back-loading" class="hidden items-center gap-2">
                    <svg class="auth-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40 20" stroke-linecap="round"/>
                    </svg>
                    Going back...
                </span>
            </button>
            <button type="submit" id="sr-continue-btn"
                class="auth-btn flex h-12 w-full items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white disabled:opacity-60 disabled:cursor-not-allowed">
                <span id="sr-continue-default" class="flex items-center gap-2">
                    Continue
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                        <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span id="sr-continue-loading" class="hidden items-center gap-2">
                    <svg class="auth-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="3" stroke-dasharray="40 20" stroke-linecap="round"/>
                    </svg>
                    Please wait...
                </span>
            </button>
        </div>

    </div>
</form>

{{-- Hidden logout form used by Back button --}}
<form id="sr-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

<script>
(function () {
    var ROLES_URL      = '{{ route("auth.api.roles") }}';
    var ORGS_URL       = '{{ route("auth.api.orgs") }}';
    var WAREHOUSES_URL = '{{ route("auth.api.warehouses") }}';

    function loadSelect2AndInit() {
        if (typeof $ === 'undefined') {
            var jq = document.createElement('script');
            jq.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
            jq.onload = function () { loadSelect2Plugin(); };
            document.head.appendChild(jq);
        } else if (!$.fn.select2) {
            loadSelect2Plugin();
        } else {
            initSelect2();
        }
    }

    function loadSelect2Plugin() {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        s.onload = initSelect2;
        document.head.appendChild(s);
    }

    function processResults(data) { return { results: data }; }

    function showRoleLoading() {
        var el = document.getElementById('sr-role-loading');
        el.classList.remove('hidden');
        el.classList.add('flex');
    }
    function hideRoleLoading() {
        var el = document.getElementById('sr-role-loading');
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    function initSelect2() {
        $('#sr_client_id').select2({
            containerCssClass: 'sr-select2',
            placeholder: 'Select Tenant',
            allowClear: true,
            width: '100%'
        });
        $('#sr_role_id').select2({
            containerCssClass: 'sr-select2',
            placeholder: 'Select Role',
            allowClear: true,
            width: '100%',
            ajax: {
                url: ROLES_URL, dataType: 'json', delay: 250,
                data: function (p) { return { q: p.term, client_id: $('#sr_client_id').val() }; },
                transport: function (params, success, failure) {
                    showRoleLoading();
                    var req = $.ajax(params);
                    req.then(success).fail(failure).always(hideRoleLoading);
                    return req;
                },
                processResults: processResults, cache: true
            }
        });
        $('#sr_org_id').select2({
            containerCssClass: 'sr-select2',
            placeholder: 'Select Organization',
            allowClear: true,
            width: '100%',
            ajax: {
                url: ORGS_URL, dataType: 'json', delay: 250,
                data: function (p) { return { q: p.term, client_id: $('#sr_client_id').val(), role_id: $('#sr_role_id').val() }; },
                processResults: processResults, cache: true
            }
        });
        $('#sr_warehouse_id').select2({
            containerCssClass: 'sr-select2',
            placeholder: 'Select Warehouse',
            allowClear: true,
            width: '100%',
            ajax: {
                url: WAREHOUSES_URL, dataType: 'json', delay: 250,
                data: function (p) { return { q: p.term, role_id: $('#sr_role_id').val(), org_id: $('#sr_org_id').val() }; },
                processResults: processResults, cache: true
            }
        });

        $('#sr_client_id').on('change', function () {
            $('#sr_role_id').val(null).trigger('change');
            $('#sr_org_id').val(null).trigger('change');
            $('#sr_warehouse_id').val(null).trigger('change');
            var clientId = $(this).val();
            if (clientId) { setDefaultRole(clientId); }
        });

        $('#sr_role_id').on('change', function () {
            var orgSel = $('#sr_org_id');
            orgSel.empty().append(new Option('*', '0', true, true)).trigger('change');
            $('#sr_warehouse_id').val(null).trigger('change');
        });

        $('#sr_org_id').on('change', function () {
            $('#sr_warehouse_id').val(null).trigger('change');
        });

        function setDefaultRole(clientId) {
            showRoleLoading();
            $.ajax({
                url: ROLES_URL, type: 'GET', data: { client_id: clientId },
                success: function (data) {
                    if (data && data.length > 0) {
                        $('#sr_role_id').append(new Option(data[0].text, data[0].id, true, true)).trigger('change');
                    }
                },
                complete: hideRoleLoading
            });
        }

        // Pre-select default tenant if present
        var initial = $('#sr_client_id').val();
        if (initial) { setDefaultRole(initial); }
    }

    // Back = logout and go back to signin
    document.getElementById('sr-back-btn').addEventListener('click', function () {
        var btn = this;
        btn.disabled = true;
        document.getElementById('sr-back-default').classList.add('hidden');
        document.getElementById('sr-back-loading').classList.remove('hidden');
        document.getElementById('sr-back-loading').classList.add('flex');
        document.getElementById('sr-logout-form').submit();
    });

    // Continue = show loading on submit
    document.getElementById('roles-form').addEventListener('submit', function () {
        var btn = document.getElementById('sr-continue-btn');
        btn.disabled = true;
        document.getElementById('sr-continue-default').classList.add('hidden');
        document.getElementById('sr-continue-loading').classList.remove('hidden');
        document.getElementById('sr-continue-loading').classList.add('flex');
    });

    loadSelect2AndInit();
})();
</script>
