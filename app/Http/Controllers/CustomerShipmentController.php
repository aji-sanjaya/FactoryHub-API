<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\IdempiereService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class CustomerShipmentController extends Controller
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
        $customerShipmentConfig = config('idempiere.customer-shipment');

        // 1. Check Authentication / Session Context
        if (!\Illuminate\Support\Facades\Session::has('api_token')) {
            return redirect()->route('signin');
        }

        // 2. Handle Detail View (Form) if document_id is present
        if (request()->has('document_id')) {
            $docId = request('document_id');
            $customerShipment = null;

            if ($docId !== 'new') {
                try {
                    $decryptedId = \Illuminate\Support\Facades\Crypt::decryptString($docId);
                    $customerShipment = \App\Models\Idempiere\CInOut::find($decryptedId);

                    if (!$customerShipment) {
                        return redirect()->route('customer-shipment.index')->with('error', 'Customer Shipment not found.');
                    }
                } catch (\Exception $e) {
                    return redirect()->route('customer-shipment.index')->with('error', 'Invalid Link');
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
            if ($customerShipment) {
                $currentOrgId = $customerShipment->ad_org_id;
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

            // Fetch Users for Checked/Approved By
            $users = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT DISTINCT u.ad_user_id AS id, u.name AS text
                FROM ad_user u
                JOIN ad_user_roles ur ON ur.ad_user_id = u.ad_user_id
                WHERE u.isactive = 'Y' AND u.ad_client_id = ?
                ORDER BY u.name
            ", [$clientId]);

            // Fetch Customers (BPartner)
            $bpartners = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT c_bpartner_id AS id, name AS text
                FROM c_bpartner
                WHERE isactive = 'Y' AND ad_client_id = ? AND iscustomer = ?
                ORDER BY name
            ", [$clientId, $customerShipmentConfig['filters']['is_customer']]);

            // Fetch Client Name
            $client = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_client')
                ->where('ad_client_id', $clientId)
                ->first();
            $clientName = $client ? $client->name : 'Unknown Client';

            // Fetch Lines if Editing (Paginated)
            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            if ($customerShipment) {
                // Get Current Page and Per Page
                $page = request()->get('lines_page', 1);
                $perPage = request()->get('per_page', 10);
                // Validate per_page
                if (!in_array($perPage, [10, 25, 50, 100])) {
                    $perPage = 10;
                }

                // Base Query
                $query = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_inoutline as iol')
                    ->leftJoin('m_product as p', 'p.m_product_id', '=', 'iol.m_product_id')
                    ->leftJoin('c_uom as uom', 'uom.c_uom_id', '=', 'p.c_uom_id')
                    ->leftJoin('m_locator as loc', 'loc.m_locator_id', '=', 'iol.m_locator_id')
                    ->leftJoin('c_orderline as ol', 'ol.c_orderline_id', '=', 'iol.c_orderline_id')
                    ->leftJoin('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
                    ->where('iol.m_inout_id', $customerShipment->m_inout_id)
                    ->where('iol.isactive', 'Y') 
                    ->select(
                        'iol.m_inoutline_id',
                        'iol.line',
                        'iol.m_product_id',
                        'p.value as product_code',
                        'p.name as product_name',
                        'iol.movementqty as qty',
                        'uom.uomsymbol as uom_name',
                        'uom.stdprecision as uom_precision',
                        'iol.description',
                        'iol.m_locator_id',
                        'loc.value as locator_name',
                        'iol.c_orderline_id',
                        'o.documentno as so_documentno'
                    );

                // Search
                if (request()->has('q_lines') && !empty(request('q_lines'))) {
                    $q = request('q_lines');
                    $query->where(function ($sub) use ($q) {
                        $sub->where('p.name', 'ilike', "%{$q}%")
                            ->orWhere('iol.description', 'ilike', "%{$q}%")
                            ->orWhere('p.value', 'ilike', "%{$q}%");
                    });
                }

                $query->orderBy('iol.line', 'asc');

                $lines = $query->paginate($perPage, ['*'], 'lines_page', $page);
                $lines->appends(['per_page' => $perPage, 'q_lines' => request('q_lines'), 'document_id' => request('document_id'), 'tab' => 'lines']);
            }

            $viewData = [
                'title' => $docId === 'new' ? 'Create Customer Shipment' : 'Edit Customer Shipment',
                'clientName' => $clientName,
                'shipment' => $customerShipment,
                'lines' => $lines,
                'priorities' => $priorities,
                'organizations' => $organizations,
                'warehouses' => $warehouses,
                'users' => $users,
                'bpartners' => $bpartners,
                'documentIdParam' => $docId,
                'docNo' => isset($customerShipment) ? $customerShipment->documentno : '** New **',
                'status' => isset($customerShipment) ? $customerShipment->status_label : 'Drafted',
                'desc' => isset($customerShipment) ? $customerShipment->description : '',
                'currentOrgId' => isset($customerShipment) ? $customerShipment->ad_org_id : null,
                'isNew' => is_null($customerShipment),
                'docIdParam' => request('document_id'),
                'isReadOnly' => isset($customerShipment) && in_array($customerShipment->docstatus, $customerShipmentConfig['statuses']['read_only']),
            ];

            // AJAX Partial Rendering
            if (request()->ajax() && request()->has('ajax_tab')) {
                $tab = request()->get('ajax_tab');
                if ($tab === 'header') {
                    return view('pages.customer-shipment.partials.tab-header', $viewData);
                } elseif ($tab === 'lines') {
                    return view('pages.customer-shipment.partials.tab-lines', $viewData);
                } elseif ($tab === 'attachments') {
                    $attachments = [];
                    if (isset($customerShipment)) {
                        $url = "models/m_inout/{$customerShipment->m_inout_id}/attachments";
                        \Illuminate\Support\Facades\Log::info("Fetching attachments for ID {$customerShipment->m_inout_id} from: $url");

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
                    return view('pages.customer-shipment.partials.tab-attachments', $viewData);
                } elseif ($tab === 'journals') {
                    $journals = collect();
                    if (isset($customerShipment)) {
                        $journals = \Illuminate\Support\Facades\DB::connection('idempiere')
                            ->table('fact_acct as fa')
                            ->join('c_elementvalue as ev', 'ev.c_elementvalue_id', '=', 'fa.account_id')
                            ->select(
                                'ev.value as account_value',
                                'ev.name as account_name',
                                'fa.description',
                                \Illuminate\Support\Facades\DB::raw('COALESCE(fa.amtsourcedr, 0) as amt_source_dr'),
                                \Illuminate\Support\Facades\DB::raw('COALESCE(fa.amtsourcecr, 0) as amt_source_cr'),
                                \Illuminate\Support\Facades\DB::raw('COALESCE(fa.amtacctdr, 0) as amt_acct_dr'),
                                \Illuminate\Support\Facades\DB::raw('COALESCE(fa.amtacctcr, 0) as amt_acct_cr')
                            )
                            ->where('fa.ad_table_id', $customerShipmentConfig['journals']['table_id'])
                            ->where('fa.record_id', $customerShipment->m_inout_id)
                            ->orderBy('fa.fact_acct_id')
                            ->paginate(10)
                            ->appends(['ajax_tab' => 'journals']);
                    }
                    $viewData['journals'] = $journals;
                    return view('pages.customer-shipment.partials.tab-journals', $viewData);
                }
            }

            return view('pages.customer-shipment.form', $viewData);
        }

        $clientId = \Illuminate\Support\Facades\Session::get('idempiere_client');
        $orgId = \Illuminate\Support\Facades\Session::get('idempiere_org');

        // 2. Base Query - Filter for Customer Shipments (issotrx='Y' AND movementtype='C-')
        $query = \App\Models\Idempiere\CInOut::query()
            ->where('issotrx', $customerShipmentConfig['defaults']['is_so_trx'])
            ->where('movementtype', $customerShipmentConfig['defaults']['movement_type']);

        if ($clientId) {
            $query->where('ad_client_id', $clientId);
        }

        if ($orgId && $orgId > 0) {
            $query->where('ad_org_id', $orgId);
        }

        // 3. Calculate Statistics for Current Month (based on MovementDate)
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        $statsQuery = clone $query;
        $statsQuery->whereBetween('movementdate', [$startOfMonth, $endOfMonth]);

        $countCompleted = (clone $statsQuery)
            ->whereIn('docstatus', $customerShipmentConfig['statuses']['completed'])
            ->count();

        $countDraft = (clone $statsQuery)
            ->whereIn('docstatus', $customerShipmentConfig['statuses']['draft'])
            ->count();

        $countInProgress = (clone $statsQuery)
            ->where('docstatus', $customerShipmentConfig['statuses']['in_progress'])
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

        if (request('date_from')) {
            $query->whereDate('movementdate', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('movementdate', '<=', request('date_to'));
        }

        if (!request()->has('status')) {
            // Initial page load, or pagination without explicit filter params
            $query->whereIn('docstatus', $customerShipmentConfig['statuses']['default_list']);
        } else {
            $statusStr = request('status');
            if (!empty($statusStr)) {
                $statusArr = is_array($statusStr) ? $statusStr : explode(',', $statusStr);
                $query->whereIn('docstatus', $statusArr);
            } else {
                // If status is passed empty (user unchecked all status), match nothing
                $query->whereRaw('0 = 1');
            }
        }

        // 5. Fetch List Data (Paginated)
        $shipments = $query->orderBy('movementdate', 'desc')
            ->orderBy('created', 'desc')
            ->paginate(10)
            ->withQueryString();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.customer-shipment.customer-shipmentTable', ['shipments' => $shipments])->render(),
                'pagination' => (string) $shipments->links()
            ]);
        }

        return view('pages.customer-shipment.index', [
            'title' => 'Customer Shipment',
            'shipments' => $shipments,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted,
        ]);
    }

    public function create()
    {
        return redirect()->route('customer-shipment.index', ['document_id' => 'new']);
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
            ->table('c_bpartner_location as bpl')
            ->join('c_location as l', 'bpl.c_location_id', '=', 'l.c_location_id')
            ->where('bpl.c_bpartner_id', $bpartnerId)
            ->where('bpl.isactive', 'Y')
            ->get(['bpl.c_bpartner_location_id as id', 'l.address1 as text']);

        return response()->json($locations);
    }

    public function getProducts(Request $request)
    {
        $customerShipmentConfig = config('idempiere.customer-shipment');
        $search = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $customerShipmentConfig['limits']['products_per_page'];

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
        $query->where('p.issold', $customerShipmentConfig['filters']['is_sold']);

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

    /**
     * Get Sales Order lines with qty balance for "From SO" feature.
     * Only returns lines from Completed SOs matching the shipment's c_bpartner_id,
     * where QtyOrdered > total delivered qty (from all Draft+IP+Complete shipments).
     */
    public function getSOLines(Request $request)
    {
        $customerShipmentConfig = config('idempiere.customer-shipment');
        $documentId = $request->query('document_id');
        $soFilter = $request->query('so_document_no', '');
        $datePromisedFilter = $request->query('date_promised', '');

        if (!$documentId) {
            return response()->json(['data' => []]);
        }

        try {
            $shipmentId = Crypt::decryptString($documentId);
        } catch (DecryptException $e) {
            return response()->json(['data' => []], 400);
        }

        // Get shipment's c_bpartner_id
        $shipment = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('m_inout')
            ->where('m_inout_id', $shipmentId)
            ->first(['c_bpartner_id']);

        if (!$shipment || !$shipment->c_bpartner_id) {
            return response()->json(['data' => []]);
        }

        $bpartnerId = $shipment->c_bpartner_id;

        // Build query: get SO lines from Completed SOs for this bpartner
        // with qty balance > 0
        // qty_balance = qtyordered - SUM(all m_inoutline.movementqty where m_inout.docstatus IN ('DR','IP','CO') and linked to this SO line)
        $query = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_orderline as ol')
            ->join('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
            ->join('m_product as p', 'p.m_product_id', '=', 'ol.m_product_id')
            ->leftJoin('c_uom as uom', 'uom.c_uom_id', '=', 'p.c_uom_id')
            ->where('o.c_bpartner_id', $bpartnerId)
            ->where('o.docstatus', $customerShipmentConfig['filters']['source_sales_order_doc_status'])
            ->where('o.issotrx', $customerShipmentConfig['defaults']['is_so_trx'])
            ->where('ol.isactive', 'Y')
            ->where('o.c_doctypetarget_id', $customerShipmentConfig['doc_types']['source_sales_order'])
            ->select(
                'ol.c_orderline_id',
                'ol.c_order_id',
                'o.documentno as so_document_no',
                'ol.line',
                'ol.m_product_id',
                'p.value as product_code',
                'p.name as product_name',
                'ol.qtyordered',
                'ol.datepromised',
                'uom.uomsymbol as uom_name',
                'uom.stdprecision as uom_precision',
                'ol.description'
            );

        // Filter by SO document number
        if (!empty($soFilter)) {
            $query->where('o.documentno', 'ilike', "%{$soFilter}%");
        }

        // Filter by date promised
        if (!empty($datePromisedFilter)) {
            $query->whereDate('ol.datepromised', $datePromisedFilter);
        }

        $query->orderBy('o.documentno', 'asc')
              ->orderBy('ol.line', 'asc');

        $soLines = $query->get();

        // Calculate delivered qty for each SO line (from all shipments with docstatus DR, IP, CO)
        $result = [];
        foreach ($soLines as $line) {
            $deliveredQty = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_inoutline as iol')
                ->join('m_inout as io', 'io.m_inout_id', '=', 'iol.m_inout_id')
                ->where('iol.c_orderline_id', $line->c_orderline_id)
                ->whereIn('io.docstatus', $customerShipmentConfig['statuses']['delivery_progress'])
                ->where('io.issotrx', $customerShipmentConfig['defaults']['is_so_trx'])
                ->sum('iol.movementqty');

            $qtyBalance = $line->qtyordered - $deliveredQty;

            if ($qtyBalance > 0) {
                $result[] = [
                    'c_orderline_id' => $line->c_orderline_id,
                    'c_order_id' => $line->c_order_id,
                    'so_document_no' => $line->so_document_no,
                    'line' => $line->line,
                    'm_product_id' => $line->m_product_id,
                    'product_code' => $line->product_code,
                    'product_name' => $line->product_name,
                    'qty_ordered' => (float) $line->qtyordered,
                    'qty_delivered' => (float) $deliveredQty,
                    'qty_balance' => (float) $qtyBalance,
                    'date_promised' => $line->datepromised,
                    'uom_name' => $line->uom_name,
                    'uom_precision' => $line->uom_precision ?? 2,
                    'description' => $line->description,
                ];
            }
        }

        // Get distinct SO document numbers for filter dropdown
        $soDocuments = collect($result)->pluck('so_document_no')->unique()->values();

        return response()->json([
            'data' => $result,
            'so_documents' => $soDocuments,
        ]);
    }

    public function store(Request $request)
    {
        $customerShipmentConfig = config('idempiere.customer-shipment');
        // 1. Validate Form Input
        $validated = $request->validate([
            'org_id' => 'required',
            'warehouse_id' => 'required',
            'movement_date' => 'required|date_format:m-d-Y',
            'description' => 'nullable|string',
            'c_bpartner_id' => 'required',
            'c_bpartner_location_id' => 'nullable',
            'shipment_reference' => 'nullable|string',
            'c_doctype_id' => 'nullable|integer',
            'deliveryviarule' => 'nullable|string|in:D,P,S',
            'freightcostrule' => 'nullable|string|in:C,F,I,U',
            'm_shipper_id' => 'nullable|integer',
            'a_asset_id' => 'nullable|integer',
            'salesrep_id' => 'nullable|integer',
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

        // Format Date
        $movementDate = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['movement_date'])->format('Y-m-d');

        // Payload Structure
        $payload = [
            'AD_Client_ID' => (int) $sessionClientId,
            'AD_Org_ID' => (int) $validated['org_id'],
            'M_Warehouse_ID' => (int) $validated['warehouse_id'],
            'Description' => $validated['description'],
            'MovementDate' => $movementDate,
            'AD_User_ID' => (int) $sessionUserId,
            'C_BPartner_ID' => (int) $validated['c_bpartner_id'],
            'IsSOTrx' => $customerShipmentConfig['defaults']['is_so_trx'] === 'Y',
            'MovementType' => $customerShipmentConfig['defaults']['movement_type'],
            'IsActive' => true,
            'POReference' => $validated['shipment_reference'] ?? null,
            'C_DocType_ID' => !empty($validated['c_doctype_id']) ? (int) $validated['c_doctype_id'] : null,
            'DeliveryViaRule' => $validated['deliveryviarule'] ?? $customerShipmentConfig['defaults']['delivery_via_rule'],
            'FreightCostRule' => $validated['freightcostrule'] ?? $customerShipmentConfig['defaults']['freight_cost_rule'],
        ];

        if (!empty($validated['m_shipper_id']) && ($validated['deliveryviarule'] ?? '') === 'S') {
            $payload['M_Shipper_ID'] = (int) $validated['m_shipper_id'];
        }

        if (!empty($validated['a_asset_id'])) {
            $payload['A_Asset_ID'] = (int) $validated['a_asset_id'];
        }
        if (!empty($validated['salesrep_id'])) {
            $payload['SalesRep_ID'] = (int) $validated['salesrep_id'];
        }

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

        // Remove null values
        $payload = array_filter($payload, function ($value) {
            return !is_null($value);
        });

        try {
            // API POST
            $response = $this->idempiereService->post('models/m_inout', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['M_InOut_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'Customer Shipment created successfully',
                    'data' => [
                        'm_inout_id' => $id,
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
            \Illuminate\Support\Facades\Log::error('Customer Shipment Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create customer shipment: ' . $e->getMessage()], 500);
        }
    }

    public function updateHeader(Request $request, $id)
    {
        // 1. Validate Form Input
        $validated = $request->validate([
            'org_id' => 'nullable',
            'warehouse_id' => 'nullable',
            'movement_date' => 'nullable|date_format:m-d-Y',
            'description' => 'nullable|string',
            'c_bpartner_id' => 'nullable',
            'c_bpartner_location_id' => 'nullable',
            'shipment_reference' => 'nullable|string',
            'c_doctype_id' => 'nullable|integer',
            'deliveryviarule' => 'nullable|string|in:D,P,S',
            'freightcostrule' => 'nullable|string|in:C,F,I,U',
            'm_shipper_id' => 'nullable|integer',
            'a_asset_id' => 'nullable|integer',
            'salesrep_id' => 'nullable|integer',
        ]);

        $payload = [];
        if (!empty($validated['org_id']))
            $payload['AD_Org_ID'] = (int) $validated['org_id'];
        if (!empty($validated['warehouse_id']))
            $payload['M_Warehouse_ID'] = (int) $validated['warehouse_id'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        if (!empty($validated['c_bpartner_id']))
            $payload['C_BPartner_ID'] = (int) $validated['c_bpartner_id'];
        if (!empty($validated['c_bpartner_location_id']))
            $payload['C_BPartner_Location_ID'] = (int) $validated['c_bpartner_location_id'];
        if (isset($validated['shipment_reference']))
            $payload['POReference'] = $validated['shipment_reference'];
        if (!empty($validated['c_doctype_id']))
            $payload['C_DocType_ID'] = (int) $validated['c_doctype_id'];
        if (isset($validated['deliveryviarule']))
            $payload['DeliveryViaRule'] = $validated['deliveryviarule'];
        if (isset($validated['freightcostrule']))
            $payload['FreightCostRule'] = $validated['freightcostrule'];
        if (!empty($validated['m_shipper_id']) && ($validated['deliveryviarule'] ?? '') === 'S') {
            $payload['M_Shipper_ID'] = (int) $validated['m_shipper_id'];
        }

        if (!empty($validated['a_asset_id'])) {
            $payload['A_Asset_ID'] = (int) $validated['a_asset_id'];
        }
        if (!empty($validated['salesrep_id'])) {
            $payload['SalesRep_ID'] = (int) $validated['salesrep_id'];
        }

        if (!empty($validated['movement_date'])) {
            $payload['MovementDate'] = \Carbon\Carbon::createFromFormat('m-d-Y', $validated['movement_date'])->format('Y-m-d');
        }

        try {
            // API PUT
            $response = $this->idempiereService->put("models/m_inout/{$id}", $payload);

            if ($response->successful()) {
                return response()->json(['message' => 'Customer Shipment updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Customer Shipment Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update customer shipment: ' . $e->getMessage()], 500);
        }
    }

    public function process(Request $request)
    {
        $customerShipmentConfig = config('idempiere.customer-shipment');
        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:CO,PR,VO,CL,RE',
        ]);

        try {
            $shipmentId = Crypt::decryptString($validated['document_id']);

            $action = $validated['doc_action'];
            $isReActive = false;

            if ($action === 'RE') {
                $action = 'RC';
                $isReActive = true;
            }

            $payload = [
                'doc-action' => $action
            ];

            \Illuminate\Support\Facades\Log::info('Processing customer shipment', [
                'm_inout_id' => $shipmentId,
                'action' => $action
            ]);

            // Use IdempiereService
            $response = $this->idempiereService->put("models/m_inout/{$shipmentId}", $payload);

            if ($response->successful()) {
                $newDocumentId = null;

                if ($isReActive) {
                    try {
                        $originalDoc = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_inout')
                            ->where('m_inout_id', $shipmentId)
                            ->first();

                        if ($originalDoc) {
                            $newPayload = [
                                'AD_Client_ID' => $originalDoc->ad_client_id,
                                'AD_Org_ID' => $originalDoc->ad_org_id,
                                'M_Warehouse_ID' => $originalDoc->m_warehouse_id,
                                'Description' => $originalDoc->description,
                                'MovementDate' => $originalDoc->movementdate,
                                'C_BPartner_ID' => $originalDoc->c_bpartner_id,
                                'C_BPartner_Location_ID' => $originalDoc->c_bpartner_location_id,
                                'IsSOTrx' => $customerShipmentConfig['defaults']['is_so_trx'] === 'Y',
                                'MovementType' => $customerShipmentConfig['defaults']['movement_type'],
                                'IsActive' => true,
                                'POReference' => $originalDoc->poreference,
                                'C_DocType_ID' => $originalDoc->c_doctype_id,
                                'DeliveryViaRule' => $originalDoc->deliveryviarule,
                                'FreightCostRule' => $originalDoc->freightcostrule,
                                'M_Shipper_ID' => $originalDoc->m_shipper_id,
                                'C_Order_ID' => $originalDoc->c_order_id,
                            ];

                            $newPayload = array_filter((array) $newPayload, fn($v) => !is_null($v));
                            $newDocResponse = $this->idempiereService->post('models/m_inout', $newPayload);

                            if ($newDocResponse->successful()) {
                                $newDocData = $newDocResponse->json();
                                $newMInOutId = $newDocData['id'] ?? $newDocData['M_InOut_ID'] ?? $newDocData['recordID'] ?? null;

                                if ($newMInOutId) {
                                    $newDocumentId = Crypt::encryptString($newMInOutId);

                                    $originalLines = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_inoutline')
                                        ->where('m_inout_id', $shipmentId)
                                        ->where('isactive', 'Y')
                                        ->get();

                                    foreach ($originalLines as $line) {
                                        $linePayload = [
                                            'AD_Client_ID' => $line->ad_client_id,
                                            'AD_Org_ID' => $line->ad_org_id,
                                            'M_InOut_ID' => $newMInOutId,
                                            'M_Locator_ID' => $line->m_locator_id,
                                            'M_Product_ID' => $line->m_product_id,
                                            'C_UOM_ID' => $line->c_uom_id,
                                            'MovementQty' => $line->movementqty,
                                            'QtyEntered' => $line->qtyentered,
                                            'Description' => $line->description,
                                            'C_OrderLine_ID' => $line->c_orderline_id,
                                            'IsActive' => true,
                                        ];
                                        $linePayload = array_filter((array) $linePayload, fn($v) => !is_null($v));
                                        $this->idempiereService->post('models/m_inoutline', $linePayload);
                                    }

                                    // Switch Document No via manual query
                                    $originalDocNo = $originalDoc->documentno;
                                    $newDocRecord = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_inout')->where('m_inout_id', $newMInOutId)->first();
                                    $newDocNo = $newDocRecord->documentno;

                                    if ($originalDocNo && $newDocNo) {
                                        // Set old reversed document to newDocNo
                                        \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_inout')
                                            ->where('m_inout_id', $shipmentId)
                                            ->update(['documentno' => $newDocNo]);

                                        // Set the new draft document to the original document no
                                        \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_inout')
                                            ->where('m_inout_id', $newMInOutId)
                                            ->update(['documentno' => $originalDocNo]);
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to copy document on Re-Active: ' . $e->getMessage());
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Document processed successfully',
                    'data' => $response->json(),
                    'new_document_id' => $newDocumentId,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error('Failed to process customer shipment API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID'], 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to process customer shipment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to process document: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
        ]);

        try {
            $shipmentId = Crypt::decryptString($validated['document_id']);

            $response = $this->idempiereService->delete("models/m_inout/{$shipmentId}");

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
                "models/m_inout/{$docId}/attachments",
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
            $response = $this->idempiereService->delete("models/m_inout/{$docId}/attachments/{$encodedId}");

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
        return $this->generatePdf($id, 'pages.customer-shipment.pdf');
    }

    public function printStyle2($id)
    {
        return $this->generatePdf($id, 'pages.customer-shipment.pdf-style2');
    }

    public function printStyle3($id)
    {
        return $this->generatePdf($id, 'pages.customer-shipment.pdf-style3');
    }

    protected function generatePdf($id, $viewName)
    {
        try {
            try {
                $decryptedId = Crypt::decryptString($id);
            } catch (\Exception $e) {
                $decryptedId = $id;
            }

            $customerShipment = \App\Models\Idempiere\CInOut::findOrFail($decryptedId);

            // Fetch Lines
            $lines = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_inoutline as iol')
                ->leftJoin('m_product as p', 'p.m_product_id', '=', 'iol.m_product_id')
                ->leftJoin('c_uom as uom', 'uom.c_uom_id', '=', 'iol.c_uom_id')
                ->leftJoin('m_locator as loc', 'loc.m_locator_id', '=', 'iol.m_locator_id')
                ->leftJoin('c_orderline as ol', 'ol.c_orderline_id', '=', 'iol.c_orderline_id')
                ->where('iol.m_inout_id', $decryptedId)
                ->where('iol.isactive', 'Y')
                ->select(
                    'iol.line',
                    'p.value as product_code',
                    'p.name as product_name',
                    'iol.movementqty as qty',
                    'uom.uomsymbol as uom_name',
                    'iol.description',
                    'loc.value as locator_name',
                    'iol.poreference as line_poref',
                    'ol.poreference as ol_poref'
                )
                ->orderBy('iol.line')
                ->get();

            // Customer info
            $customerName = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_bpartner')
                ->where('c_bpartner_id', $customerShipment->c_bpartner_id)
                ->value('name') ?? '-';

            // Customer address
            $customerAddress = '-';
            if ($customerShipment->c_bpartner_location_id) {
                $custLoc = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_bpartner_location as bpl')
                    ->leftJoin('c_location as cl', 'cl.c_location_id', '=', 'bpl.c_location_id')
                    ->where('bpl.c_bpartner_location_id', $customerShipment->c_bpartner_location_id)
                    ->select('cl.address1', 'cl.address2', 'cl.city', 'cl.postal')
                    ->first();
                if ($custLoc) {
                    $parts = array_filter([$custLoc->address1, $custLoc->address2, $custLoc->city, $custLoc->postal]);
                    $customerAddress = implode(', ', $parts) ?: '-';
                }
            }

            // Contact person
            $contactPerson = '-';
            if ($customerShipment->ad_user_id) {
                $contactPerson = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')
                    ->where('ad_user_id', $customerShipment->ad_user_id)
                    ->value('name') ?? '-';
            }

            // SO info
            $soDocumentNo = '-';
            $soDate = null;
            $soPoReference = '-';
            if ($customerShipment->c_order_id) {
                $so = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_order')
                    ->where('c_order_id', $customerShipment->c_order_id)
                    ->select('documentno', 'dateordered', 'poreference')
                    ->first();
                if ($so) {
                    $soDocumentNo = $so->documentno ?? '-';
                    $soDate = $so->dateordered ? date('m/d/Y', strtotime($so->dateordered)) : null;
                    $soPoReference = $so->poreference ?? '-';
                }
            }

            // Org info
            $orgInfo = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_orginfo as oi')
                ->leftJoin('c_location as cl', 'cl.c_location_id', '=', 'oi.c_location_id')
                ->where('oi.ad_org_id', $customerShipment->ad_org_id)
                ->select('cl.address1', 'cl.address2', 'cl.city', 'cl.postal', 'oi.phone', 'oi.fax')
                ->first();

            $orgName = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_org')
                ->where('ad_org_id', $customerShipment->ad_org_id)
                ->value('name') ?? '-';

            $clientName = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_client')
                ->where('ad_client_id', $customerShipment->ad_client_id)
                ->value('name') ?? '-';

            $orgAddress1 = '';
            $orgAddress2 = '';
            $orgAddress3 = '';
            $orgPhone = '-';
            $orgFax = '-';
            if ($orgInfo) {
                $orgAddress1 = $orgInfo->address1 ?? '';
                $orgAddress2 = $orgInfo->address2 ?? '';
                $orgAddress3 = ($orgInfo->city ? $orgInfo->city . ', ' : '') . ($orgInfo->postal ?: '');
                $orgPhone = $orgInfo->phone ?: '-';
                $orgFax = $orgInfo->fax ?: '-';
            }

            // Override with company business partner address to match Requisition PDF
            try {
                $compBP = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_bpartner as bp')
                    ->leftJoin('c_bpartner_location as bpl', function ($join) {
                        $join->on('bp.c_bpartner_id', '=', 'bpl.c_bpartner_id')
                             ->where('bpl.isactive', '=', 'Y');
                    })
                    ->leftJoin('c_location as locbp', 'bpl.c_location_id', '=', 'locbp.c_location_id')
                    ->where('bp.c_bpartner_id', config('idempiere.client_id'))
                    ->select('locbp.address1', 'locbp.address2', 'locbp.address3')
                    ->first();
                if ($compBP) {
                    $orgAddress1 = $compBP->address1 ?? '';
                    $orgAddress2 = $compBP->address2 ?? '';
                    $orgAddress3 = $compBP->address3 ?? '';
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Company Address override warning: ' . $e->getMessage());
            }

            // Fetch Logo from iDempiere
            $logoBase64 = null;
            try {
                $clientInfo = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('ad_clientinfo')
                    ->where('ad_client_id', $customerShipment->ad_client_id)
                    ->first();

                if ($clientInfo && isset($clientInfo->logo_id)) {
                    $image = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('ad_image')
                        ->where('ad_image_id', $clientInfo->logo_id)
                        ->first();

                    if ($image && $image->binarydata) {
                        $content = is_resource($image->binarydata)
                            ? stream_get_contents($image->binarydata)
                            : $image->binarydata;
                        $logoBase64 = 'data:image/png;base64,' . base64_encode($content);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Customer Shipment Logo fetch warning: ' . $e->getMessage());
            }

            // Fallback to local logo if iDempiere logo not available
            if (!$logoBase64) {
                $logoPath = public_path('assets/media/logos/logo-long.png');
                if (file_exists($logoPath)) {
                    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($logoPath));
                }
            }

            // Prepared By (CreatedBy)
            $preparedBy = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')
                ->where('ad_user_id', $customerShipment->createdby)
                ->value('name') ?? '-';

            // Shipper Name
            $shipperName = '-';
            if ($customerShipment->m_shipper_id) {
                $shipperName = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_shipper')
                    ->where('m_shipper_id', $customerShipment->m_shipper_id)
                    ->value('name') ?? '-';
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, [
                'shipment' => $customerShipment,
                'lines' => $lines,
                'customerName' => $customerName,
                'customerAddress' => $customerAddress,
                'contactPerson' => $contactPerson,
                'soDocumentNo' => $soDocumentNo,
                'soDate' => $soDate,
                'soPoReference' => $soPoReference,
                'clientName' => $clientName,
                'orgName' => $orgName,
                'orgAddress1' => $orgAddress1,
                'orgAddress2' => $orgAddress2,
                'orgAddress3' => $orgAddress3,
                'orgPhone' => $orgPhone,
                'orgFax' => $orgFax,
                'preparedBy' => $preparedBy,
                'shipperName' => $shipperName,
                'logoBase64' => $logoBase64,
            ]);

            $filename = 'DeliveryOrder-' . str_replace(['/', '\\'], '-', $customerShipment->documentno) . '.pdf';
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Customer Shipment Print Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to print: ' . $e->getMessage());
        }
    }

    public function viewAttachment($document_id, $file_name)
    {
        try {
            $docId = Crypt::decryptString($document_id);
            $encodedFileName = rawurlencode($file_name);

            $url = "models/m_inout/{$docId}/attachments/{$encodedFileName}";
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

    public function repost(Request $request, $id)
    {
        try {
            $decryptedId = Crypt::decryptString($id);
            $receipt = \App\Models\Idempiere\CInOut::findOrFail($decryptedId);

            // Trigger re-post by setting posted to N and processing to N
            $receipt->posted = 'N';
            $receipt->processing = 'N';
            $receipt->save();

            return response()->json([
                'success' => true,
                'message' => 'Customer Shipment marked for re-posting successfully.'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Customer Shipment Repost Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger re-post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportJournals($id)
    {
        try {
            $customerShipmentConfig = config('idempiere.customer-shipment');
            $decryptedId = Crypt::decryptString($id);
            $receipt = \App\Models\Idempiere\CInOut::findOrFail($decryptedId);

            $journals = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('fact_acct as fa')
                ->join('c_elementvalue as ev', 'ev.c_elementvalue_id', '=', 'fa.account_id')
                ->select(
                    'ev.value as account_value',
                    'ev.name as account_name',
                    \Illuminate\Support\Facades\DB::raw('COALESCE(fa.amtacctdr, 0) as amt_acct_dr'),
                    \Illuminate\Support\Facades\DB::raw('COALESCE(fa.amtacctcr, 0) as amt_acct_cr')
                )
                ->where('fa.ad_table_id', $customerShipmentConfig['journals']['table_id'])
                ->where('fa.record_id', $receipt->m_inout_id)
                ->orderBy('fa.fact_acct_id')
                ->get();

            $filename = 'journal_' . $receipt->documentno . '_' . date('Ymd_His') . '.xls';

            $headers = [
                "Content-Type" => "application/vnd.ms-excel",
                "Content-Disposition" => "attachment; filename=\"$filename\"",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ];

            return response()->stream(function () use ($journals, $receipt) {
                echo "<html><head><meta charset='UTF-8'></head><body>";
                echo "<h3>Journal Entries for Customer Shipment: " . $receipt->documentno . "</h3>";
                echo "<table border='1'>";
                echo "<thead>
                        <tr>
                            <th style='background-color: #f0f0f0; font-weight: bold;'>Account</th>
                            <th style='background-color: #f0f0f0; font-weight: bold;'>Name</th>
                            <th style='background-color: #f0f0f0; font-weight: bold;'>Debit</th>
                            <th style='background-color: #f0f0f0; font-weight: bold;'>Credit</th>
                        </tr>
                      </thead><tbody>";

                $totalDr = 0;
                $totalCr = 0;

                foreach ($journals as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row->account_value) . "</td>";
                    echo "<td>" . htmlspecialchars($row->account_name) . "</td>";
                    echo "<td style='text-align: right;'>" . number_format($row->amt_acct_dr, 2) . "</td>";
                    echo "<td style='text-align: right;'>" . number_format($row->amt_acct_cr, 2) . "</td>";
                    echo "</tr>";
                    $totalDr += $row->amt_acct_dr;
                    $totalCr += $row->amt_acct_cr;
                }

                echo "<tr style='font-weight: bold; background-color: #e0e0e0;'>";
                echo "<td colspan='2' style='text-align: right;'>Total:</td>";
                echo "<td style='text-align: right;'>" . number_format($totalDr, 2) . "</td>";
                echo "<td style='text-align: right;'>" . number_format($totalCr, 2) . "</td>";
                echo "</tr>";
                echo "</tbody></table>";
                echo "</body></html>";
            }, 200, $headers);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Customer Shipment Export Journals Error: " . $e->getMessage());
            abort(500, 'Failed to export journals: ' . $e->getMessage());
        }
    }

    public function toggleTracking(Request $request, $id)
    {
        $validated = $request->validate([
            'field' => 'required|in:isdocumentback,iscustomergr',
            'value' => 'required|in:Y,N'
        ]);

        try {
            $decryptedId = Crypt::decryptString($id);
            $field = strtolower($validated['field']);
            $value = $validated['value'];

            \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_inout')
                ->where('m_inout_id', $decryptedId)
                ->update([$field => $value]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Customer Shipment Tracking Update Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}
