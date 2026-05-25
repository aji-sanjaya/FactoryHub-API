<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Idempiere\COrder;
use App\Models\Idempiere\COrderLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
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
        $purchaseOrderConfig = config('idempiere.create-po');

        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        // Handle Detail View (Form) if document_id is present
        if (request()->has('document_id')) {
            return $this->showForm(request('document_id'));
        }

        // List View Logic
        $perPage = (int) request()->get('per_page', $purchaseOrderConfig['limits']['list_per_page']);
        $page = request()->get('page', 1);
        $status = request()->get('status', 'all');
        $search = request()->get('search', '');

        $clientId = Session::get('idempiere_client');
        $roleId = Session::get('idempiere_role');
        // Note: PO uses AD_Role_OrgAccess or User Access similar to Requisition

        // Base Query using DB Facade for performance/joins if needed, or Eloquent
        $query = COrder::where('ad_client_id', $clientId)
            ->where('issotrx', $purchaseOrderConfig['defaults']['is_so_trx'])
            ->where('isactive', 'Y');

        // Apply Filters (Search, Status, Date) - Simplified for brevity
        if ($status !== 'all') {
            // Map status label to code if needed
            $query->where('docstatus', $status);
        }
        if ($search) {
            $query->where('documentno', 'ilike', "%{$search}%");
        }

        $query->orderBy('created', 'desc');

        $orders = $query->paginate($perPage);

        // Calculate Counts
        $countAll = COrder::where('ad_client_id', $clientId)->where('issotrx', $purchaseOrderConfig['defaults']['is_so_trx'])->where('isactive', 'Y')->count();
        $countDraft = COrder::where('ad_client_id', $clientId)->where('issotrx', $purchaseOrderConfig['defaults']['is_so_trx'])->where('isactive', 'Y')->whereIn('docstatus', $purchaseOrderConfig['statuses']['draft'])->count();
        $countInProgress = COrder::where('ad_client_id', $clientId)->where('issotrx', $purchaseOrderConfig['defaults']['is_so_trx'])->where('isactive', 'Y')->whereIn('docstatus', $purchaseOrderConfig['statuses']['in_progress'])->count();
        $countCompleted = COrder::where('ad_client_id', $clientId)->where('issotrx', $purchaseOrderConfig['defaults']['is_so_trx'])->where('isactive', 'Y')->whereIn('docstatus', $purchaseOrderConfig['statuses']['completed'])->count();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.purchase-order.order-table', ['orders' => $orders])->render(),
            ]);
        }

        return view('pages.purchase-order.index', [
            'title' => 'Purchase Order',
            'orders' => $orders,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted
        ]);
    }

    // Helper for Form View (Show/Create/Edit)
    private function showForm($docId)
    {
        $purchaseOrderConfig = config('idempiere.create-po');

        $order = null;
        if ($docId !== 'new') {
            try {
                $decryptedId = Crypt::decryptString($docId);
                $order = COrder::findOrFail($decryptedId);
            } catch (\Exception $e) {
                return redirect()->route('purchase-order.index')->with('error', 'Invalid Link');
            }
        }

        // Fetch Common Data (Orgs, Warehouses, PriceLists, Vendors)
        $roleId = Session::get('idempiere_role');
        $clientId = Session::get('idempiere_client');

        // --- Robust Client Name Fallback Logic (Session → DB → Config) ---
        $userData = Session::get('user_data');
        $tenantName = config('idempiere.tenant.name');
        $clientName = null;
        if (is_array($userData)) {
            $clientName = trim((string) ($userData['client_name'] ?? '')) ?: null;
        } elseif (is_object($userData)) {
            $clientName = trim((string) ($userData->client_name ?? '')) ?: null;
        }
        if (!$clientName && $clientId) {
            $clientName = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('ad_client')
                ->where('ad_client_id', $clientId)
                ->value('name');
        }
        $clientName = $clientName ?: $tenantName;

        // Reuse Organization Logic (with fallback)
        $organizations = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT o.ad_org_id AS id, o.name AS text
                FROM ad_org o
                JOIN ad_role_orgaccess roa ON roa.ad_org_id = o.ad_org_id
                WHERE o.isactive = 'Y' AND roa.isactive = 'Y' AND roa.ad_role_id = ? AND o.ad_org_id <> 0
                ORDER BY o.name
            ", [$roleId]);

        if (empty($organizations)) {
            $organizations = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT ad_org_id AS id, name AS text
                FROM ad_org
                WHERE ad_client_id = ? AND isactive = 'Y' AND ad_org_id <> 0
                ORDER BY name
            ", [$clientId]);
        }

        // Determine Current Org
        $currentOrgId = $order ? $order->ad_org_id : (count($organizations) > 0 ? $organizations[0]->id : null);

        // Warehouses
        $warehouses = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT DISTINCT w.m_warehouse_id AS id, w.name AS text
                FROM m_warehouse w
                WHERE w.isactive = 'Y' AND w.ad_org_id = ?
                ORDER BY w.name
            ", [$currentOrgId]);

        // Price Lists (Purchase)
        $pricelists = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT pl.m_pricelist_id AS id, pl.name AS text
                FROM m_pricelist pl
                WHERE pl.isactive = 'Y' AND pl.ad_client_id = ? AND pl.issopricelist='N'
                ORDER BY pl.name
            ", [$clientId]);

        // Vendors (C_BPartner where isVendor='Y')
        $vendors = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT c_bpartner_id AS id, name AS text
                FROM c_bpartner
                WHERE isactive = 'Y' AND ad_client_id = ? AND isvendor='Y'
                ORDER BY name LIMIT {$purchaseOrderConfig['limits']['vendor_search']}
            ", [$clientId]);

        // Fetch Users for Checked/Approved By/Contact
        $users = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT DISTINCT u.ad_user_id AS id, u.name AS text
                FROM ad_user u
                JOIN ad_user_roles ur ON ur.ad_user_id = u.ad_user_id
                WHERE u.isactive = 'Y' AND u.ad_client_id = ?
                ORDER BY u.name
            ", [$clientId]);

        // Fetch Priorities
        $priorities = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT rl.value AS id, rl.name AS text
                FROM ad_ref_list rl
                JOIN ad_reference r ON r.ad_reference_id = rl.ad_reference_id
                WHERE r.name = '_PriorityRule' AND rl.isactive = 'Y'
                ORDER BY rl.value
            ", );

        // Fetch Payment Terms
        $paymentTerms = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
                SELECT c_paymentterm_id AS id, name AS text
                FROM c_paymentterm
                WHERE isactive = 'Y' AND ad_client_id = ?
                ORDER BY name
            ", [$clientId]);

        // DocTypes (Target) for PO
        $docTypes = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
            SELECT dt.c_doctype_id AS id, dt.name AS text
            FROM c_doctype dt
            WHERE dt.isactive = 'Y'
            AND dt.docbasetype = ?
            AND dt.issotrx = ?
            AND dt.ad_client_id = ?
            AND dt.ad_org_id IN (0, ?)
            AND dt.docsubtypeso IS NULL
            ORDER BY dt.c_doctype_id DESC
        ", [
            $purchaseOrderConfig['doc_types']['base_type'],
            $purchaseOrderConfig['defaults']['is_so_trx'],
            $clientId,
            $clientId,
        ]);

        // Projects
        $projects = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
            SELECT c_project_id AS id, value || ' - ' || name AS text
            FROM c_project
            WHERE ad_client_id = ? AND isactive='Y' AND issummary='N'
            ORDER BY value
        ", [$clientId]);

        // Taxes List for Dropdown (Purchase Tax only - sopotype 'P' or 'B')
        $taxesList = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
            SELECT c_tax_id AS id, name AS text, rate
            FROM c_tax
            WHERE ad_client_id = ? 
                AND isactive='Y' 
                AND (sopotype = 'P' OR sopotype = 'B')
            ORDER BY name
        ", [$clientId]);

        // Lines
        if ($order) {
            $defaultLinePerPage = $purchaseOrderConfig['limits']['line_default_per_page'];
            $linePerPageOptions = $purchaseOrderConfig['limits']['line_per_page_options'];
            $linePerPage = request()->integer('per_page', $defaultLinePerPage);

            if (!in_array($linePerPage, $linePerPageOptions, true)) {
                $linePerPage = $defaultLinePerPage;
            }

            $lines = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_orderline as ol')
                ->leftJoin('m_product as p', 'ol.m_product_id', '=', 'p.m_product_id')
                ->leftJoin('c_uom as u', 'ol.c_uom_id', '=', 'u.c_uom_id')
                ->leftJoin('m_requisitionline as rl', 'ol.m_requisitionline_id', '=', 'rl.m_requisitionline_id')
                ->leftJoin('m_requisition as r', 'rl.m_requisition_id', '=', 'r.m_requisition_id')
                ->where('ol.c_order_id', $order->c_order_id)
                ->select(
                    'ol.c_orderline_id',
                    'ol.line',
                    'ol.m_product_id',
                    'ol.qtyentered as qty',
                    'ol.priceentered as priceactual',
                    'ol.linenetamt',
                    'ol.description',
                    'ol.m_requisitionline_id',
                    'ol.iswithholding as is_withholding',
                    'ol.withholdingrate as withholding_rate',
                    'ol.withholdingamount as withholding_amount',
                    'p.name as product_name',
                    'p.value as product_code',
                    'u.name as uom_name',
                    'u.uomsymbol as uom_symbol',
                    'r.documentno as requisition_documentno'
                )
                ->orderBy('ol.line')
                ->paginate($linePerPage);
        } else {
            // Return empty paginator for new orders
            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $purchaseOrderConfig['limits']['line_default_per_page']);
        }
        // Taxes
        $taxes = [];
        if ($order) {
            $taxes = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_ordertax as ot')
                ->join('c_tax as t', 'ot.c_tax_id', '=', 't.c_tax_id')
                ->where('ot.c_order_id', $order->c_order_id)
                ->select(
                    't.name as tax_name',
                    'ot.taxamt',
                    'ot.taxbaseamt',
                    'ot.processed' // just to check if computed
                )
                ->get();
        }
        if ($order) {
            $statusLabel = $this->getStatusLabel($order->docstatus);
        } else {
            $statusLabel = $purchaseOrderConfig['defaults']['document_status_label'];
        }

        // Check Active Workflow
        $hasActiveWorkflow = false;
        if ($order) {
            $hasActiveWorkflow = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('ad_wf_activity')
                ->join('ad_table', 'ad_table.ad_table_id', '=', 'ad_wf_activity.ad_table_id')
                ->where('ad_table.tablename', $purchaseOrderConfig['workflow']['table_name'])
                ->where('ad_wf_activity.record_id', $order->c_order_id)
                ->where('ad_wf_activity.processed', 'N')
                ->exists();
        }

        $viewData = [
            'title' => $docId === 'new' ? 'Create Purchase Order' : 'Edit Purchase Order',
            'order' => $order,
            'lines' => $lines,
            'organizations' => $organizations,
            'warehouses' => $warehouses,
            'pricelists' => $pricelists,
            'vendors' => $vendors,
            'users' => $users,
            'priorities' => $priorities,
            'paymentTerms' => $paymentTerms,
            'docTypes' => $docTypes,
            'projects' => $projects,
            'isNew' => is_null($order),
            'docNo' => $order ? $order->documentno : '** New **',
            'status' => $statusLabel,
            'currentOrgId' => $currentOrgId,
            'dateOrdered' => $order && $order->dateordered ? \Illuminate\Support\Carbon::parse($order->dateordered)->format('Y-m-d') : date('Y-m-d'),
            'datePromised' => $order && $order->datepromised ? \Illuminate\Support\Carbon::parse($order->datepromised)->format('Y-m-d') : date('Y-m-d'),
            'docIdParam' => request('document_id'),
            'isReadOnly' => $order && in_array($order->docstatus, $purchaseOrderConfig['statuses']['read_only'], true),
            'isDraft' => $order && $order->docstatus === 'DR',
            'activeTab' => request('tab', 'header'),
            'salesRepId' => $order && $order->salesrep_id ? $order->salesrep_id : ($sessionUserId ?? null),
            'taxes' => $taxes,
            // 'taxes' => $taxes, // Correct via previous line
            'taxesList' => $taxesList,
            'hasActiveWorkflow' => $hasActiveWorkflow,
            'purchaseOrderConfig' => $purchaseOrderConfig,
            'clientName' => $clientName,
        ];

        // Set docTypeId explicitly
        if ($order && isset($order->c_doctypetarget_id)) {
            $viewData['docTypeId'] = $order->c_doctypetarget_id;
        } else {
            $viewData['docTypeId'] = $purchaseOrderConfig['doc_types']['purchase_order'];
        }

        // Debug logging
        \Illuminate\Support\Facades\Log::info('PO Form ViewData', [
            'docId' => $docId,
            'order_is_null' => is_null($order),
            'order_exists' => !is_null($order),
            'order_c_doctypetarget_id' => $order ? ($order->c_doctypetarget_id ?? 'NOT_SET') : 'N/A',
            'final_docTypeId' => $viewData['docTypeId'],
            'isNew' => $viewData['isNew']
        ]);

        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');
            if ($tab === 'header')
                return view('pages.purchase-order.partials.tab-header', array_merge($viewData, ['clientName' => $clientName]));
            if ($tab === 'lines')
                return view('pages.purchase-order.partials.tab-lines', $viewData);
            if ($tab === 'attachments') {
                $attachments = [];
                // Using existing $order object
                if (isset($order)) {
                    try {
                        $url = "models/c_order/{$order->c_order_id}/attachments";

                        $response = $this->idempiereService->get($url);

                        if ($response->successful()) {
                            $json = $response->json();

                            if (isset($json['attachments'])) {
                                $attachments = json_decode(json_encode($json['attachments']), FALSE);
                            } elseif (isset($json['records'])) {
                                $attachments = json_decode(json_encode($json['records']), FALSE);
                            } else {
                                // Fallback: try to see if the root is the array or object
                                // Some versions return { "details": [...] } or just [...]
                                $attachments = is_array($json) ? json_decode(json_encode($json), FALSE) : [];
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to fetch attachments: " . $e->getMessage());
                        // Fallback to empty to allow tab to load
                        $attachments = [];
                    }
                }
                $viewData['attachments'] = $attachments;
                return view('pages.purchase-order.partials.tab-attachments', $viewData);
            }
        }

        return view('pages.purchase-order.form', $viewData);
    }

    // Store Header
    public function store(Request $request)
    {
        $purchaseOrderConfig = config('idempiere.create-po');

        // 1. Validate Form Input
        $validated = $request->validate([
            'org_id' => 'required',
            'warehouse_id' => 'required',
            'c_bpartner_id' => 'required', // Vendor
            'date_ordered' => 'required|date_format:Y-m-d',
            'date_promised' => 'nullable|date_format:Y-m-d',
            'doc_type_id' => 'required',
            'pricelist_id' => 'required',
            'description' => 'nullable|string',
            'priority_rule' => 'nullable|string',
            'c_paymentterm_id' => 'nullable|numeric',
            'adw_ad_user_checked_id' => 'required|numeric',
            'adw_ad_user_approved_id' => 'required|numeric',
            'c_project_id' => 'nullable',
            'c_tax_id' => 'required|numeric',
        ]);

        // Session Context
        $userData = Session::get('user_data');
        $sessionClientId = Session::get('idempiere_client');
        $sessionUserId = null;

        if (is_array($userData)) {
            $sessionUserId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null;
        } elseif (is_object($userData)) {
            $sessionUserId = $userData->userId ?? $userData->id ?? $userData->ad_user_id ?? null;
        }

        if (!$sessionUserId || !$sessionClientId) {
            return response()->json(['message' => 'User Context Not Found. Please re-login.'], 401);
        }

        // Lookup BPartner Details (Location & Contact)
        $bpartnerId = (int) $validated['c_bpartner_id'];
        $bpartnerLocationId = null;
        $adUserId = null;

        // Try to find a default location (BillTo or ShipTo)
        $location = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_bpartner_location')
            ->where('c_bpartner_id', $bpartnerId)
            ->where('isactive', 'Y')
            ->orderBy('isbillto', 'desc') // Prefer BillTo
            ->orderBy('isshipto', 'desc')
            ->first();

        if ($location) {
            $bpartnerLocationId = $location->c_bpartner_location_id;
        }

        // Try to find a contact user
        $contact = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('ad_user')
            ->where('c_bpartner_id', $bpartnerId)
            ->where('isactive', 'Y')
            ->first(); // Just pick first one for now

        if ($contact) {
            $adUserId = $contact->ad_user_id;
        }

        // Payload Construction matching User Request
        $payload = [
            'AD_Client_ID' => (int) $sessionClientId,
            'AD_Org_ID' => (int) $validated['org_id'],
            'M_Warehouse_ID' => (int) $validated['warehouse_id'],
            'C_DocTypeTarget_ID' => (int) $validated['doc_type_id'],
            'M_PriceList_ID' => (int) $validated['pricelist_id'],
            'DateOrdered' => $validated['date_ordered'],
            'Description' => $validated['description'],
            'IsSOTrx' => $purchaseOrderConfig['defaults']['is_so_trx'],

            // Vendors & Locations
            'C_BPartner_ID' => $bpartnerId,
            'C_BPartner_Location_ID' => $bpartnerLocationId,
            'AD_User_ID' => $adUserId, // Partner Contact

            // Bill To (Same as Vendor for now)
            'Bill_BPartner_ID' => $bpartnerId,
            'Bill_Location_ID' => $bpartnerLocationId,
            'Bill_User_ID' => $adUserId,

            // New Defaults Requested
            'DeliveryViaRule' => $purchaseOrderConfig['defaults']['delivery_via_rule'],
            'PriorityRule' => $validated['priority_rule'] ?? $purchaseOrderConfig['defaults']['priority_rule'],
            'C_Currency_ID' => $purchaseOrderConfig['defaults']['currency_id'],
            'PaymentRule' => $purchaseOrderConfig['defaults']['payment_rule'],

            // Company Agent (Sales Rep) - User Request: same as creator
            'SalesRep_ID' => (int) $sessionUserId,

            // System Managed (commented out to avoid errors)
            // 'DocStatus' => 'DR', 
            // 'CreatedBy' => (int) $sessionUserId,
            // 'UpdatedBy' => (int) $sessionUserId,
        ];


        // Custom DPK Fields (USER REQUESTED THESE BE INCLUDED)
        // If these still cause 500 error, the columns are missing in DB.
        if (!empty($validated['adw_ad_user_checked_id']))
            $payload['ADW_AD_User_Checked_ID'] = (int) $validated['adw_ad_user_checked_id'];
        if (!empty($validated['adw_ad_user_approved_id']))
            $payload['ADW_AD_User_Approved_ID'] = (int) $validated['adw_ad_user_approved_id'];

        // C_Tax_ID (Mandatory)
        if (!empty($validated['c_tax_id']))
            $payload['C_Tax_ID'] = (int) $validated['c_tax_id'];

        // C_Project_ID (if added to form later, for now optional)
        if ($request->has('c_project_id')) {
            $payload['C_Project_ID'] = (int) $request->input('c_project_id');
        }

        // Optional Fields
        if (!empty($validated['date_promised']))
            $payload['DatePromised'] = $validated['date_promised'];
        else
            $payload['DatePromised'] = $validated['date_ordered']; // Default to ordered date

        if (!empty($validated['c_paymentterm_id']))
            $payload['C_PaymentTerm_ID'] = (int) $validated['c_paymentterm_id'];

        // Log payload for debugging
        Log::info('PO Create Payload:', $payload);

        try {
            // API POST to c_order
            $response = $this->idempiereService->post('models/c_order', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['C_Order_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'Purchase Order created successfully',
                    'data' => [
                        'c_order_id' => $id,
                        'encrypted_id' => Crypt::encryptString($id)
                    ]
                ]);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('PO Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create PO: ' . $e->getMessage()], 500);
        }
    }

    // Create view route (redirect)
    public function create()
    {
        return redirect()->route('purchase-order.index', ['document_id' => 'new']);
    }
    public function update(Request $request, $id)
    {
        // Validate
        $validated = $request->validate([
            'org_id' => 'nullable',
            'warehouse_id' => 'nullable',
            'c_bpartner_id' => 'nullable',
            'date_ordered' => 'nullable|date_format:Y-m-d',
            'description' => 'nullable|string',
            'pricelist_id' => 'nullable',
            'date_promised' => 'nullable|date_format:Y-m-d',
            'priority_rule' => 'nullable|string',
            'c_paymentterm_id' => 'nullable|numeric',
            'adw_ad_user_checked_id' => 'required|numeric',
            'adw_ad_user_approved_id' => 'required|numeric',
            'doc_type_id' => 'nullable',
            'c_project_id' => 'nullable',
            'c_tax_id' => 'required|numeric',
            'invoicerule' => 'nullable|string',
            'deliveryrule' => 'nullable|string'
        ]);

        $payload = [];
        if (!empty($validated['org_id']))
            $payload['AD_Org_ID'] = (int) $validated['org_id'];
        if (!empty($validated['warehouse_id']))
            $payload['M_Warehouse_ID'] = (int) $validated['warehouse_id'];
        if (!empty($validated['c_bpartner_id']))
            $payload['C_BPartner_ID'] = (int) $validated['c_bpartner_id'];
        if (!empty($validated['date_ordered']))
            $payload['DateOrdered'] = $validated['date_ordered'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        if (!empty($validated['pricelist_id']))
            $payload['M_PriceList_ID'] = (int) $validated['pricelist_id'];

        if (!empty($validated['date_promised']))
            $payload['DatePromised'] = $validated['date_promised'];
        if (!empty($validated['priority_rule']))
            $payload['PriorityRule'] = $validated['priority_rule'];
        if (!empty($validated['c_paymentterm_id']))
            $payload['C_PaymentTerm_ID'] = (int) $validated['c_paymentterm_id'];

        // Custom DPK Fields (Uncommented as requested)
        if (!empty($validated['adw_ad_user_checked_id']))
            $payload['ADW_AD_User_Checked_ID'] = (int) $validated['adw_ad_user_checked_id'];
        if (!empty($validated['adw_ad_user_approved_id']))
            $payload['ADW_AD_User_Approved_ID'] = (int) $validated['adw_ad_user_approved_id'];

        if (!empty($validated['doc_type_id']))
            $payload['C_DocTypeTarget_ID'] = (int) $validated['doc_type_id'];

        if (!empty($validated['c_project_id']))
            $payload['C_Project_ID'] = (int) $validated['c_project_id'];

        // C_Tax_ID (Mandatory)
        if (!empty($validated['c_tax_id']))
            $payload['C_Tax_ID'] = (int) $validated['c_tax_id'];

        try {
            $response = $this->idempiereService->put("models/c_order/{$id}", $payload);
            if ($response->successful()) {
                return response()->json(['message' => 'PO updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('PO Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update PO: ' . $e->getMessage()], 500);
        }
    }

    // API: Get Warehouses
    public function getWarehouses(Request $request)
    {
        $orgId = $request->get('org_id');
        if (!$orgId)
            return response()->json([]);

        $warehouses = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
            SELECT w.m_warehouse_id AS id, w.name AS text
            FROM m_warehouse w
            WHERE w.isactive = 'Y' AND w.ad_org_id = ?
            ORDER BY w.name
        ", [$orgId]);

        return response()->json($warehouses);
    }

    // API: Get Products
    public function getProducts(Request $request)
    {
        $purchaseOrderConfig = config('idempiere.create-po');
        // Reuse similar logic to Requisition or extract to Service. 
        // For now, simple implementation directly querying DB.
        $search = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $purchaseOrderConfig['limits']['products_per_page'];
        $clientId = Session::get('idempiere_client');

        if (!$clientId)
            return response()->json(['results' => [], 'pagination' => ['more' => false]]);

        $query = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('m_product as p')
            ->leftJoin('c_uom as u', 'p.c_uom_id', '=', 'u.c_uom_id')
            ->select('p.m_product_id as id', \Illuminate\Support\Facades\DB::raw("p.value || ' - ' || p.name as text"), 'u.name as uom_name')
            ->where('p.isactive', 'Y')->where('p.issummary', 'N')->where('p.ad_client_id', $clientId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'ilike', "%{$search}%")->orWhere('p.value', 'ilike', "%{$search}%");
            });
        }

        $offset = ($page - 1) * $perPage;
        $products = $query->offset($offset)->limit($perPage + 1)->orderBy('p.name')->get();
        $hasMore = count($products) > $perPage;
        if ($hasMore)
            $products = $products->slice(0, $perPage)->values();

        return response()->json(['results' => $products, 'pagination' => ['more' => $hasMore]]);
    }

    // API: Get Product Price
    public function getProductPrice(Request $request)
    {
        $productId = $request->get('product_id');
        $priceListId = $request->get('pricelist_id');
        if (!$productId || !$priceListId)
            return response()->json(['price' => 0]);

        // Determine PL Version (Valid From date logic is complex, pick latest active)
        $price = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_productprice as pp')
            ->join('m_pricelist_version as plv', 'pp.m_pricelist_version_id', '=', 'plv.m_pricelist_version_id')
            ->where('pp.m_product_id', $productId)
            ->where('plv.m_pricelist_id', $priceListId)
            ->where('plv.isactive', 'Y')
            ->orderBy('plv.validfrom', 'desc')
            ->value('pp.pricestd'); // Use Standard Price

        return response()->json(['price' => $price ?: 0]);
    }

    // API: Get Requisition Lines
    public function getRequisitionLines(Request $request)
    {
        $purchaseOrderConfig = config('idempiere.create-po');
        $clientId = Session::get('idempiere_client');
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = $purchaseOrderConfig['limits']['requisition_modal'];

        if (strlen($search) < $purchaseOrderConfig['limits']['requisition_min_search_length']) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
                'total' => 0,
                'per_page' => $perPage,
            ]);
        }

        $query = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('m_requisitionline as rl')
            ->join('m_requisition as r', 'r.m_requisition_id', '=', 'rl.m_requisition_id')
            ->join('m_product as p', 'p.m_product_id', '=', 'rl.m_product_id')
            ->where('r.docstatus', 'CO') // Completed Requisitions
            ->where('rl.ad_client_id', $clientId)
            // Filter out lines already fully ordered
            ->whereRaw('rl.qty - COALESCE((SELECT SUM(ol.qtyentered) FROM c_orderline ol JOIN c_order o ON o.c_order_id = ol.c_order_id WHERE o.docstatus NOT IN (\'VO\', \'RE\') AND ol.m_requisitionline_id = rl.m_requisitionline_id), 0) > 0')
            ->select(
                'rl.m_requisitionline_id as id',
                'r.documentno as po_number', // Using PO Number as requested, but it's Requisition DocumentNo
                'p.name as product_name',
                'p.value as product_value',
                'rl.qty as qty',
                \Illuminate\Support\Facades\DB::raw('(rl.qty - COALESCE((SELECT SUM(ol.qtyentered) FROM c_orderline ol JOIN c_order o ON o.c_order_id = ol.c_order_id WHERE o.docstatus NOT IN (\'VO\', \'RE\') AND ol.m_requisitionline_id = rl.m_requisitionline_id), 0)) as remaining_qty'),
                'rl.m_product_id'
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('r.documentno', 'ilike', "%{$search}%")
                    ->orWhere('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });
        }

        $results = $query->orderBy('r.documentno', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $results->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->po_number . ' - ' . $item->product_value . ' - ' . $item->product_name . ' (Qty: ' . $item->qty . ', Rem: ' . $item->remaining_qty . ')',
                    'po_number' => $item->po_number,
                    'product_name' => $item->product_name,
                    'product_value' => $item->product_value,
                    'm_product_id' => $item->m_product_id,
                    'qty' => $item->qty,
                    'remaining_qty' => $item->remaining_qty,
                ];
            }),
            'pagination' => ['more' => $results->hasMorePages()],
            'total' => $results->total(),
            'per_page' => $results->perPage(),
        ]);
    }

    // Line: Store (Create/Update)
    public function storeLine(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required', // Encrypted ID
            'line_id' => 'nullable', // If update
            'm_product_id' => 'required',
            'qty' => 'required|numeric',
            'price' => 'required|numeric|gt:0',
            'c_tax_id' => 'required|numeric', // Add validation
            'description' => 'nullable|string',
            'm_requisitionline_id' => 'nullable|numeric', // New Field
            'is_withholding'   => 'nullable',
            'withholding_rate' => 'nullable|numeric|min:0',
        ]);

        try {
            $orderId = Crypt::decryptString($validated['document_id']);

            // Context
            $userData = Session::get('user_data');
            $sessionClientId = Session::get('idempiere_client');
            $sessionUserId = is_array($userData) ? ($userData['userId'] ?? 0) : ($userData->userId ?? 0);

            // Calculate Line Net
            $qty = (float) $validated['qty'];
            $price = (float) $validated['price'];
            $lineNet = $qty * $price;

            // Withholding Tax (PPh23)
            $isWithholding   = $request->boolean('is_withholding');
            $withholdingRate = (float) ($validated['withholding_rate'] ?? 0);
            $withholdingAmt  = $isWithholding ? round($lineNet * $withholdingRate / 100, 2) : 0;

            // ── Requisition Qty Validation ──────────────────────────────────────
            // Only validate when the line is linked to a requisition line.
            if (!empty($validated['m_requisitionline_id'])) {
                $reqLineId = (int) $validated['m_requisitionline_id'];

                // 1. Get the original qty from the requisition line
                $reqLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_requisitionline')
                    ->where('m_requisitionline_id', $reqLineId)
                    ->select('qty')
                    ->first();

                if (!$reqLine) {
                    return response()->json([
                        'message' => 'Requisition line not found.'
                    ], 422);
                }

                $reqQty = (float) $reqLine->qty;

                // 2. Sum all existing order lines that reference the same requisition line
                //    Exclude the current line being updated (if any).
                $usedQtyQuery = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_orderline')
                    ->where('m_requisitionline_id', $reqLineId);

                if (!empty($validated['line_id'])) {
                    // Editing an existing line — exclude itself from the sum
                    $usedQtyQuery->where('c_orderline_id', '!=', (int) $validated['line_id']);
                }

                $usedQty = (float) ($usedQtyQuery->sum('qtyentered') ?? 0);

                // 3. Check if new qty would exceed the requisition qty
                $totalAfterSave = $usedQty + $qty;
                if ($totalAfterSave > $reqQty) {
                    $remaining = $reqQty - $usedQty;
                    return response()->json([
                        'message' => "Qty exceeds requisition limit. "
                            . "Requisition Qty: {$reqQty}, "
                            . "Already ordered: {$usedQty}, "
                            . "Remaining available: " . max(0, $remaining) . "."
                    ], 422);
                }
            }
            // ────────────────────────────────────────────────────────────────────

            // Base Payload (Fields common to Create and Update)
            $payload = [
                'M_Product_ID' => (int) $validated['m_product_id'],
                'QtyOrdered' => $qty,
                'QtyEntered' => $qty,
                'PriceEntered' => $price,
                'PriceList' => $price,
                'PriceLimit' => $price,
                'Discount' => 0.0,
                'Description' => $validated['description'] ?? null,
                'C_Tax_ID' => (int) $validated['c_tax_id'],
                'IsWithholding'     => $isWithholding ? 'Y' : 'N',
                'WithholdingRate'   => $withholdingRate,
                'WithholdingAmount' => $withholdingAmt,
            ];

            if (!empty($validated['m_requisitionline_id'])) {
                $payload['M_RequisitionLine_ID'] = (int) $validated['m_requisitionline_id'];
            }

            if (!empty($validated['line_id'])) {
                // Update
                $lineId = $validated['line_id'];

                $receivedQty = (float) \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_inoutline as iol')
                    ->join('m_inout as io', 'io.m_inout_id', '=', 'iol.m_inout_id')
                    ->where('iol.c_orderline_id', (int) $lineId)
                    ->whereNotIn('io.docstatus', ['VO', 'RE'])
                    ->sum('iol.movementqty');

                if ($qty < $receivedQty) {
                    return response()->json([
                        'message' => 'Qty PO line tidak boleh lebih kecil dari total Qty Receipt. '
                            . 'Qty Receipt saat ini: '
                            . rtrim(rtrim(number_format($receivedQty, 2, '.', ''), '0'), '.')
                            . '.'
                    ], 422);
                }

                $response = $this->idempiereService->put("models/c_orderline/{$lineId}", $payload);
                $action = 'updated';
            } else {
                // Create
                $payload['AD_Client_ID'] = (int) $sessionClientId;
                $payload['C_Order_ID'] = (int) $orderId;
                $payload['LineNetAmt'] = $lineNet;
                $response = $this->idempiereService->post("models/c_orderline", $payload);
                $action = 'created';
            }

            if ($response->successful()) {
                // Sum withholdingamount from all lines and update c_order header
                $totalWithholding = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_orderline')
                    ->where('c_order_id', $orderId)
                    ->sum('withholdingamount');

                \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_order')
                    ->where('c_order_id', $orderId)
                    ->update(['withholdingamount' => $totalWithholding ?? 0]);

                // Fetch updated order totals
                $order = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_order')
                    ->where('c_order_id', $orderId)
                    ->select('grandtotal', 'totallines', 'withholdingamount')
                    ->first();

                return response()->json([
                    'message' => "Line $action successfully",
                    'total_lines' => number_format($order->totallines ?? 0, 2),
                    'grandtotal' => number_format($order->grandtotal ?? 0, 2),
                    'tax_amount' => number_format(($order->grandtotal ?? 0) - ($order->totallines ?? 0), 2),
                    'withholding_total' => number_format($order->withholdingamount ?? 0, 2),
                    'grand_total_net' => number_format(($order->grandtotal ?? 0) - ($order->withholdingamount ?? 0), 2),
                ]);
            } else {
                Log::error("PO Line Error ($action): " . $response->body());
                return response()->json(['message' => "Failed to $action line: " . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('PO Line Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // Line: Delete
    public function destroyLine(Request $request)
    {
        $validated = $request->validate(['line_ids' => 'required|array']);

        try {
            $orderId = null;
            foreach ($validated['line_ids'] as $lineId) {
                $lineId = (int) $lineId; // Ensure integer

                $linkedReceiptExists = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_inoutline as iol')
                    ->join('m_inout as io', 'io.m_inout_id', '=', 'iol.m_inout_id')
                    ->where('iol.c_orderline_id', $lineId)
                    ->whereNotIn('io.docstatus', ['VO', 'RE'])
                    ->exists();

                if ($linkedReceiptExists) {
                    return response()->json([
                        'message' => 'Cannot delete PO line because it is already linked to Material Receipt lines.'
                    ], 422);
                }

                // Fetch order_id from first line for totals later
                if (!$orderId) {
                    $line = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('c_orderline')
                        ->where('c_orderline_id', $lineId)
                        ->select('c_order_id')
                        ->first();
                    $orderId = $line->c_order_id ?? null;
                }

                // Call iDempiere API and CHECK the response
                $response = $this->idempiereService->delete("models/c_orderline/{$lineId}");

                if (!$response->successful()) {
                    $errorBody = $response->json('detail') ?? $response->body();
                    Log::error("PO Line Delete Failed (line_id={$lineId}): " . $response->body());
                    return response()->json([
                        'message' => "Failed to delete line #{$lineId}: {$errorBody}"
                    ], $response->status() ?: 500);
                }
            }

            // Fetch updated totals
            $updatedTotals = [];
            if ($orderId) {
                $order = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_order')
                    ->where('c_order_id', $orderId)
                    ->select('grandtotal', 'totallines')
                    ->first();
                $updatedTotals['grandtotal'] = number_format($order->grandtotal ?? 0, 2);
                $updatedTotals['tax_amount'] = number_format(($order->grandtotal ?? 0) - ($order->totallines ?? 0), 2);
            }

            return response()->json(array_merge(['message' => 'Line(s) deleted successfully'], $updatedTotals));

        } catch (\Exception $e) {
            Log::error('PO Line Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    public function process(Request $request)
    {
        $purchaseOrderConfig = config('idempiere.create-po');
        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:' . implode(',', $purchaseOrderConfig['workflow']['allowed_actions']),
        ]);

        try {
            $orderId = Crypt::decryptString($validated['document_id']); 
            $payload = [
                'doc-action' => $validated['doc_action']
            ]; 
            \Illuminate\Support\Facades\Log::info('Processing Purchase Order', [
                'order_id' => $orderId,
                'action' => $validated['doc_action']
            ]);

            // Use IdempiereService
            $response = $this->idempiereService->put("models/c_order/{$orderId}", $payload);

            if ($response->successful()) {  
                if ($validated['doc_action'] === 'CO') {
                    // Manual DB update as requested to ensure custom columns are cleared
                    \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('c_order')
                        ->where('c_order_id', $orderId)
                        ->update([
                            'adw_checked_date' => null,
                            'adw_approved_date' => null,
                            'adw_checked_isapproved' => null,
                            'adw_approve_isapproved' => null,
                        ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Document processed successfully',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Process Failed: ' . $response->body()
                ], 400);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PO Process Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
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

            // Use IdempiereService to post attachment
            // Note: Service needs to support Multipart
            $response = $this->idempiereService->uploadFile(
                "models/c_order/{$docId}/attachments",
                $file,
                $file->getClientOriginalName()
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
            Log::error('View Attachment Error: ' . $e->getMessage());
            abort(500, 'Error viewing attachment');
        }
    }

    // Delete Purchase Order (Header)
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
        ]);

        try {
            $orderId = Crypt::decryptString($validated['document_id']);

            // Only allow deletion of Draft documents
            $order = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_order')
                ->where('c_order_id', $orderId)
                ->select('docstatus', 'documentno')
                ->first();

            if (!$order) {
                return response()->json(['message' => 'Purchase Order not found.'], 404);
            }

            if ($order->docstatus !== 'DR') {
                return response()->json([
                    'message' => 'Only Draft purchase orders can be deleted.'
                ], 422);
            }

            $response = $this->idempiereService->delete("models/c_order/{$orderId}");

            if (!$response->successful()) {
                $errorBody = $response->json('detail') ?? $response->body();
                Log::error("PO Delete Failed (order_id={$orderId}): " . $response->body());
                return response()->json([
                    'message' => 'Failed to delete purchase order: ' . $errorBody
                ], $response->status() ?: 500);
            }

            return response()->json([
                'message' => 'Purchase Order ' . $order->documentno . ' deleted successfully.'
            ]);

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID.'], 400);
        } catch (\Exception $e) {
            Log::error('PO Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    public function print($id)
    {
        try {
            $decryptedId = Crypt::decryptString($id);
            $order = COrder::with('lines')->find($decryptedId);

            if (!$order)
                abort(404);

            // Fetch Vendor with location and contact info
            $vendor = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_bpartner as bp')
                ->leftJoin('c_bpartner_location as bpl', function ($join) use ($order) {
                    $join->on('bpl.c_bpartner_id', '=', 'bp.c_bpartner_id')
                        ->where('bpl.c_bpartner_location_id', '=', $order->c_bpartner_location_id);
                })
                ->leftJoin('c_location as loc', 'loc.c_location_id', '=', 'bpl.c_location_id')
                ->leftJoin('ad_user as u', function ($join) use ($order) {
                    $join->on('u.c_bpartner_id', '=', 'bp.c_bpartner_id')
                        ->where('u.ad_user_id', '=', $order->ad_user_id ?? 0);
                })
                ->where('bp.c_bpartner_id', $order->c_bpartner_id)
                ->select(
                    'bp.c_bpartner_id',
                    'bp.name as vendor_name',
                    'bp.taxid',
                    \Illuminate\Support\Facades\DB::raw("COALESCE(loc.address1, '') as address1"),
                    \Illuminate\Support\Facades\DB::raw("COALESCE(loc.address2, '') as address2"),
                    \Illuminate\Support\Facades\DB::raw("COALESCE(loc.city, '') as city"),
                    \Illuminate\Support\Facades\DB::raw("COALESCE(u.name, '') as contact_name"),
                    \Illuminate\Support\Facades\DB::raw("COALESCE(u.phone, '') as phone")
                )
                ->first();

            // Fetch Client Name
            $clientName = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('ad_client')
                ->where('ad_client_id', $order->ad_client_id)
                ->value('name');

            // Fetch Org address via c_bpartner → c_bpartner_location → c_location
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
                    ->select('bp.taxid', 'locbp.address1', 'locbp.address2', 'locbp.address3')
                    ->first();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('PO Org info fetch warning: ' . $e->getMessage());
            }

            // Fetch Signers Names from Custom Columns
            $checkedBy = $order->adw_ad_user_checked_id ?
                \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')->where('ad_user_id', $order->adw_ad_user_checked_id)->value('description') : null;

            $approvedBy = $order->adw_ad_user_approved_id ?
                \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')->where('ad_user_id', $order->adw_ad_user_approved_id)->value('description') : null;

            // Creator as Prepared By
            $preparedBy = \Illuminate\Support\Facades\DB::connection('idempiere')->table('ad_user')->where('ad_user_id', $order->createdby)->value('description');
            $preparedDate = date('d M Y H:i', strtotime($order->created));
            $preparedQr = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode("Prepared by " . $preparedBy . " on " . $order->created);

            // Checked By — status logic
            $checkedQr = null;
            $checkedDate = 'Pending'; // Default when not yet actioned
            if ($checkedBy) {
                if ($order->adw_checked_isapproved == 'AP' && $order->adw_checked_date) {
                    $checkedDate = date('d M Y H:i', strtotime($order->adw_checked_date));
                    $checkedQr = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode("Checked by " . $checkedBy . " on " . $order->adw_checked_date);
                } elseif ($order->adw_checked_isapproved == 'RE') {
                    $checkedDate = 'Rejected';
                }
            }

            // Approved By — status logic
            $approvedQr = null;
            $approvedDate = 'Pending'; // Default when not yet actioned
            if ($approvedBy) {
                if ($order->adw_approve_isapproved == 'AP' && $order->adw_approved_date) {
                    $approvedDate = date('d M Y H:i', strtotime($order->adw_approved_date));
                    $approvedQr = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode("Approved by " . $approvedBy . " on " . $order->adw_approved_date);
                } elseif ($order->adw_approve_isapproved == 'RE') {
                    $approvedDate = 'Rejected';
                }
            }

            // Fetch Logo from iDempiere (same as Requisition print)
            $logoBase64 = null;
            try {
                $clientInfo = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('ad_clientinfo')
                    ->where('ad_client_id', $order->ad_client_id)
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
                Log::warning('PO Logo fetch warning: ' . $e->getMessage());
            }

            // Fallback to local logo if iDempiere logo not available
            if (!$logoBase64) {
                $logoPath = public_path('assets/media/logos/logo-long.png');
                if (file_exists($logoPath)) {
                    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($logoPath));
                }
            }

            // Payment Term
            $paymentTerm = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_paymentterm')
                ->where('c_paymentterm_id', $order->c_paymentterm_id)
                ->value('name');

            // Lines with UOM
            $lines = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_orderline as ol')
                ->leftJoin('m_product as p', 'ol.m_product_id', '=', 'p.m_product_id')
                ->leftJoin('c_uom as u', 'ol.c_uom_id', '=', 'u.c_uom_id')
                ->where('ol.c_order_id', $order->c_order_id)
                ->select(
                    'ol.*',
                    'p.value as product_value',
                    'p.name as product_name',
                    'u.uomsymbol'
                )
                ->orderBy('ol.line')
                ->get();

            // Fetch Tax Amount from c_ordertax
            $taxAmount = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_ordertax as ot')
                ->where('ot.c_order_id', $order->c_order_id)
                ->sum('ot.taxamt');

            // Fetch Tax Name (optional, for display)
            $taxName = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_ordertax as ot')
                ->join('c_tax as t', 'ot.c_tax_id', '=', 't.c_tax_id')
                ->where('ot.c_order_id', $order->c_order_id)
                ->value('t.name');

                $taxRate = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_ordertax as ot')
                    ->join('c_tax as t', 'ot.c_tax_id', '=', 't.c_tax_id')
                    ->where('ot.c_order_id', $order->c_order_id)
                    ->value('t.rate');

            $pdf = Pdf::loadView('pages.purchase-order.pdf', [
                'order' => $order,
                'lines' => $lines,
                'vendor' => $vendor,
                'orgInfo' => $orgInfo,
                'clientName' => $clientName,
                'paymentTerm' => $paymentTerm,
                'preparedBy' => $preparedBy,
                'checkedBy' => $checkedBy,
                'approvedBy' => $approvedBy,
                'preparedQr' => $preparedQr,
                'checkedQr' => $checkedQr,
                'approvedQr' => $approvedQr,
                'preparedDate' => $preparedDate,
                'checkedDate' => $checkedDate,
                'approvedDate' => $approvedDate,
                'logoBase64' => $logoBase64,
                'taxAmount' => $taxAmount ?? 0,
                'taxName' => $taxName ?? 'PPN',
                'taxRate' => $taxRate ?? 0,
                'withholdingTotal' => $order->withholdingamount ?? 0,
            ])->setOptions(['isRemoteEnabled' => true]);

            $filename = 'PO-' . str_replace(['/', '\\'], '-', $order->documentno) . '.pdf';
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('Print Error: ' . $e->getMessage());
            abort(500, 'Error generating PDF');
        }
    }

    public function priceHistory($id)
    {
        try {
            $decryptedId = Crypt::decryptString($id);
            $order = COrder::find($decryptedId);

            if (!$order) {
                abort(404);
            }

            $poDate = Carbon::parse($order->dateordered);
            
            // Calculate the 3-month range: month of PO and 2 preceding months
            $m1 = $poDate->copy()->subMonths(2);
            $m2 = $poDate->copy()->subMonths(1);
            $m3 = $poDate;

            $startDateTime = $m1->copy()->startOfMonth()->toDateTimeString();
            $endDateTime = $m3->copy()->endOfMonth()->toDateTimeString();

            // Month ranges for bucket classification
            $m1Start = $m1->copy()->startOfMonth();
            $m1End   = $m1->copy()->endOfMonth();
            $m2Start = $m2->copy()->startOfMonth();
            $m2End   = $m2->copy()->endOfMonth();
            $m3Start = $m3->copy()->startOfMonth();
            $m3End   = $m3->copy()->endOfMonth();

            // Fetch products in this PO
            $products = DB::connection('idempiere')
                ->table('c_orderline as ol')
                ->join('m_product as p', 'ol.m_product_id', '=', 'p.m_product_id')
                ->where('ol.c_order_id', $decryptedId)
                ->select('p.m_product_id', 'p.value as product_value', 'p.name as product_name')
                ->distinct()
                ->orderBy('p.value')
                ->get();

            $reportData = [];

            foreach ($products as $product) {
                // 1. Qty Finish Good
                $qtyFinishGood = DB::connection('idempiere')
                    ->table('m_storageonhand as soh')
                    ->join('m_locator as l', 'l.m_locator_id', '=', 'soh.m_locator_id')
                    ->where('l.m_warehouse_id', 1000000)
                    ->where('soh.m_product_id', $product->m_product_id)
                    ->sum('soh.qtyonhand') ?? 0;

                // 2. Query PO lines for this product in 3 months
                $poLines = DB::connection('idempiere')
                    ->table('c_orderline as ol')
                    ->join('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
                    ->join('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'o.c_bpartner_id')
                    ->leftJoin('c_paymentterm as pt', 'pt.c_paymentterm_id', '=', 'o.c_paymentterm_id')
                    ->where('ol.m_product_id', $product->m_product_id)
                    ->where('o.issotrx', 'N')
                    ->whereNotIn('o.docstatus', ['VO', 'RE'])
                    ->whereBetween('o.dateordered', [$startDateTime, $endDateTime])
                    ->select(
                        'o.dateordered',
                        'o.c_bpartner_id',
                        'bp.name as vendor_name',
                        'ol.qtyentered',
                        'ol.priceentered',
                        'pt.netdays',
                        'ol.c_orderline_id'
                    )
                    ->get();

                // 3. Receipt Quantities
                $poLineIds = $poLines->pluck('c_orderline_id')->toArray();
                $receiptQtyMap = [];
                if (!empty($poLineIds)) {
                    $receiptQtyMap = DB::connection('idempiere')
                        ->table('m_inoutline as iol')
                        ->join('m_inout as io', 'io.m_inout_id', '=', 'iol.m_inout_id')
                        ->whereIn('iol.c_orderline_id', $poLineIds)
                        ->whereNotIn('io.docstatus', ['VO', 'RE'])
                        ->select('iol.c_orderline_id', DB::raw('SUM(iol.movementqty) as total_received'))
                        ->groupBy('iol.c_orderline_id')
                        ->get()
                        ->pluck('total_received', 'c_orderline_id')
                        ->toArray();
                }

                // 4. Group by Vendor and Month bucket
                $vendorData = [];

                foreach ($poLines as $line) {
                    $vendorId = $line->c_bpartner_id;
                    if (!isset($vendorData[$vendorId])) {
                        $vendorData[$vendorId] = [
                            'vendor_name' => $line->vendor_name,
                            'buckets' => [
                                1 => ['prices' => [], 'qty_po' => 0, 'qty_rr' => 0, 'top' => []],
                                2 => ['prices' => [], 'qty_po' => 0, 'qty_rr' => 0, 'top' => []],
                                3 => ['prices' => [], 'qty_po' => 0, 'qty_rr' => 0, 'top' => []],
                            ]
                        ];
                    }

                    $orderDate = Carbon::parse($line->dateordered);
                    $bucket = null;
                    if ($orderDate->between($m1Start, $m1End)) {
                        $bucket = 1;
                    } elseif ($orderDate->between($m2Start, $m2End)) {
                        $bucket = 2;
                    } elseif ($orderDate->between($m3Start, $m3End)) {
                        $bucket = 3;
                    }

                    if ($bucket) {
                        $vendorData[$vendorId]['buckets'][$bucket]['prices'][] = (float) $line->priceentered;
                        $vendorData[$vendorId]['buckets'][$bucket]['qty_po'] += (float) $line->qtyentered;
                        $vendorData[$vendorId]['buckets'][$bucket]['qty_rr'] += (float) ($receiptQtyMap[$line->c_orderline_id] ?? 0);
                        if ($line->netdays !== null) {
                            $vendorData[$vendorId]['buckets'][$bucket]['top'][] = (int) $line->netdays;
                        }
                    }
                }

                // Compute averages and check non-empty states
                $processedVendors = [];
                foreach ($vendorData as $vendorId => $vInfo) {
                    $buckets = [];
                    $hasAnyData = false;
                    
                    // Month Averages arrays for row Average calculation
                    $rowPrices = [];
                    $rowQtyPOs = [];
                    $rowQtyRRs = [];
                    $rowTOPs = [];

                    for ($b = 1; $b <= 3; $b++) {
                        $bData = $vInfo['buckets'][$b];
                        if (!empty($bData['prices'])) {
                            $hasAnyData = true;
                            $avgPrice = array_sum($bData['prices']) / count($bData['prices']);
                            $avgTop = !empty($bData['top']) ? array_sum($bData['top']) / count($bData['top']) : null;
                            
                            $buckets[$b] = [
                                'price' => $avgPrice,
                                'qty_po' => $bData['qty_po'],
                                'qty_rr' => $bData['qty_rr'],
                                'top' => $avgTop,
                                'empty' => false
                            ];

                            $rowPrices[] = $avgPrice;
                            $rowQtyPOs[] = $bData['qty_po'];
                            $rowQtyRRs[] = $bData['qty_rr'];
                            if ($avgTop !== null) {
                                $rowTOPs[] = $avgTop;
                            }
                        } else {
                            $buckets[$b] = [
                                'price' => null,
                                'qty_po' => null,
                                'qty_rr' => null,
                                'top' => null,
                                'empty' => true
                            ];
                        }
                    }

                    if ($hasAnyData) {
                        $processedVendors[$vendorId] = [
                            'vendor_name' => $vInfo['vendor_name'],
                            'buckets' => $buckets,
                            'row_average' => [
                                'price' => !empty($rowPrices) ? array_sum($rowPrices) / count($rowPrices) : null,
                                'qty_po' => !empty($rowQtyPOs) ? array_sum($rowQtyPOs) / count($rowQtyPOs) : null,
                                'qty_rr' => !empty($rowQtyRRs) ? array_sum($rowQtyRRs) / count($rowQtyRRs) : null,
                                'top' => !empty($rowTOPs) ? array_sum($rowTOPs) / count($rowTOPs) : null,
                            ]
                        ];
                    }
                }

                // Compute Month-wise Averages (Price IDR Average footer)
                $monthAverages = [
                    1 => null,
                    2 => null,
                    3 => null,
                    'grand_average' => null
                ];

                $allPrices = [];
                for ($b = 1; $b <= 3; $b++) {
                    $bPrices = [];
                    foreach ($processedVendors as $vId => $vData) {
                        if (!$vData['buckets'][$b]['empty']) {
                            $bPrices[] = $vData['buckets'][$b]['price'];
                            $allPrices[] = $vData['buckets'][$b]['price'];
                        }
                    }
                    if (!empty($bPrices)) {
                        $monthAverages[$b] = array_sum($bPrices) / count($bPrices);
                    }
                }
                if (!empty($allPrices)) {
                    $monthAverages['grand_average'] = array_sum($allPrices) / count($allPrices);
                }

                $reportData[] = [
                    'product_id' => $product->m_product_id,
                    'product_value' => $product->product_value,
                    'product_name' => $product->product_name,
                    'qty_finish_good' => $qtyFinishGood,
                    'vendors' => $processedVendors,
                    'month_averages' => $monthAverages
                ];
            }

            // Client Name & User Name
            $clientName = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('ad_client')
                ->where('ad_client_id', $order->ad_client_id)
                ->value('name') ?? '';

            $userData = Session::get('user_data');
            $userId = is_array($userData) ? ($userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null) : ($userData->userId ?? $userData->id ?? $userData->ad_user_id ?? null);
            $printedBy = 'ADempiere';
            if ($userId) {
                $printedBy = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('ad_user')
                    ->where('ad_user_id', $userId)
                    ->value('name') ?? 'ADempiere';
            }

            $pdf = Pdf::loadView('pages.purchase-order.price-history-pdf', [
                'order' => $order,
                'clientName' => $clientName,
                'printedBy' => $printedBy,
                'm1Name' => $m1->format('M'),
                'm2Name' => $m2->format('M'),
                'm3Name' => $m3->format('M'),
                'y1' => $m1->format('Y'),
                'y2' => $m2->format('Y'),
                'y3' => $m3->format('Y'),
                'reportData' => $reportData,
                'printedDate' => Carbon::now()->format('D, d-M-Y H:i:s A'),
            ])->setOptions(['isRemoteEnabled' => true]);

            $filename = 'PriceHistory-' . str_replace(['/', '\\'], '-', $order->documentno) . '.pdf';

            if (request()->has('download')) {
                return $pdf->download($filename);
            }

            return $pdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('Price History PDF Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            abort(500, 'Error generating PDF: ' . $e->getMessage());
        }
    }

    private function getStatusLabel(?string $status): string
    {
        $statusLabels = config('idempiere.create-po.statuses.labels', []);

        return $statusLabels[$status] ?? ($status ?? 'Unknown');
    }
}
