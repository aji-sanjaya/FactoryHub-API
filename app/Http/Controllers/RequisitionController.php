<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\IdempiereService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class RequisitionController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requisitionConfig = config('idempiere.create-pr');

        // 1. Check Authentication / Session Context
        if (!\Illuminate\Support\Facades\Session::has('api_token')) {
            return redirect()->route('signin');
        }

        // 2. Handle Detail View (Form) if document_id is present
        if (request()->has('document_id')) {
            $docId = request('document_id');
            $requisition = null;

            if ($docId !== 'new') {
                try {
                    $decryptedId = \Illuminate\Support\Facades\Crypt::decryptString($docId);
                    $requisition = \App\Models\Idempiere\MRequisition::find($decryptedId);

                    if (!$requisition) {
                        return redirect()->route('requisition.index')->with('error', 'Requisition not found.');
                    }
                } catch (\Exception $e) {
                    return redirect()->route('requisition.index')->with('error', 'Invalid Link');
                }
            }

            // Common Data Fetching
            $priorities = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT rl.value AS id, rl.name AS text
                FROM ad_ref_list rl
                JOIN ad_reference r ON r.ad_reference_id = rl.ad_reference_id
                WHERE r.name = '_PriorityRule' AND rl.isactive = 'Y'
                ORDER BY rl.value
            ");

            $roleId = \Illuminate\Support\Facades\Session::get('idempiere_role');
            $organizations = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT o.ad_org_id AS id, o.name AS text
                FROM ad_org o
                JOIN ad_role_orgaccess roa ON roa.ad_org_id = o.ad_org_id
                WHERE o.isactive = 'Y' AND roa.isactive = 'Y' AND roa.ad_role_id = ? AND o.ad_org_id <> 0
                ORDER BY o.name
            ", [$roleId]);

            // Fallback: If Role access is empty (e.g. SuperUser or loose config), fetch all Orgs of Client
            if (empty($organizations)) {
                $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');
                $organizations = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                    SELECT ad_org_id AS id, name AS text
                    FROM ad_org
                    WHERE ad_client_id = ? AND isactive = 'Y' AND ad_org_id <> 0
                    ORDER BY name
                ", [$clientId]);
            }

            // Determine Current Org ID
            // If New: Default to first Org (Min ID) -- consistent with previous logic
            // If Edit: Use Requisition Org
            $currentOrgId = null;
            if ($requisition) {
                $currentOrgId = $requisition->ad_org_id;
            } elseif (count($organizations) > 0) {
                $sortedOrgs = collect($organizations)->sortBy('id');
                $currentOrgId = $sortedOrgs->first()->id;
            }

            // Fetch Warehouses
            // Modified to depend only on Organization ID, removing ad_role_orgaccess JOIN which might be empty
            $warehouses = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT DISTINCT w.m_warehouse_id AS id, w.name AS text
                FROM m_warehouse w
                WHERE w.isactive = 'Y' AND w.ad_org_id = ?
                ORDER BY w.name
            ", [$currentOrgId]);

            $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');
            $pricelists = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT pl.m_pricelist_id AS id, pl.name AS text
                FROM m_pricelist pl
                WHERE pl.isactive = 'Y' AND pl.ad_client_id = ?
                ORDER BY pl.name
            ", [$clientId]);

            // Fetch Users for Checked/Approved By
            $users = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT DISTINCT u.ad_user_id AS id, u.name AS text
                FROM ad_user u
                JOIN ad_user_roles ur ON ur.ad_user_id = u.ad_user_id
                WHERE u.isactive = 'Y' AND u.ad_client_id = ?
                ORDER BY u.name
            ", [$clientId]);

            // Fetch Cost Centers
            $costCenters = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT c_costcenter_id AS id, name AS text
                FROM c_costcenter
                WHERE isactive = 'Y' AND ad_client_id = ?
                ORDER BY name
            ", [$clientId]);

            // Fetch Departments
            $departments = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT c_department_id AS id, name AS text
                FROM c_department
                WHERE isactive = 'Y' AND ad_client_id = ?
                ORDER BY name
            ", [$clientId]);

            // Fetch Client Name
            $client = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_client')
                ->where('ad_client_id', $clientId)
                ->first();
            $clientName = $client ? $client->name : 'Unknown Client';

            // Fetch Lines if Editing (Paginated)
            $defaultLinePerPage = $requisitionConfig['limits']['line_default_per_page'];
            $linePerPageOptions = $requisitionConfig['limits']['line_per_page_options'];

            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $defaultLinePerPage);
            if ($requisition) {
                // Get Current Page and Per Page
                $page = request()->get('lines_page', 1);
                $perPage = (int) request()->get('per_page', $defaultLinePerPage);
                // Validate per_page
                if (!in_array($perPage, $linePerPageOptions, true)) {
                    $perPage = $defaultLinePerPage;
                }

                // Base Query
                $query = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_requisitionline as rl')
                    ->leftJoin('m_product as p', 'p.m_product_id', '=', 'rl.m_product_id')
                    ->leftJoin('c_uom as uom', 'uom.c_uom_id', '=', 'rl.c_uom_id')
                    ->where('rl.m_requisition_id', $requisition->m_requisition_id)
                    ->where('rl.isactive', 'Y')
                    ->select(
                        'rl.m_requisitionline_id',
                        'rl.line',
                        'rl.m_product_id',
                        'p.value as product_code',
                        'p.name as product_name',
                        'rl.qty',
                        'uom.uomsymbol as uom_name',
                        'rl.priceactual',
                        'rl.linenetamt',
                        'rl.description'
                    );

                // Search
                if (request()->has('q_lines') && !empty(request('q_lines'))) {
                    $q = request('q_lines');
                    $query->where(function ($sub) use ($q) {
                        $sub->where('p.name', 'ilike', "%{$q}%")
                            ->orWhere('rl.description', 'ilike', "%{$q}%")
                            ->orWhere('p.value', 'ilike', "%{$q}%");
                    });
                }

                $query->orderBy('rl.line', 'asc');

                $lines = $query->paginate($perPage, ['*'], 'lines_page', $page);
                $lines->appends(['per_page' => $perPage, 'q_lines' => request('q_lines'), 'document_id' => request('document_id'), 'tab' => 'lines']);
            }

            $viewData = [
                'title' => $docId === 'new' ? 'Create Requisition' : 'Edit Requisition',
                'clientName' => $clientName,
                'requisition' => $requisition,
                'lines' => $lines,
                'priorities' => $priorities,
                'organizations' => $organizations,
                'warehouses' => $warehouses,
                'pricelists' => $pricelists,
                'users' => $users,
                'costCenters' => $costCenters,
                'departments' => $departments,
                'documentIdParam' => $docId,
                'docNo' => isset($requisition) ? $requisition->documentno : '** New **',
                'status' => isset($requisition) ? $requisition->status_label : $requisitionConfig['defaults']['document_status_label'],
                'desc' => isset($requisition) ? $requisition->description : '',
                'currentOrgId' => isset($requisition) ? $requisition->ad_org_id : null,
                'dateDocValue' => isset($requisition) ? \Carbon\Carbon::parse($requisition->datedoc)->format('m-d-Y') : now()->format('m-d-Y'),
                // Note: Logic for defaults is currently in Blade, but for Partials we might need it here or rely on Blade to re-calc.
                // Since I copied the Blade logic to Partials, they should re-calc defaults if variables are passed.
                // However, Blade Partials rely on variables like $isNew.
                'isNew' => is_null($requisition),
                // Pass DocId Param consistent with Blade
                'docIdParam' => request('document_id'),
                'isReadOnly' => isset($requisition) && in_array($requisition->docstatus, $requisitionConfig['statuses']['read_only'], true),
                'requisitionConfig' => $requisitionConfig,
            ];

            // AJAX Partial Rendering
            if (request()->ajax() && request()->has('ajax_tab')) {
                $tab = request()->get('ajax_tab');
                if ($tab === 'header') {
                    return view('pages.requisition.partials.tab-header', $viewData);
                } elseif ($tab === 'lines') {
                    return view('pages.requisition.partials.tab-lines', $viewData);
                } elseif ($tab === 'attachments') {
                    $attachments = [];
                    // Using existing $requisition object
                    if (isset($requisition)) {
                        $url = "models/m_requisition/{$requisition->m_requisition_id}/attachments";
                        \Illuminate\Support\Facades\Log::info("Fetching attachments for ID {$requisition->m_requisition_id} from: $url");

                        $response = $this->idempiereService->get($url);

                        \Illuminate\Support\Facades\Log::info("Attachment Response Status: " . $response->status());
                        \Illuminate\Support\Facades\Log::info("Attachment Response Body: " . $response->body());

                        if ($response->successful()) {
                            $json = $response->json();

                            // Log the keys to help debug
                            \Illuminate\Support\Facades\Log::info("Attachment Keys: " . implode(', ', array_keys($json)));

                            if (isset($json['attachments'])) {
                                $attachments = json_decode(json_encode($json['attachments']), FALSE);
                            } elseif (isset($json['records'])) {
                                $attachments = json_decode(json_encode($json['records']), FALSE);
                            } elseif (is_array($json) && !empty($json) && array_keys($json)[0] === 0) {
                                // Handle direct array response
                                $attachments = json_decode(json_encode($json), FALSE);
                            }
                        }
                    }
                    $viewData['attachments'] = $attachments;
                    return view('pages.requisition.partials.tab-attachments', $viewData);
                }
            }

            return view('pages.requisition.form', $viewData);
        }

        $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');
        $orgId = \Illuminate\Support\Facades\Session::get('idempiere_org');

        // Fallback for demo if no session (Optional, can be removed)
        if (!$clientId) {
            // For development safety, maybe default to 0 or handle error
            // return redirect()->route('signin')->withErrors(['msg' => 'Session expired']);
        }

        // 2. Base Query
        $query = \App\Models\Idempiere\MRequisition::query();

        if ($clientId) {
            $query->where('ad_client_id', $clientId);
        }

        // Filter by Org if selected and not wildcard (0)
        // If orgId is 0, we typically show all orgs the user has access to, 
        // OR just filter by client. For now, if > 0, strict filter.
        if ($orgId && $orgId > 0) {
            $query->where('ad_org_id', $orgId);
        }

        // 3. Calculate Statistics for Current Month (based on DateDoc)
        // "Total document requisition bulan ini"
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        // Helper to clone query for stats to apply date filter
        $statsQuery = clone $query;
        $statsQuery->whereBetween('datedoc', [$startOfMonth, $endOfMonth]);

        // Stats: Completed (CO, CL)
        $countCompleted = (clone $statsQuery)
            ->whereIn('docstatus', $requisitionConfig['statuses']['completed'])
            ->count();

        // Stats: Draft (DR, IN)
        $countDraft = (clone $statsQuery)
            ->whereIn('docstatus', $requisitionConfig['statuses']['draft'])
            ->count();

        // Stats: In Progress (IP) - Maybe include AP (Approved) as In Progress? 
        // User asked for "Inprogress".
        // Let's include IP.
        $countInProgress = (clone $statsQuery)
            ->where('docstatus', $requisitionConfig['statuses']['in_progress'])
            ->count();

        // Stats: All Documents (Total for month)
        $countAll = $statsQuery->count();


        // 4. Search & Advanced Filtering

        // Basic Search (Document No - specific or fuzzy)
        $search = request('search');
        if ($search) {
            $query->where('documentno', 'ilike', "%{$search}%");
        }

        // Advanced Filter: Description
        if (request('description')) {
            $query->where('description', 'ilike', "%" . request('description') . "%");
        }

        // Advanced Filter: Date Required (using datedoc or date_required if exists)
        // Adjust column if 'date_required' exists, otherwise 'datedoc'
        if (request('date_required_start')) {
            $query->whereDate('datedoc', '>=', request('date_required_start'));
        }
        if (request('date_required_end')) {
            $query->whereDate('datedoc', '<=', request('date_required_end'));
        }

        // Advanced Filter: Status
        if (request('status')) {
            $query->where('docstatus', request('status'));
        }

        // 5. Fetch List Data (Paginated)
        // Sort by Created desc or DateDoc desc
        $requisitions = $query->orderBy('datedoc', 'desc')
            ->orderBy('created', 'desc')
            ->paginate($requisitionConfig['limits']['list_per_page'])
            ->withQueryString();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.requisition.requisition-table', ['requisitions' => $requisitions])->render(),
                'pagination' => (string) $requisitions->links()
            ]);
        }

        return view('pages.requisition.index', [
            'title' => 'Requisition',
            'requisitions' => $requisitions,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted,
        ]);
    }

    public function create()
    {
        return redirect()->route('requisition.index', ['document_id' => 'new']);
    }

    public function getWarehouses(Request $request)
    {
        $roleId = \Illuminate\Support\Facades\Session::get('idempiere_role');
        $orgId = $request->input('org_id');

        if (!$roleId || !$orgId)
            return response()->json([]);

        $warehouses = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
            SELECT DISTINCT
                w.m_warehouse_id AS id,
                w.name           AS text
            FROM m_warehouse w
            JOIN ad_role_orgaccess roa 
                    ON roa.ad_org_id = w.ad_org_id
            WHERE w.isactive = 'Y'
                AND roa.isactive = 'Y'
                AND roa.ad_role_id = ?
                AND w.ad_org_id = ?
            ORDER BY w.name
        ", [$roleId, $orgId]);

        return response()->json($warehouses);
    }

    public function store(Request $request)
    {
        // 1. Validate Form Input
        $validated = $request->validate([
            'org_id' => 'required',
            'warehouse_id' => 'required',
            'date_required' => 'required|date_format:m-d-Y',
            'description' => 'nullable|string',
            'pricelist_id' => 'required',
            'priority_rule' => 'required',
            'adw_ad_user_checked_id' => 'nullable',
            'adw_ad_user_approved_id' => 'nullable',
            'cost_center_id' => 'nullable',
            'c_department_id' => 'nullable',
            'datedoc' => 'required|date_format:m-d-Y',
        ]);

        // Get Context
        $sessionClientId = \Illuminate\Support\Facades\Session::get('idempiere_client');
        $sessionRoleId = \Illuminate\Support\Facades\Session::get('idempiere_role');

        // Retrieve User ID from Session or Auth
        $userData = \Illuminate\Support\Facades\Session::get('user_data');
        $sessionUserId = null;
        if (is_array($userData)) {
            $sessionUserId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null;
        } elseif (is_object($userData)) {
            $sessionUserId = $userData->userId ?? $userData->id ?? $userData->ad_user_id ?? null;
        }

        if (!$sessionUserId) {
            return response()->json(['message' => 'User Context Not Found. Please re-login.'], 401);
        }

        if (!$sessionClientId) {
            return response()->json(['message' => 'Session expired or invalid context'], 401);
        }

        // Format Dates
        $dateRequired = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['date_required'])->format('Y-m-d');
        $dateDoc = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['datedoc'])->format('Y-m-d');

        // Payload Structure based on User Request
        $payload = [
            'AD_Client_ID' => (int) $sessionClientId,
            'AD_Org_ID' => (int) $validated['org_id'],
            'M_Warehouse_ID' => (int) $validated['warehouse_id'],
            'Description' => $validated['description'],
            'PriorityRule' => (string) $validated['priority_rule'],
            'M_PriceList_ID' => (int) $validated['pricelist_id'],
            'DateRequired' => $dateRequired,
            'DateDoc' => $dateDoc,
            'AD_User_ID' => (int) $sessionUserId,
            'C_DocType_ID' => (int) config('idempiere.create-pr.doc_types.purchase_requisition'),
            // Custom Fields
            'ADW_AD_User_Checked_ID' => !empty($validated['adw_ad_user_checked_id']) ? (int) $validated['adw_ad_user_checked_id'] : null,
            'ADW_AD_User_Approved_ID' => !empty($validated['adw_ad_user_approved_id']) ? (int) $validated['adw_ad_user_approved_id'] : null,
            'C_CostCenter_ID' => !empty($validated['cost_center_id']) ? (int) $validated['cost_center_id'] : null,
            'C_Department_ID' => !empty($validated['c_department_id']) ? (int) $validated['c_department_id'] : null,
            'IsActive' => true,
        ];

        // Remove null values
        $payload = array_filter($payload, function ($value) {
            return !is_null($value);
        });

        try {
            // API POST
            $response = $this->idempiereService->post('models/m_requisition', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['M_Requisition_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'Requisition created successfully',
                    'data' => [
                        'm_requisition_id' => $id,
                        'encrypted_id' => \Illuminate\Support\Facades\Crypt::encryptString($id)
                    ]
                ]);
            } else {
                if ($response->status() === 401) {
                    return response()->json(['message' => 'Authorization Failed. Please try logging out and logging back in.'], 401);
                }
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Requisition Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create requisition: ' . $e->getMessage()], 500);
        }
    }

    public function updateHeader(Request $request, $id)
    {
        // 1. Validate Form Input
        $validated = $request->validate([
            'org_id' => 'nullable',
            'warehouse_id' => 'nullable',
            'date_required' => 'nullable|date_format:m-d-Y',
            'description' => 'nullable|string',
            'pricelist_id' => 'nullable',
            'priority_rule' => 'nullable|string',
            'adw_ad_user_checked_id' => 'nullable',
            'adw_ad_user_approved_id' => 'nullable',
            'cost_center_id' => 'nullable',
            'c_department_id' => 'nullable',
            'datedoc' => 'nullable|date_format:m-d-Y',
        ]);

        // Prepare Payload (Only send what changed or full header?)
        // Safer to send full header fields that are editable.

        $payload = [];
        if (!empty($validated['org_id']))
            $payload['AD_Org_ID'] = (int) $validated['org_id'];
        if (!empty($validated['warehouse_id']))
            $payload['M_Warehouse_ID'] = (int) $validated['warehouse_id'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        if (!empty($validated['pricelist_id']))
            $payload['M_PriceList_ID'] = (int) $validated['pricelist_id'];
        if (isset($validated['priority_rule']))
            $payload['PriorityRule'] = (string) $validated['priority_rule'];
        if (!empty($validated['adw_ad_user_checked_id']))
            $payload['ADW_AD_User_Checked_ID'] = (int) $validated['adw_ad_user_checked_id'];
        if (!empty($validated['adw_ad_user_approved_id']))
            $payload['ADW_AD_User_Approved_ID'] = (int) $validated['adw_ad_user_approved_id'];
        if (!empty($validated['cost_center_id']))
            $payload['C_CostCenter_ID'] = (int) $validated['cost_center_id'];
        if (!empty($validated['c_department_id']))
            $payload['C_Department_ID'] = (int) $validated['c_department_id'];

        if (!empty($validated['date_required'])) {
            $payload['DateRequired'] = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['date_required'])->format('Y-m-d');
        }
        if (!empty($validated['datedoc'])) {
            $payload['DateDoc'] = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['datedoc'])->format('Y-m-d');
        }

        try {
            // API PUT
            $response = $this->idempiereService->put("models/M_Requisition/{$id}", $payload);

            if ($response->successful()) {
                return response()->json(['message' => 'Requisition updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Requisition Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update requisition: ' . $e->getMessage()], 500);
        }
    }
    public function getProducts(Request $request)
    {
        $requisitionConfig = config('idempiere.create-pr');
        $search = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $requisitionConfig['limits']['products_per_page'];

        // Get Client ID from session
        $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');

        if (!$clientId) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false]
            ]);
        }

        $query = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('m_product as p')
            ->leftJoin('c_uom as u', 'p.c_uom_id', '=', 'u.c_uom_id')
            ->select(
                'p.m_product_id as id',
                \Illuminate\Support\Facades\DB::raw("p.value || ' - ' || p.name as text"),
                'p.c_uom_id',
                'u.uomsymbol as uom_symbol',
                'u.uomsymbol as uom_name'
            )
            ->where('p.isactive', 'Y')
            ->where('p.issummary', 'N')
            ->where('p.ad_client_id', $clientId); // Filter by client

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });
        }

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get products with one extra to check if there are more
        $products = $query->offset($offset)
            ->limit($perPage + 1)
            ->orderBy('p.name')
            ->get();

        // Check if there are more results
        $hasMore = count($products) > $perPage;

        // Remove the extra item if exists
        if ($hasMore) {
            $products = $products->slice(0, $perPage)->values();
        }

        return response()->json([
            'results' => $products,
            'pagination' => [
                'more' => $hasMore
            ]
        ]);
    }

    public function getProductPrice(Request $request)
    {
        $productId = $request->get('product_id');
        $priceListId = $request->get('pricelist_id');

        if (!$productId || !$priceListId) {
            return response()->json(['price' => 0.00]);
        }

        // Query the product price from the price list
        $productPrice = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('m_productprice')
            ->where('m_product_id', $productId)
            ->where('m_pricelist_version_id', function ($query) use ($priceListId) {
                $query->select('m_pricelist_version_id')
                    ->from('m_pricelist_version')
                    ->where('m_pricelist_id', $priceListId)
                    ->where('isactive', 'Y')
                    ->orderBy('validfrom', 'desc')
                    ->limit(1);
            })
            ->where('isactive', 'Y')
            ->first();

        $price = $productPrice ? (float) $productPrice->pricestd : 0.00;

        return response()->json(['price' => $price]);
    }

    public function process(Request $request)
    {
        $requisitionConfig = config('idempiere.create-pr');
        $workflowConfig = $requisitionConfig['workflow'] ?? [];
        $reactivateAction = $workflowConfig['reactivate_action'] ?? 'RE';
        $reactivateFrom = $workflowConfig['reactivate_from'] ?? ['CO'];
        $draftStatus = \App\Models\Idempiere\MRequisition::STATUS_DRAFTED;
        $completeAction = $workflowConfig['complete_action'] ?? 'CO';
        $allowedActions = array_values(array_unique(array_merge(
            $workflowConfig['allowed_actions'] ?? [],
            [$reactivateAction]
        )));

        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:' . implode(',', $allowedActions),
        ]);

        try {
            $requisitionId = Crypt::decryptString($validated['document_id']);

            if ($validated['doc_action'] === $reactivateAction) {
                $requisition = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_requisition')
                    ->where('m_requisition_id', $requisitionId)
                    ->select('m_requisition_id', 'documentno', 'docstatus')
                    ->first();

                if (!$requisition) {
                    return response()->json(['message' => 'Requisition not found'], 404);
                }

                if (!in_array($requisition->docstatus, $reactivateFrom, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This requisition cannot be re-activated from its current status.',
                    ], 400);
                }

                \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_requisition')
                    ->where('m_requisition_id', $requisitionId)
                    ->update([
                        'docstatus' => $draftStatus,
                        'docaction' => $completeAction,
                        'processed' => 'N',
                        'processing' => 'N',
                        'processedon' => 0,
                        'adw_approved_date' => null,
                        'adw_approve_isapproved' => null,
                        'adw_checked_date' => null,
                        'adw_checked_isapproved' => null,
                        'updated' => now(),
                    ]);

                \Illuminate\Support\Facades\Log::info('Requisition re-activated via manual bypass', [
                    'requisition_id' => $requisitionId,
                    'documentno' => $requisition->documentno,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document re-activated successfully',
                    'data' => [
                        'm_requisition_id' => $requisitionId,
                        'docstatus' => $draftStatus,
                        'docaction' => $completeAction,
                    ],
                ]);
            }

            $payload = [
                'doc-action' => $validated['doc_action']
            ];

            \Illuminate\Support\Facades\Log::info('Processing requisition', [
                'requisition_id' => $requisitionId,
                'action' => $validated['doc_action']
            ]);

            // Use IdempiereService
            $response = $this->idempiereService->put("models/M_Requisition/{$requisitionId}", $payload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document processed successfully',
                    'data' => $response->json()
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error('Failed to process requisition API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID'], 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to process requisition: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to process document: ' . $e->getMessage()], 500);
        }
    }

    public function print($id)
    {
        try {
            // Decrypt ID if it's encrypted (frontend sends encrypted)
            // But wait, my route is /print/{id}. The link in blade will use encrypted ID.
            // So yes, decrypt.
            try {
                $decryptedId = Crypt::decryptString($id);
            } catch (\Exception $e) {
                // Fallback if not encrypted (testing)
                $decryptedId = $id;
            }

            $requisition = \App\Models\Idempiere\MRequisition::findOrFail($decryptedId);

            // Fetch Lines
            $lines = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_requisitionline as rl')
                ->leftJoin('m_product as p', 'p.m_product_id', '=', 'rl.m_product_id')
                ->leftJoin('c_uom as uom', 'uom.c_uom_id', '=', 'rl.c_uom_id')
                ->where('rl.m_requisition_id', $decryptedId)
                ->where('rl.isactive', 'Y')
                ->select(
                    'rl.line',
                    'p.value as product_code',
                    'p.name as product_name',
                    'rl.qty',
                    'uom.uomsymbol as uom_name',
                    'rl.priceactual',
                    'rl.daterequired',
                    'rl.description'
                )
                ->orderBy('rl.line')
                ->get();

            // Consolidate Signature Data (Name, Date, QR)

            // 1. Prepared By (CreatedBy)
            $preparedBy = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')
                ->where('ad_user_id', $requisition->createdby)
                ->value('name');

            $preparedDate = date('d M Y H:i', strtotime($requisition->updated));
            $preparedQrData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate($requisition->documentno);
            $preparedQr = "data:image/svg+xml;base64," . base64_encode($preparedQrData);

            // 2. Checked By (Step 1)
            $checkedBy = null;
            $checkedQr = null;
            $checkedDate = 'PENDING';

            if ($requisition->adw_ad_user_checked_id) {
                $checkedBy = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')
                    ->where('ad_user_id', $requisition->adw_ad_user_checked_id)
                    ->value('name');

                // Check Status (AP/RE)
                if ($requisition->adw_checked_isapproved == 'AP' && $requisition->adw_checked_date) {
                    $checkedDate = date('d M Y H:i', strtotime($requisition->adw_checked_date));
                    $checkedQrData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate("Checked by " . $checkedBy . " on " . $requisition->adw_checked_date);
                    $checkedQr = "data:image/svg+xml;base64," . base64_encode($checkedQrData);
                } elseif ($requisition->adw_checked_isapproved == 'RE') {
                    $checkedDate = 'REJECTED';
                }
            }

            // 3. Approved By (Step 2)
            $approvedBy = null;
            $approvedQr = null;
            $approvedDate = 'PENDING';

            if ($requisition->adw_ad_user_approved_id) {
                $approvedBy = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')
                    ->where('ad_user_id', $requisition->adw_ad_user_approved_id)
                    ->value('name');

                // Check Status (AP/RE)
                if ($requisition->adw_approve_isapproved == 'AP' && $requisition->adw_approved_date) {
                    $approvedDate = date('d M Y H:i', strtotime($requisition->adw_approved_date));
                    $approvedQrData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate("Approved by " . $approvedBy . " on " . $requisition->adw_approved_date);
                    $approvedQr = "data:image/svg+xml;base64," . base64_encode($approvedQrData);
                } elseif ($requisition->adw_approve_isapproved == 'RE') {
                    $approvedDate = 'REJECTED';
                }
            }

            $completedActivities = [];



            // Fetch client name
            $clientName = null;
            try {
                $clientName = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('ad_client')
                    ->where('ad_client_id', $requisition->ad_client_id)
                    ->value('name');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Client name fetch warning: ' . $e->getMessage());
            }

            // Fetch org address via c_bpartner → c_bpartner_location → c_location
            $orgInfo = null;
            try {
                $orgInfo = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_bpartner as bp')
                    ->leftJoin('c_bpartner_location as bpl', function ($join) {
                        $join->on('bp.c_bpartner_id', '=', 'bpl.c_bpartner_id')
                             ->where('bpl.isactive', '=', 'Y');
                    })
                    ->leftJoin('c_location as locbp', 'bpl.c_location_id', '=', 'locbp.c_location_id')
                    ->where('bp.c_bpartner_id', config('idempiere.client_id'))
                    ->select('locbp.address1', 'locbp.address2', 'locbp.address3')
                    ->first();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Org info fetch warning: ' . $e->getMessage());
            }

            // Fetch Tenant Logo
            $logoBase64 = null;
            try {
                $clientInfo = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('ad_clientinfo')
                    ->where('ad_client_id', $requisition->ad_client_id)
                    ->first();

                if ($clientInfo && isset($clientInfo->logo_id)) {
                    $image = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('ad_image')
                        ->where('ad_image_id', $clientInfo->logo_id)
                        ->first();

                    if ($image && $image->binarydata) {
                        // Detect mime type if possible, otherwise default to png
                        $mime = 'image/png';
                        // Convert binary stream to base64
                        // In PHP fetching bytea from PDO might return resource stream
                        if (is_resource($image->binarydata)) {
                            $content = stream_get_contents($image->binarydata);
                        } else {
                            $content = $image->binarydata;
                        }
                        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Logo fetch warning: ' . $e->getMessage());
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.requisition.pdf', [
                'requisition' => $requisition,
                'lines' => $lines,
                'preparedBy' => $preparedBy,
                'checkedBy' => $checkedBy,
                'approvedBy' => $approvedBy,
                'logoBase64' => $logoBase64,
                'preparedQr' => $preparedQr,
                'checkedQr' => $checkedQr,
                'approvedQr' => $approvedQr,
                'preparedDate' => $preparedDate,
                'checkedDate' => $checkedDate,
                'approvedDate' => $approvedDate,
                'clientName' => $clientName,
                'orgInfo' => $orgInfo,
            ])->setOptions(['isRemoteEnabled' => true]);

            $filename = 'Requisition-' . str_replace(['/', '\\'], '-', $requisition->documentno) . '.pdf';
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Print Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to print: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required'
        ]);

        try {
            $requisitionId = Crypt::decryptString($validated['document_id']);

            $linkedOrderLineExists = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_requisitionline as rl')
                ->join('c_orderline as ol', 'ol.m_requisitionline_id', '=', 'rl.m_requisitionline_id')
                ->where('rl.m_requisition_id', $requisitionId)
                ->exists();

            if ($linkedOrderLineExists) {
                return response()->json([
                    'message' => 'Cannot delete requisition because one or more requisition lines are already linked to Purchase Order lines.'
                ], 422);
            }

            \Illuminate\Support\Facades\Log::info('Deleting requisition', [
                'requisition_id' => $requisitionId
            ]);

            $response = $this->idempiereService->delete("models/m_requisition/{$requisitionId}");

            \Illuminate\Support\Facades\Log::info('API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                $responseBody = $response->json();
                $errorMsg = 'Failed to delete requisition';

                if (isset($responseBody['title'])) {
                    $errorMsg = $responseBody['title'];
                } elseif (isset($responseBody['message'])) {
                    $errorMsg = $responseBody['message'];
                } elseif (isset($responseBody['error'])) {
                    $errorMsg = $responseBody['error'];
                }

                \Illuminate\Support\Facades\Log::error('Failed to delete requisition', [
                    'requisition_id' => $requisitionId,
                    'error' => $errorMsg,
                    'full_response' => $responseBody
                ]);

                return response()->json([
                    'message' => $errorMsg,
                    'details' => $responseBody
                ], $response->status());
            }

            return response()->json([
                'success' => true,
                'message' => 'Requisition deleted successfully'
            ]);

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID'], 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete requisition: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete requisition: ' . $e->getMessage()], 500);
        }
    }
    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'document_id' => 'required',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240'
        ]);

        try {
            $docId = Crypt::decryptString($request->document_id);
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();

            $response = $this->idempiereService->uploadFile(
                "models/m_requisition/{$docId}/attachments",
                $file,
                $filename,
                $mimeType
            );

            if ($response->successful()) {
                return response()->json(['success' => true]);
            }

            return response()->json(['message' => 'Upload failed: ' . $response->body()], 500);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Upload Attachment Error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function deleteAttachment(Request $request)
    {
        $request->validate([
            'document_id' => 'required',
            'attachment_id' => 'required'
        ]);

        try {
            $docId = Crypt::decryptString($request->document_id);
            $attId = $request->attachment_id;

            // Encode ID (or filename) to handle special chars/spaces
            $encodedId = rawurlencode($attId);

            $response = $this->idempiereService->delete("models/m_requisition/{$docId}/attachments/{$encodedId}");

            if ($response->successful()) {
                return response()->json(['success' => true]);
            }
            return response()->json(['message' => 'Delete failed: ' . $response->body()], 500);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Delete Attachment Error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function viewAttachment($document_id, $file_name)
    {
        try {
            $docId = Crypt::decryptString($document_id);
            $encodedFileName = rawurlencode($file_name);

            $url = "models/m_requisition/{$docId}/attachments/{$encodedFileName}";
            $response = $this->idempiereService->get($url);

            if ($response->successful()) {
                $content = $response->body();
                $contentType = $response->header('Content-Type');

                return response($content)
                    ->header('Content-Type', $contentType)
                    ->header('Content-Security-Policy', "default-src 'self'")
                    ->header('X-Content-Type-Options', 'nosniff');
            }

            abort(404, 'Attachment not found.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('View Attachment Error: ' . $e->getMessage());
            abort(500, 'Error viewing attachment');
        }
    }
}
