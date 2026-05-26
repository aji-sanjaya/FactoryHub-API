<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\IdempiereService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class SalesOrderController extends Controller
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
        $salesOrderConfig = config('idempiere.sales-order');

        // 1. Check Authentication / Session Context
        if (!\Illuminate\Support\Facades\Session::has('api_token')) {
            return redirect()->route('signin');
        }

        // 2. Handle Detail View (Form) if document_id is present
        if (request()->has('document_id')) {
            $docId = request('document_id');
            $salesOrder = null;

            if ($docId !== 'new') {
                try {
                    $decryptedId = \Illuminate\Support\Facades\Crypt::decryptString($docId);
                    $salesOrder = \App\Models\Idempiere\COrder::find($decryptedId);

                    if (!$salesOrder) {
                        return redirect()->route('sales-order.index')->with('error', 'Sales Order not found.');
                    }
                } catch (\Exception $e) {
                    return redirect()->route('sales-order.index')->with('error', 'Invalid Link');
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
            if ($salesOrder) {
                $currentOrgId = $salesOrder->ad_org_id;
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

            // For Sales Order, Price List MUST be Sales Price List (issopricelist = 'Y')
            $pricelists = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT pl.m_pricelist_id AS id, pl.name AS text
                FROM m_pricelist pl
                WHERE pl.isactive = 'Y' AND pl.ad_client_id = ? AND pl.issopricelist = ?
                ORDER BY pl.name
            ", [$clientId, $salesOrderConfig['filters']['is_sales_price_list']]);

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
            ", [$clientId, $salesOrderConfig['filters']['is_customer']]);

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
            $defaultLinePerPage = $salesOrderConfig['limits']['line_default_per_page'];
            $linePerPageOptions = $salesOrderConfig['limits']['line_per_page_options'];

            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $defaultLinePerPage);
            if ($salesOrder) {
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
                    ->where('ol.c_order_id', $salesOrder->c_order_id)
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
                        'ol.datepromised'
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
                'title' => $docId === 'new' ? 'Create Sales Order' : 'Edit Sales Order',
                'clientName' => $clientName,
                'salesOrder' => $salesOrder,
                'lines' => $lines,
                'priorities' => $priorities,
                'organizations' => $organizations,
                'warehouses' => $warehouses,
                'pricelists' => $pricelists,
                'users' => $users,
                'bpartners' => $bpartners,
                'documentIdParam' => $docId,
                'docNo' => isset($salesOrder) ? $salesOrder->documentno : '** New **',
                'status' => isset($salesOrder) ? $salesOrder->status_label : $salesOrderConfig['defaults']['document_status_label'],
                'desc' => isset($salesOrder) ? $salesOrder->description : '',
                'currentOrgId' => isset($salesOrder) ? $salesOrder->ad_org_id : null,
                'isNew' => is_null($salesOrder),
                'docIdParam' => request('document_id'),
                'isReadOnly' => isset($salesOrder) && in_array($salesOrder->docstatus, $salesOrderConfig['statuses']['read_only'], true),
                'taxes' => $taxes,
                'salesOrderConfig' => $salesOrderConfig,
                'priceListPrecision' => isset($salesOrder) && $salesOrder->m_pricelist_id
                    ? (int) (\Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('m_pricelist')
                        ->where('m_pricelist_id', $salesOrder->m_pricelist_id)
                        ->value('priceprecision') ?? $salesOrderConfig['defaults']['price_precision'])
                    : $salesOrderConfig['defaults']['price_precision'],
            ];

            // AJAX Partial Rendering
            if (request()->ajax() && request()->has('ajax_tab')) {
                $tab = request()->get('ajax_tab');
                if ($tab === 'header') {
                    return view('pages.sales-order.partials.tab-header', $viewData);
                } elseif ($tab === 'lines') {
                    return view('pages.sales-order.partials.tab-lines', $viewData);
                } elseif ($tab === 'attachments') {
                    $attachments = [];
                    if (isset($salesOrder)) {
                        $url = "models/c_order/{$salesOrder->c_order_id}/attachments";
                        \Illuminate\Support\Facades\Log::info("Fetching attachments for ID {$salesOrder->c_order_id} from: $url");

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
                    return view('pages.sales-order.partials.tab-attachments', $viewData);
                }
            }

            return view('pages.sales-order.form', $viewData);
        }

        $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');
        $orgId = \Illuminate\Support\Facades\Session::get('idempiere_org');

        // 2. Base Query
        $query = \App\Models\Idempiere\COrder::query()->where('issotrx', $salesOrderConfig['defaults']['is_so_trx']);

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
            ->whereIn('docstatus', $salesOrderConfig['statuses']['completed'])
            ->count();

        $countDraft = (clone $statsQuery)
            ->whereIn('docstatus', $salesOrderConfig['statuses']['draft'])
            ->count();

        $countInProgress = (clone $statsQuery)
            ->where('docstatus', $salesOrderConfig['statuses']['in_progress'])
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
        $salesOrders = $query->orderBy('dateordered', 'desc')
            ->orderBy('created', 'desc')
            ->paginate($salesOrderConfig['limits']['list_per_page'])
            ->withQueryString();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.sales-order.sales-order-table', ['salesOrders' => $salesOrders])->render(),
                'pagination' => (string) $salesOrders->links()
            ]);
        }

        return view('pages.sales-order.index', [
            'title' => 'Sales Order',
            'salesOrders' => $salesOrders,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted,
        ]);
    }

    public function create()
    {
        return redirect()->route('sales-order.index', ['document_id' => 'new']);
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
        $salesOrderConfig = config('idempiere.sales-order');

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
            'IsSOTrx' => $salesOrderConfig['defaults']['is_so_trx'] === 'Y',
            'IsActive' => true,
            'POReference' => $validated['order_reference'] ?? null,
            'C_DocTypeTarget_ID' => !empty($validated['c_doctypetarget_id']) ? (int) $validated['c_doctypetarget_id'] : $salesOrderConfig['doc_types']['target'],
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
                    'message' => 'Sales Order created successfully',
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
            \Illuminate\Support\Facades\Log::error('Sales Order Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create sales order: ' . $e->getMessage()], 500);
        }
    }

    public function updateHeader(Request $request, $id)
    {
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
        if (!empty($validated['c_doctypetarget_id']))
            $payload['C_DocTypeTarget_ID'] = (int) $validated['c_doctypetarget_id'];
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
                return response()->json(['message' => 'Sales Order updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Sales Order Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update sales order: ' . $e->getMessage()], 500);
        }
    }
    public function getProducts(Request $request)
    {
        $salesOrderConfig = config('idempiere.sales-order');
        $search = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $salesOrderConfig['limits']['products_per_page'];

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
        $query->where('p.issold', $salesOrderConfig['filters']['is_sold']);

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
        $salesOrderConfig = config('idempiere.sales-order');

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
            'price_precision' => (int) ($pricePrecision ?? $salesOrderConfig['defaults']['price_precision']),
        ]);
    }

    public function process(Request $request)
    {
        $salesOrderConfig = config('idempiere.sales-order');
        $allowedActions = $salesOrderConfig['workflow']['allowed_actions'];
        $reactivateAction = $salesOrderConfig['workflow']['reactivate_action'];

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

            $salesOrder = \App\Models\Idempiere\COrder::findOrFail($decryptedId);

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
                ->where('ad_user_id', $salesOrder->createdby)
                ->value('description');

            $preparedDate = date('d M Y H:i', strtotime($salesOrder->updated));
            $preparedQr = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($salesOrder->documentno);

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
                    ->where('ad_client_id', $salesOrder->ad_client_id)
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

            // Fetch Client Name
            $client = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('ad_client')
                ->where('ad_client_id', $salesOrder->ad_client_id)
                ->first();
            $clientName = $client ? $client->name : 'Unknown Client';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.sales-order.pdf', [
                'salesOrder' => $salesOrder,
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
            ])->setOptions(['isRemoteEnabled' => true]);

            $filename = 'SalesOrder-' . str_replace(['/', '\\'], '-', $salesOrder->documentno) . '.pdf';
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Sales Order Print Error: ' . $e->getMessage());
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
}
