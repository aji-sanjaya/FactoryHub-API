<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\IdempiereService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class DeliveryScheduleController extends Controller
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
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');

        // 1. Check Authentication / Session Context
        if (!\Illuminate\Support\Facades\Session::has('api_token')) {
            return redirect()->route('signin');
        }

        // 2. Handle Detail View (Form) if document_id is present
        if (request()->has('document_id')) {
            $docId = request('document_id');
            $deliverySchedule = null;

            if ($docId !== 'new') {
                try {
                    $decryptedId = \Illuminate\Support\Facades\Crypt::decryptString($docId);
                    $deliverySchedule = \App\Models\Idempiere\COrder::find($decryptedId);

                    if (!$deliverySchedule) {
                        return redirect()->route('delivery-schedule.index')->with('error', 'Delivery Schedule not found.');
                    }
                } catch (\Exception $e) {
                    return redirect()->route('delivery-schedule.index')->with('error', 'Invalid Link');
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
            $currentOrgId = null;
            if ($deliverySchedule) {
                $currentOrgId = $deliverySchedule->ad_org_id;
            } elseif (count($organizations) > 0) {
                $sortedOrgs = collect($organizations)->sortBy('id');
                $currentOrgId = $sortedOrgs->first()->id;
            }

            // Fetch Warehouses
            $warehouses = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT DISTINCT w.m_warehouse_id AS id, w.name AS text
                FROM m_warehouse w
                WHERE w.isactive = 'Y' AND w.ad_org_id = ?
                ORDER BY w.name
            ", [$currentOrgId]);

            $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');

            // For Delivery Schedule, Price List MUST be Sales Price List (issopricelist = 'Y')
            $pricelists = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT pl.m_pricelist_id AS id, pl.name AS text
                FROM m_pricelist pl
                WHERE pl.isactive = 'Y' AND pl.ad_client_id = ? AND pl.issopricelist = ?
                ORDER BY pl.name
            ", [$clientId, $deliveryScheduleConfig['filters']['is_sales_price_list']]);

            // Fetch Users for Checked/Approved By
            $users = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT DISTINCT u.ad_user_id AS id, u.name AS text
                FROM ad_user u
                JOIN ad_user_roles ur ON ur.ad_user_id = u.ad_user_id
                WHERE u.isactive = 'Y' AND u.ad_client_id = ?
                ORDER BY u.name
            ", [$clientId]);

            // Fetch Customers (BPartner) instead of Cost Centers
            $bpartners = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT c_bpartner_id AS id, name AS text
                FROM c_bpartner
                WHERE isactive = 'Y' AND ad_client_id = ? AND iscustomer = ?
                ORDER BY name
            ", [$clientId, $deliveryScheduleConfig['filters']['is_customer']]);

            // Fetch Client Name
            $client = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_client')
                ->where('ad_client_id', $clientId)
                ->first();
            $clientName = $client ? $client->name : 'Unknown Client';
            
            // Fetch Taxes
            $taxes = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT c_tax_id AS id, name AS text 
                FROM c_tax 
                WHERE isactive = 'Y' AND ad_client_id = ?
                ORDER BY name
            ", [$clientId]);

            // Fetch Lines if Editing (Paginated)
            $defaultLinePerPage = $deliveryScheduleConfig['limits']['line_default_per_page'];
            $linePerPageOptions = $deliveryScheduleConfig['limits']['line_per_page_options'];

            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $defaultLinePerPage);
            if ($deliverySchedule) {
                // Get Current Page and Per Page
                $page = request()->get('lines_page', 1);
                $perPage = (int) request()->get('per_page', $defaultLinePerPage);
                // Validate per_page
                if (!in_array($perPage, $linePerPageOptions, true)) {
                    $perPage = $defaultLinePerPage;
                }

                // Base Query
                $query = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_orderline as ol')
                    ->leftJoin('m_product as p', 'p.m_product_id', '=', 'ol.m_product_id')
                    ->leftJoin('c_uom as uom', 'uom.c_uom_id', '=', 'p.c_uom_id')
                    ->leftJoin('c_orderline as ref_ol', 'ref_ol.c_orderline_id', '=', 'ol.ref_orderline_id')
                    ->leftJoin('c_order as ref_o', 'ref_o.c_order_id', '=', 'ref_ol.c_order_id')
                    ->where('ol.c_order_id', $deliverySchedule->c_order_id)
                    ->where('ol.isactive', 'Y')
                    ->select(
                        'ol.c_orderline_id',
                        'ol.line',
                        'ol.m_product_id',
                        'p.value as product_code',
                        'p.name as product_name',
                        'ol.qtyentered as qty',
                        'uom.uomsymbol as uom_name',
                        'uom.stdprecision as uom_precision',
                        'ol.priceactual',
                        'ol.linenetamt',
                        'ol.description',
                        'ol.qtydelivered',
                        'ol.qtyinvoiced',
                        'ol.datepromised',
                        'ref_o.documentno as ref_so_documentno',
                        'ref_ol.line as ref_so_line'
                    );

                // Search
                if (request()->has('q_lines') && !empty(request('q_lines'))) {
                    $q = request('q_lines');
                    $query->where(function ($sub) use ($q) {
                        $sub->where('p.name', 'ilike', "%{$q}%")
                            ->orWhere('ol.description', 'ilike', "%{$q}%")
                            ->orWhere('p.value', 'ilike', "%{$q}%");
                    });
                }

                $query->orderBy('ol.line', 'asc');

                $lines = $query->paginate($perPage, ['*'], 'lines_page', $page);
                $lines->appends(['per_page' => $perPage, 'q_lines' => request('q_lines'), 'document_id' => request('document_id'), 'tab' => 'lines']);
            }

            $viewData = [
                'title' => $docId === 'new' ? 'Create Delivery Schedule' : 'Edit Delivery Schedule',
                'clientName' => $clientName,
                'deliverySchedule' => $deliverySchedule,
                'lines' => $lines,
                'priorities' => $priorities,
                'organizations' => $organizations,
                'warehouses' => $warehouses,
                'pricelists' => $pricelists,
                'users' => $users,
                'bpartners' => $bpartners,
                'documentIdParam' => $docId,
                'docNo' => isset($deliverySchedule) ? $deliverySchedule->documentno : '** New **',
                'status' => isset($deliverySchedule) ? $deliverySchedule->status_label : $deliveryScheduleConfig['defaults']['document_status_label'],
                'desc' => isset($deliverySchedule) ? $deliverySchedule->description : '',
                'currentOrgId' => isset($deliverySchedule) ? $deliverySchedule->ad_org_id : null,
                'isNew' => is_null($deliverySchedule),
                'docIdParam' => request('document_id'),
                'isReadOnly' => isset($deliverySchedule) && in_array($deliverySchedule->docstatus, $deliveryScheduleConfig['statuses']['read_only'], true),
                'taxes' => $taxes,
                'deliveryScheduleConfig' => $deliveryScheduleConfig,
                'priceListPrecision' => isset($deliverySchedule) && $deliverySchedule->m_pricelist_id
                    ? (int) (\Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('m_pricelist')
                        ->where('m_pricelist_id', $deliverySchedule->m_pricelist_id)
                        ->value('priceprecision') ?? $deliveryScheduleConfig['defaults']['price_precision'])
                    : $deliveryScheduleConfig['defaults']['price_precision'],
            ];

            // AJAX Partial Rendering
            if (request()->ajax() && request()->has('ajax_tab')) {
                $tab = request()->get('ajax_tab');
                if ($tab === 'header') {
                    return view('pages.delivery-schedule.partials.tab-header', $viewData);
                } elseif ($tab === 'lines') {
                    return view('pages.delivery-schedule.partials.tab-lines', $viewData);
                } elseif ($tab === 'attachments') {
                    $attachments = [];
                    if (isset($deliverySchedule)) {
                        $url = "models/c_order/{$deliverySchedule->c_order_id}/attachments";
                        \Illuminate\Support\Facades\Log::info("Fetching attachments for ID {$deliverySchedule->c_order_id} from: $url");

                        $response = $this->idempiereService->get($url);

                        if ($response->successful()) {
                            $json = $response->json();

                            if (isset($json['attachments'])) {
                                $attachments = json_decode(json_encode($json['attachments']), FALSE);
                            } elseif (isset($json['records'])) {
                                $attachments = json_decode(json_encode($json['records']), FALSE);
                            } elseif (is_array($json) && !empty($json) && array_keys($json)[0] === 0) {
                                $attachments = json_decode(json_encode($json), FALSE);
                            }
                        }
                    }
                    $viewData['attachments'] = $attachments;
                    return view('pages.delivery-schedule.partials.tab-attachments', $viewData);
                }
            }

            return view('pages.delivery-schedule.form', $viewData);
        }

        $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');
        $orgId = \Illuminate\Support\Facades\Session::get('idempiere_org');

        // 2. Base Query
        $query = \App\Models\Idempiere\COrder::query()
            ->where('issotrx', $deliveryScheduleConfig['defaults']['is_so_trx'])
            ->where('c_doctypetarget_id', $deliveryScheduleConfig['doc_types']['target']);

        if ($clientId) {
            $query->where('ad_client_id', $clientId);
        }

        if ($orgId && $orgId > 0) {
            $query->where('ad_org_id', $orgId);
        }

        // 3. Calculate Statistics for Current Month (based on DateOrdered)
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        $statsQuery = clone $query;
        $statsQuery->whereBetween('dateordered', [$startOfMonth, $endOfMonth]);

        $countCompleted = (clone $statsQuery)
            ->whereIn('docstatus', $deliveryScheduleConfig['statuses']['completed'])
            ->count();

        $countDraft = (clone $statsQuery)
            ->whereIn('docstatus', $deliveryScheduleConfig['statuses']['draft'])
            ->count();

        $countInProgress = (clone $statsQuery)
            ->where('docstatus', $deliveryScheduleConfig['statuses']['in_progress'])
            ->count();

        $countAll = $statsQuery->count();

        // 4. Search & Advanced Filtering
        $search = request('search');
        if ($search) {
            $query->where('documentno', 'ilike', "%{$search}%");
        }

        if (request('description')) {
            $query->where('description', 'ilike', "%" . request('description') . "%");
        }

        if (request('date_required_start')) {
            $query->whereDate('datepromised', '>=', request('date_required_start'));
        }
        if (request('date_required_end')) {
            $query->whereDate('datepromised', '<=', request('date_required_end'));
        }

        if (request('status')) {
            $query->where('docstatus', request('status'));
        }

        // 5. Fetch List Data (Paginated)
        $deliverySchedules = $query->orderBy('dateordered', 'desc')
            ->orderBy('created', 'desc')
            ->paginate($deliveryScheduleConfig['limits']['list_per_page'])
            ->withQueryString();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.delivery-schedule.delivery-schedule-table', ['deliverySchedules' => $deliverySchedules])->render(),
                'pagination' => (string) $deliverySchedules->links()
            ]);
        }

        return view('pages.delivery-schedule.index', [
            'title' => 'Delivery Schedule',
            'deliverySchedules' => $deliverySchedules,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted,
        ]);
    }

    public function create()
    {
        return redirect()->route('delivery-schedule.index', ['document_id' => 'new']);
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

    public function getSalesOrders(Request $request) {
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        $bpartnerId = $request->input('bpartner_id');
        $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');

        if (!$clientId) return response()->json([]);

        $query = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_order')
            ->select('c_order_id as id', 'documentno as text')
            ->where('ad_client_id', $clientId)
            ->where('c_doctypetarget_id', $deliveryScheduleConfig['doc_types']['source_sales_order'])
            ->where('docstatus', $deliveryScheduleConfig['defaults']['source_doc_status']);
        
        if ($bpartnerId) {
            $query->where('c_bpartner_id', $bpartnerId);
        }

        $orders = $query->orderBy('documentno', 'desc')->limit($deliveryScheduleConfig['limits']['sales_orders_per_page'])->get();
        return response()->json($orders);
    }

    public function getBPartnerLocations(Request $request)
    {
        $bpartnerId = $request->input('bpartner_id');
        if (!$bpartnerId) {
            return response()->json([]);
        }

        $locations = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_bpartner_location')
            ->where('c_bpartner_id', $bpartnerId)
            ->where('isactive', 'Y')
            ->get(['c_bpartner_location_id as id', 'name as text']);

        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        // 1. Validate Form Input
        $validated = $request->validate([
            'org_id' => 'required',
            'warehouse_id' => 'required',
            'date_required' => 'required|date_format:m-d-Y',
            'date_ordered' => 'nullable|date_format:m-d-Y',
            'description' => 'nullable|string',
            'pricelist_id' => 'required',
            'c_bpartner_id' => 'required',
            'c_bpartner_location_id' => 'nullable',
            'bill_bpartner_id' => 'nullable',
            'bill_location_id' => 'nullable',
            'order_reference' => 'nullable|string',
            'c_doctypetarget_id' => 'nullable|integer',
            'c_tax_id' => 'nullable|integer',
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
        $datePromised = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['date_required'])->format('Y-m-d');
        $dateOrdered = !empty($validated['date_ordered'])
            ? \Carbon\Carbon::createFromFormat('m-d-Y', $validated['date_ordered'])->format('Y-m-d')
            : now()->format('Y-m-d');

        // Payload Structure
        $payload = [
            'AD_Client_ID' => (int) $sessionClientId,
            'AD_Org_ID' => (int) $validated['org_id'],
            'M_Warehouse_ID' => (int) $validated['warehouse_id'],
            'Description' => $validated['description'],
            'M_PriceList_ID' => (int) $validated['pricelist_id'],
            'DatePromised' => $datePromised,
            'DateOrdered' => $dateOrdered,
            'AD_User_ID' => (int) $sessionUserId,
            'C_BPartner_ID' => (int) $validated['c_bpartner_id'],
            'IsSOTrx' => $deliveryScheduleConfig['defaults']['is_so_trx'] === 'Y',
            'IsActive' => true,
            'POReference' => $validated['order_reference'] ?? null,
            'C_DocTypeTarget_ID' => $deliveryScheduleConfig['doc_types']['target'],
            'C_Tax_ID' => !empty($validated['c_tax_id']) ? (int) $validated['c_tax_id'] : null,
        ];

        // BPartner Location
        if (!empty($validated['c_bpartner_location_id'])) {
            $payload['C_BPartner_Location_ID'] = (int) $validated['c_bpartner_location_id'];
        } else {
            $location = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_bpartner_location')
                ->where('c_bpartner_id', $validated['c_bpartner_id'])
                ->where('isactive', 'Y')
                ->first();
            if ($location) {
                $payload['C_BPartner_Location_ID'] = $location->c_bpartner_location_id;
            }
        }

        // Bill-to BPartner / Location
        if (!empty($validated['bill_bpartner_id'])) {
            $payload['Bill_BPartner_ID'] = (int) $validated['bill_bpartner_id'];
        }
        if (!empty($validated['bill_location_id'])) {
            $payload['Bill_Location_ID'] = (int) $validated['bill_location_id'];
        }


        // Remove null values
        $payload = array_filter($payload, function ($value) {
            return !is_null($value);
        });

        try {
            // API POST
            $response = $this->idempiereService->post('models/c_order', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['C_Order_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'Delivery Schedule created successfully',
                    'data' => [
                        'c_order_id' => $id,
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
            \Illuminate\Support\Facades\Log::error('Delivery Schedule Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create sales order: ' . $e->getMessage()], 500);
        }
    }

    public function updateHeader(Request $request, $id)
    {
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        // 1. Validate Form Input
        $validated = $request->validate([
            'org_id' => 'nullable',
            'warehouse_id' => 'nullable',
            'date_required' => 'nullable|date_format:m-d-Y',
            'date_ordered' => 'nullable|date_format:m-d-Y',
            'description' => 'nullable|string',
            'pricelist_id' => 'nullable',
            'c_bpartner_id' => 'nullable',
            'c_bpartner_location_id' => 'nullable',
            'bill_bpartner_id' => 'nullable',
            'bill_location_id' => 'nullable',
            'order_reference' => 'nullable|string',
            'c_doctypetarget_id' => 'nullable|integer',
            'c_tax_id' => 'nullable|integer',
        ]);

        $payload = [];
        if (!empty($validated['org_id']))
            $payload['AD_Org_ID'] = (int) $validated['org_id'];
        if (!empty($validated['warehouse_id']))
            $payload['M_Warehouse_ID'] = (int) $validated['warehouse_id'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        if (!empty($validated['pricelist_id']))
            $payload['M_PriceList_ID'] = (int) $validated['pricelist_id'];
        if (!empty($validated['c_bpartner_id']))
            $payload['C_BPartner_ID'] = (int) $validated['c_bpartner_id'];
        if (!empty($validated['c_bpartner_location_id']))
            $payload['C_BPartner_Location_ID'] = (int) $validated['c_bpartner_location_id'];
        if (!empty($validated['bill_bpartner_id']))
            $payload['Bill_BPartner_ID'] = (int) $validated['bill_bpartner_id'];
        if (!empty($validated['bill_location_id']))
            $payload['Bill_Location_ID'] = (int) $validated['bill_location_id'];
        if (isset($validated['order_reference']))
            $payload['POReference'] = $validated['order_reference'];
            
        $payload['C_DocTypeTarget_ID'] = $deliveryScheduleConfig['doc_types']['target'];
        
        if (!empty($validated['c_tax_id']))
            $payload['C_Tax_ID'] = (int) $validated['c_tax_id'];

        if (!empty($validated['date_required'])) {
            $payload['DatePromised'] = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['date_required'])->format('Y-m-d');
        }
        if (!empty($validated['date_ordered'])) {
            $payload['DateOrdered'] = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['date_ordered'])->format('Y-m-d');
        }

        try {
            // API PUT
            $response = $this->idempiereService->put("models/c_order/{$id}", $payload);

            if ($response->successful()) {
                return response()->json(['message' => 'Delivery Schedule updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Delivery Schedule Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update sales order: ' . $e->getMessage()], 500);
        }
    }
    public function getProducts(Request $request)
    {
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        $search = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $deliveryScheduleConfig['limits']['products_per_page'];

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
                'u.uomsymbol as uom_name',
                'u.stdprecision as uom_precision'
            )
            ->where('p.isactive', 'Y')
            ->where('p.issummary', 'N')
            ->where('p.ad_client_id', $clientId); // Filter by client

        // Only sold products, not purchased (finished goods for sale)
        $query->where('p.issold', $deliveryScheduleConfig['filters']['is_sold']);

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
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');

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

        // Fetch price precision from price list
        $pricePrecision = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('m_pricelist')
            ->where('m_pricelist_id', $priceListId)
            ->value('priceprecision');

        return response()->json([
            'price' => $price,
            'price_precision' => (int) ($pricePrecision ?? $deliveryScheduleConfig['defaults']['price_precision']),
        ]);
    }

    public function process(Request $request)
    {
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        $allowedActions = $deliveryScheduleConfig['workflow']['allowed_actions'];
        $reactivateAction = $deliveryScheduleConfig['workflow']['reactivate_action'];

        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:' . implode(',', $allowedActions),
        ]);

        try {
            $orderId = Crypt::decryptString($validated['document_id']);

            $payload = [
                'doc-action' => $validated['doc_action']
            ];

            \Illuminate\Support\Facades\Log::info('Processing order', [
                'c_order_id' => $orderId,
                'action' => $validated['doc_action']
            ]);

            // Use IdempiereService
            $response = $this->idempiereService->put("models/c_order/{$orderId}", $payload);

            if ($response->successful()) {
                // Custom Logic for Re-Active Action - Execute AFTER successful doc-action
                if ($validated['doc_action'] === $reactivateAction) {
                    // Get all order lines with qtydelivered information
                    $orderLines = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('c_orderline')
                        ->where('c_order_id', $orderId)
                        ->where('isactive', 'Y')
                        ->select('c_orderline_id', 'qtydelivered', 'qtyreserved')
                        ->get();

                    \Illuminate\Support\Facades\Log::info('Re-Active: Checking order lines after successful doc-action', [
                        'c_order_id' => $orderId,
                        'total_lines' => $orderLines->count()
                    ]);

                    // Update qtyreserved to 0 for lines where qtydelivered = 0
                    foreach ($orderLines as $line) {
                        if ($line->qtydelivered == 0) {
                            \Illuminate\Support\Facades\DB::connection('idempiere')
                                ->table('c_orderline')
                                ->where('c_orderline_id', $line->c_orderline_id)
                                ->update(['qtyreserved' => 0]);

                            \Illuminate\Support\Facades\Log::info('Re-Active: Reset qtyreserved to 0', [
                                'c_orderline_id' => $line->c_orderline_id,
                                'qtydelivered' => $line->qtydelivered
                            ]);
                        } else {
                            \Illuminate\Support\Facades\Log::info('Re-Active: Skip line with qtydelivered > 0', [
                                'c_orderline_id' => $line->c_orderline_id,
                                'qtydelivered' => $line->qtydelivered
                            ]);
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Document processed successfully',
                    'data' => $response->json()
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error('Failed to process order API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID'], 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to process order: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to process document: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
        ]);

        try {
            $orderId = Crypt::decryptString($validated['document_id']);

            $response = $this->idempiereService->delete("models/c_order/{$orderId}");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document deleted successfully'
                ]);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID'], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete document: ' . $e->getMessage()], 500);
        }
    }

    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'document_id' => 'required',
            'file' => 'required|file|max:10240' // 10MB
        ]);

        try {
            $docId = Crypt::decryptString($request->document_id);
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();

            // Use IdempiereService to post attachment
            $response = $this->idempiereService->uploadFile(
                "models/c_order/{$docId}/attachments",
                $file,
                $filename,
                $mimeType
            );

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Uploaded']);
            }

            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $response->body()], 400);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
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
            $encodedId = rawurlencode($attId);

            // Delete specific attachment endpoint
            $response = $this->idempiereService->delete("models/c_order/{$docId}/attachments/{$encodedId}");

            if ($response->successful()) {
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => 'Delete failed: ' . $response->body()], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function print($id)
    {
        try {
            try {
                $decryptedId = Crypt::decryptString($id);
            } catch (\Exception $e) {
                $decryptedId = $id;
            }

            $deliverySchedule = \App\Models\Idempiere\COrder::findOrFail($decryptedId);

            // Fetch Lines
            $lines = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_orderline as ol')
                ->leftJoin('m_product as p', 'p.m_product_id', '=', 'ol.m_product_id')
                ->leftJoin('c_uom as uom', 'uom.c_uom_id', '=', 'ol.c_uom_id')
                ->where('ol.c_order_id', $decryptedId)
                ->where('ol.isactive', 'Y')
                ->select(
                    'ol.line',
                    'p.value as product_code',
                    'p.name as product_name',
                    'ol.qtyentered as qty',
                    'uom.uomsymbol as uom_name',
                    'ol.priceentered as priceactual',
                    'ol.linenetamt',
                    'ol.description'
                )
                ->orderBy('ol.line')
                ->get();

            // Prepared By (CreatedBy)
            $preparedBy = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')
                ->where('ad_user_id', $deliverySchedule->createdby)
                ->value('description');

            $preparedDate = date('d M Y H:i', strtotime($deliverySchedule->updated));
            $preparedQr = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($deliverySchedule->documentno);

            $checkedBy = null;
            $checkedQr = null;
            $checkedDate = 'PENDING';

            $approvedBy = null;
            $approvedQr = null;
            $approvedDate = 'PENDING';

            // Fetch Tenant Logo
            $logoBase64 = null;
            try {
                $clientInfo = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('ad_clientinfo')
                    ->where('ad_client_id', $deliverySchedule->ad_client_id)
                    ->first();

                if ($clientInfo && isset($clientInfo->logo_id)) {
                    $image = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('ad_image')
                        ->where('ad_image_id', $clientInfo->logo_id)
                        ->first();

                    if ($image && $image->binarydata) {
                        $mime = 'image/png';
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

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.delivery-schedule.pdf', [
                'deliverySchedule' => $deliverySchedule,
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
            ])->setOptions(['isRemoteEnabled' => true]);

            $filename = 'DeliverySchedule-' . str_replace(['/', '\\'], '-', $deliverySchedule->documentno) . '.pdf';
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Delivery Schedule Print Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to print: ' . $e->getMessage());
        }
    }

    public function viewAttachment($document_id, $file_name)
    {
        try {
            $docId = Crypt::decryptString($document_id);
            $encodedFileName = rawurlencode($file_name);

            $url = "models/c_order/{$docId}/attachments/{$encodedFileName}";
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

    public function getSOLines(Request $request)
    {
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        $documentId = $request->query('document_id');
        $soFilter = $request->query('so_document_no', '');

        if (!$documentId) {
            return response()->json(['data' => []]);
        }

        try {
            $orderId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['data' => []], 400);
        }

        // Get schedule's c_bpartner_id
        $schedule = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_order')
            ->where('c_order_id', $orderId)
            ->first(['c_bpartner_id']);

        if (!$schedule || !$schedule->c_bpartner_id) {
            return response()->json(['data' => []]);
        }

        $bpartnerId = $schedule->c_bpartner_id;

        $query = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_orderline as ol')
            ->join('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
            ->join('m_product as p', 'p.m_product_id', '=', 'ol.m_product_id')
            ->leftJoin('c_uom as uom', 'uom.c_uom_id', '=', 'p.c_uom_id')
            ->where('o.c_bpartner_id', $bpartnerId)
            ->where('o.docstatus', $deliveryScheduleConfig['defaults']['source_doc_status'])
            ->where('o.issotrx', $deliveryScheduleConfig['defaults']['is_so_trx'])
            ->where('ol.isactive', 'Y')
            ->where('o.c_doctypetarget_id', $deliveryScheduleConfig['doc_types']['source_sales_order'])
            ->select(
                'ol.c_orderline_id',
                'ol.c_order_id',
                'o.documentno as so_document_no',
                'o.datepromised',
                'ol.line as so_line',
                'ol.m_product_id',
                'p.value as product_code',
                'p.name as product_name',
                'ol.qtyentered',
                'ol.qtyordered',
                'ol.qtyscheduled',
                'ol.priceactual',
                'ol.c_tax_id',
                'uom.uomsymbol'
            );

        if (!empty($soFilter)) {
            $query->where('o.documentno', 'ilike', "%{$soFilter}%");
        }

        $query->orderBy('o.datepromised', 'asc')
              ->orderBy('o.documentno', 'asc')
              ->orderBy('ol.line', 'asc')
              ->limit($deliveryScheduleConfig['limits']['source_lines_limit']);

        $soLines = $query->get();

        $result = [];
        foreach ($soLines as $line) {
            $qtyBalance = max(0, (float)$line->qtyentered - (float)$line->qtyscheduled);
            
            // Skip lines that have no remaining quantity
            if ($qtyBalance <= 0) {
                continue;
            }
            
            $result[] = [
                'c_orderline_id' => $line->c_orderline_id,
                'c_order_id' => $line->c_order_id,
                'so_document_no' => $line->so_document_no,
                'date_promised' => $line->datepromised,
                'so_line' => $line->so_line,
                'm_product_id' => $line->m_product_id,
                'product_code' => $line->product_code,
                'product_name' => $line->product_name,
                'uomsymbol' => $line->uomsymbol,
                'qty' => $qtyBalance,
                'qty_balance' => $qtyBalance,
                'qtyscheduled' => (float)$line->qtyscheduled,
                'priceactual' => $line->priceactual,
                'c_tax_id' => $line->c_tax_id
            ];
        }

        $soDocuments = collect($result)->pluck('so_document_no')->unique()->values();

        return response()->json([
            'data' => $result,
            'so_documents' => $soDocuments,
        ]);
    }
}
