<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IdempiereService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    private const REMEMBER_COOKIE = 'factoryhub_remember';
    private const REMEMBER_COOKIE_MINUTES = 43200;

    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    public function showLoginForm(Request $request)
    {
        if (Session::has('api_token')) {
            return redirect()->route(Session::has('idempiere_role') ? 'dashboard' : 'auth.roles');
        }

        $rememberedCredentials = $this->getRememberedCredentials($request);

        if ($rememberedCredentials) {
            $result = $this->idempiereService->login($rememberedCredentials['username'], $rememberedCredentials['password']);

            if ($result && isset($result['token'])) {
                $this->storeLoginSession($result, $rememberedCredentials['username'], $rememberedCredentials['password']);

                return redirect()->route('auth.roles');
            }
        }

        return view('pages.auth.signin', ['title' => 'Sign In']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        // Map input to userName for iDempiere
        $username = trim($request->input('username'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        $result = $this->idempiereService->login($username, $password);

        if ($result && isset($result['token'])) {
            $this->storeLoginSession($result, $username, $password);

            $response = $request->expectsJson()
                ? response()->json(['success' => true])
                : redirect()->route('auth.roles');

            return $remember
                ? $response->withCookie($this->makeRememberCookie($username, $password))
                : $response->withCookie(Cookie::forget(self::REMEMBER_COOKIE));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah.',
            ], 422);
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('username'));
    }

    public function showRoleSelectionPartial(Request $request)
    {
        if (!Session::has('api_token')) {
            return response('Unauthorized', 401);
        }

        try {
            $userData = Session::get('user_data');
            $userId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null;
            $username = Session::get('idempiere_username');

            $tenants = \App\Models\Idempiere\AdClient::where('isactive', 'Y')
                ->where('ad_client_id', '>', 0)
                ->orderBy('name')
                ->get(['ad_client_id as id', 'name as text']);

            $debug_first_user = \App\Models\Idempiere\AdUser::first();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to fetch iDempiere clients (partial): ' . $e->getMessage());
            $tenants = collect();
            $debug_first_user = null;
            $userId = null;
            $username = null;
            $debug_error = $e->getMessage();
        }

        return view('pages.auth.select-role-partial', [
            'tenants'          => $tenants,
            'debug_userId'     => $userId ?? null,
            'debug_username'   => $username ?? 'Not in Session',
            'debug_first_user' => $debug_first_user ?? null,
            'debug_error'      => $debug_error ?? null,
        ]);
    }

    public function showRoleSelection()
    {
        // Check if token exists
        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        try {
            // Get User ID from session
            $userData = Session::get('user_data');
            $userId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null;

            $username = Session::get('idempiere_username');

            // Fetch Tenants (Clients) - Global List as requested
            // SELECT ad_client_id AS id, name AS text FROM ad_client WHERE isactive = 'Y' AND ad_client_id > 0
            $tenants = \App\Models\Idempiere\AdClient::where('isactive', 'Y')
                ->where('ad_client_id', '>', 0)
                ->orderBy('name')
                ->get(['ad_client_id as id', 'name as text']);

            \Illuminate\Support\Facades\Log::info('Tenants found:', ['count' => count($tenants)]);

            // Roles are now fetched via AJAX based on selected Client

            // DEBUG: Fetch first user to inspect structure
            $debug_first_user = \App\Models\Idempiere\AdUser::first();

        } catch (\Exception $e) {
            // Fallback or error handling if DB connection fails
            // For now, logging the error and passing empty array
            \Illuminate\Support\Facades\Log::error('Failed to fetch iDempiere clients: ' . $e->getMessage());
            $tenants = [];
            $debug_first_user = null;
            $userId = null;
            $username = null;
            // Pass error to view for easier debugging
            $debug_error = $e->getMessage();
        }

        return view('pages.auth.select-role', [
            'title' => 'Select Role',
            'tenants' => $tenants,
            'debug_userId' => $userId,
            'debug_username' => $username ?? 'Not in Session',
            'debug_first_user' => $debug_first_user,
            'debug_error' => $debug_error ?? null
        ]);
    }

    public function getClients(Request $request)
    {
        $clients = \App\Models\Idempiere\AdClient::where('isactive', 'Y')
            ->where('ad_client_id', '>', 0)
            ->orderBy('name')
            ->get(['ad_client_id as id', 'name as text']);

        return response()->json($clients);
    }

    public function getRoles(Request $request)
    {
        $clientId = $request->input('client_id');

        // Accept user_id directly (for mobile app) or fall back to session
        $userId = $request->input('user_id');

        if (!$userId) {
            // Get User ID from session
            $userData = Session::get('user_data');
            $userId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null;

            // Use lookup fallback if needed (same logic as showRoleSelection)
            if (!$userId) {
                $username = Session::get('idempiere_username');
                if ($username) {
                    $userRecord = \App\Models\Idempiere\AdUser::where(function ($query) use ($username) {
                        $query->whereRaw('LOWER(name) = ?', [strtolower($username)])
                            ->orWhereRaw('LOWER(value) = ?', [strtolower($username)]);
                    })->select('ad_user_id')->first();
                    if ($userRecord)
                        $userId = $userRecord->ad_user_id;
                }
            }
        }

        if (!$userId || !$clientId) {
            return response()->json([]);
        }

        // Fetch Roles based on User ID AND Client ID
        // SELECT DISTINCT r.ad_role_id AS id, r.name AS text ... AND r.ad_client_id = :ad_client_id
        $rolesQuery = \App\Models\Idempiere\AdRole::query()
            ->from('ad_role as r')
            ->join('ad_user_roles as ur', 'ur.ad_role_id', '=', 'r.ad_role_id')
            ->join('ad_user as u', 'u.ad_user_id', '=', 'ur.ad_user_id')
            ->where('r.isactive', 'Y')
            ->where('ur.isactive', 'Y') // Check assignment is active
            ->where('u.isactive', 'Y')
            ->where('u.ad_user_id', $userId)
            ->whereIn('r.ad_client_id', [0, $clientId]) // Allow System roles (0) and Client roles
            ->distinct()
            ->orderBy('r.name'); // Order by name is usually better for dropdowns

        if ($request->has('q')) {
            $searchTerm = strtolower($request->input('q'));
            $rolesQuery->whereRaw('LOWER(r.name) LIKE ?', ["%{$searchTerm}%"]);
        }

        $roles = $rolesQuery->get(['r.ad_role_id as id', 'r.name as text']);

        return response()->json($roles);
    }

    public function getOrgs(Request $request)
    {
        $clientId = $request->input('client_id');
        $roleId = $request->input('role_id');

        // Accept user_id directly (for mobile app) or fall back to session
        $userId = $request->input('user_id');

        if (!$userId) {
            // Get User ID from session
            $userData = Session::get('user_data');
            $userId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null;

            // Fallback user lookup
            if (!$userId) {
                $username = Session::get('idempiere_username');
                if ($username) {
                    $userRecord = \App\Models\Idempiere\AdUser::where(function ($query) use ($username) {
                        $query->whereRaw('LOWER(name) = ?', [strtolower($username)])
                            ->orWhereRaw('LOWER(value) = ?', [strtolower($username)]);
                    })->select('ad_user_id')->first();
                    if ($userRecord)
                        $userId = $userRecord->ad_user_id;
                }
            }
        }

        if (!$userId || !$clientId || !$roleId) {
            return response()->json([]);
        }

        // Fetch Orgs based on User, Role, and Client
        // Query: SELECT DISTINCT o.ad_org_id AS id, o.name AS text FROM ad_org o ...
        $orgsQuery = \App\Models\Idempiere\AdOrg::query()
            ->from('ad_org as o')
            ->join('ad_role_orgaccess as roa', 'roa.ad_org_id', '=', 'o.ad_org_id')
            ->join('ad_user_roles as ur', 'ur.ad_role_id', '=', 'roa.ad_role_id')
            ->join('ad_user as u', 'u.ad_user_id', '=', 'ur.ad_user_id')
            ->where('o.isactive', 'Y')
            ->where('roa.isactive', 'Y')
            ->where('u.isactive', 'Y')
            ->where('u.ad_user_id', $userId)
            ->where('ur.ad_role_id', $roleId)
            ->where('o.ad_client_id', $clientId)
            ->distinct()
            ->orderBy('o.ad_org_id');

        if ($request->has('q')) {
            $searchTerm = strtolower($request->input('q'));
            $orgsQuery->whereRaw('LOWER(o.name) LIKE ?', ["%{$searchTerm}%"]);
        }

        $orgs = $orgsQuery->get(['o.ad_org_id as id', 'o.name as text']);

        // Add wildcard option
        $payload = array_merge(
            [['id' => 0, 'text' => '*']],
            $orgs->toArray()
        );

        return response()->json($payload);
    }

    public function getWarehouses(Request $request)
    {
        $roleId = $request->input('role_id');
        $orgId = $request->input('org_id');

        // Validation or checks?
        if (!$roleId || !$orgId) {
            return response()->json([]);
        }

        // Fetch Warehouses based on Role and Org
        // Query: SELECT DISTINCT w.m_warehouse_id AS id, w.name AS text FROM m_warehouse w ...
        $warehousesQuery = \App\Models\Idempiere\MWarehouse::query()
            ->from('m_warehouse as w')
            ->join('ad_role_orgaccess as roa', 'roa.ad_org_id', '=', 'w.ad_org_id')
            ->where('w.isactive', 'Y')
            ->where('roa.isactive', 'Y')
            ->where('roa.ad_role_id', $roleId)
            ->where('w.ad_org_id', $orgId);

        // If organization is * (0), we might need special handling? 
        // The user's query implicitly requires w.ad_org_id match the passed org_id.
        // If org_id is 0, it probably won't match any warehouse unless we have global warehouses?
        // Assuming strictly following query for now.

        $warehousesQuery->distinct()->orderBy('w.m_warehouse_id');

        if ($request->has('q')) {
            $searchTerm = strtolower($request->input('q'));
            $warehousesQuery->whereRaw('LOWER(w.name) LIKE ?', ["%{$searchTerm}%"]);
        }

        $warehouses = $warehousesQuery->get(['w.m_warehouse_id as id', 'w.name as text']);

        return response()->json($warehouses);
    }

    public function selectRole(Request $request)
    {
        $request->validate([
            'role_id' => 'required',
            'client_id' => 'required',
            'org_id' => 'required',
        ]);

        // Context Login
        $username = Session::get('idempiere_username');
        $password = Session::get('idempiere_auth_pwd');

        $clientId = (int) $request->input('client_id');
        $roleId = (int) $request->input('role_id');
        $orgId = (int) $request->input('org_id');
        $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        if ($username && $password) {
            $contextResult = $this->idempiereService->loginWithContext($username, $password, $clientId, $roleId, $orgId, $warehouseId);

            if ($contextResult && isset($contextResult['token'])) {
                Session::put('api_token', $contextResult['token']);

                // Update user_data with context result to ensure we have the correct userId
                $currentUserData = Session::get('user_data', []);
                if (is_array($contextResult)) {
                    $newUserData = array_merge(is_array($currentUserData) ? $currentUserData : [], $contextResult);
                    Session::put('user_data', $newUserData);
                }
            } else {
                return back()->withErrors(['msg' => 'Failed to obtain context token from iDempiere.']);
            }
        }

        // Session::forget('idempiere_auth_pwd'); // Keep password for Token Refresh as requested

        // Store selected context in session
        Session::put('idempiere_role', $roleId);
        Session::put('idempiere_client', $clientId);
        Session::put('idempiere_org', $orgId);
        Session::put('idempiere_warehouse', $warehouseId);
        Session::forget('selected_root_menu_id');

        // Perform Laravel Login (Simulated/Bypass for now to satisfy auth middleware)
        // In a real scenario, we might find a local user by email or create one.
        // Use stored username from login step
        $usernameInSession = Session::get('idempiere_username');
        $userData = Session::get('user_data');

        // Determine Display Name (Name > Value > Username > Fallback)
        $displayName = $userData['name'] ?? $userData['value'] ?? $usernameInSession ?? 'Guest User';
        $emailIdentifier = ($usernameInSession ?? 'guest') . '@example.com';

        // Update or Create local user to reflect current details
        $user = \App\Models\User::updateOrCreate(
            ['email' => $emailIdentifier],
            ['name' => $displayName, 'password' => bcrypt('password')]
        );
        \Illuminate\Support\Facades\Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function logout(): RedirectResponse
    {
        Session::forget('api_token');
        Session::forget('user_data');
        Session::forget('idempiere_role');
        Session::forget('idempiere_client');
        Session::forget('idempiere_org');
        Session::forget('idempiere_warehouse');
        Session::forget('idempiere_username');
        Session::forget('idempiere_auth_pwd');
        Session::forget('selected_root_menu_id');
        \Illuminate\Support\Facades\Auth::logout();

        return redirect()->route('signin')->withCookie(Cookie::forget(self::REMEMBER_COOKIE));
    }

    public function changeRole(Request $request)
    {
        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        Session::forget('idempiere_role');
        Session::forget('idempiere_client');
        Session::forget('idempiere_org');
        Session::forget('idempiere_warehouse');
        Session::forget('selected_root_menu_id');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('auth.roles'),
            ]);
        }

        return redirect()->route('auth.roles');
    }

    private function storeLoginSession(array $result, string $username, string $password): void
    {
        Session::put('api_token', $result['token']);
        Session::put('user_data', $result);
        Session::put('idempiere_username', $username);
        Session::put('idempiere_auth_pwd', $password);
        Session::forget('selected_root_menu_id');
    }

    private function makeRememberCookie(string $username, string $password)
    {
        return Cookie::make(
            self::REMEMBER_COOKIE,
            json_encode([
                'username' => $username,
                'password' => $password,
            ], JSON_THROW_ON_ERROR),
            self::REMEMBER_COOKIE_MINUTES
        );
    }

    private function getRememberedCredentials(Request $request): ?array
    {
        $cookieValue = $request->cookie(self::REMEMBER_COOKIE);

        if (blank($cookieValue)) {
            return null;
        }

        try {
            $credentials = json_decode($cookieValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));

            return null;
        }

        $username = trim((string) ($credentials['username'] ?? ''));
        $password = (string) ($credentials['password'] ?? '');

        if ($username === '' || $password === '') {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));

            return null;
        }

        return [
            'username' => $username,
            'password' => $password,
        ];
    }
}
