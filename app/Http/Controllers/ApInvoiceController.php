<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use App\Models\Idempiere\CInvoice;
use App\Models\Idempiere\CInvoiceLine;
use Barryvdh\DomPDF\Facade\Pdf;

class ApInvoiceController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    public function index()
    {
        $apInvoiceConfig = config('idempiere.ap-invoice');

        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        if (request()->has('document_id')) {
            return $this->showForm(request('document_id'));
        }

        $perPage = (int) request()->get('per_page', $apInvoiceConfig['limits']['list_per_page']);
        $status = request()->get('status', 'all');
        $search = request()->get('search', '');

        $clientId = Session::get('idempiere_client');

        $query = CInvoice::where('c_invoice.ad_client_id', $clientId)
            ->where('c_invoice.issotrx', $apInvoiceConfig['defaults']['is_so_trx'])
            ->where('c_invoice.isactive', 'Y');

        // Filter to AP Invoice doc base type (API) via join
        $query->join('c_doctype as dt', 'dt.c_doctype_id', '=', 'c_invoice.c_doctype_id')
            ->where('dt.docbasetype', $apInvoiceConfig['doc_types']['base_type'])
            ->select('c_invoice.*');

        if ($status !== 'all') {
            $query->where('c_invoice.docstatus', $status);
        }
        if ($search) {
            $query->where('c_invoice.documentno', 'ilike', "%{$search}%");
        }

        $query->orderBy('c_invoice.created', 'desc');
        $invoices = $query->paginate($perPage);

        $baseQuery = CInvoice::where('c_invoice.ad_client_id', $clientId)
            ->where('c_invoice.issotrx', $apInvoiceConfig['defaults']['is_so_trx'])
            ->where('c_invoice.isactive', 'Y')
            ->join('c_doctype as dt', 'dt.c_doctype_id', '=', 'c_invoice.c_doctype_id')
            ->where('dt.docbasetype', $apInvoiceConfig['doc_types']['base_type']);

        $countAll = (clone $baseQuery)->count();
        $countDraft = (clone $baseQuery)->whereIn('c_invoice.docstatus', $apInvoiceConfig['statuses']['draft'])->count();
        $countInProgress = (clone $baseQuery)->whereIn('c_invoice.docstatus', $apInvoiceConfig['statuses']['in_progress'])->count();
        $countCompleted = (clone $baseQuery)->whereIn('c_invoice.docstatus', $apInvoiceConfig['statuses']['completed'])->count();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.ap-invoice.invoice-table', [
                    'invoices' => $invoices,
                ])->render(),
            ]);
        }

        return view('pages.ap-invoice.index', [
            'title' => 'AP Invoice',
            'invoices' => $invoices,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted,
        ]);
    }

    private function showForm($docId)
    {
        $apInvoiceConfig = config('idempiere.ap-invoice');

        $invoice = null;
        if ($docId !== 'new') {
            try {
                $decryptedId = Crypt::decryptString($docId);
                $invoice = CInvoice::findOrFail($decryptedId);
            } catch (\Exception $e) {
                return redirect()->route('ap-invoice.index')->with('error', 'Invalid Link');
            }
        }

        $roleId = Session::get('idempiere_role');
        $clientId = Session::get('idempiere_client');
        $tenantName = config('idempiere.tenant.name');

        $userData = Session::get('user_data');
        $clientName = null;
        if (is_array($userData)) {
            $clientName = trim((string) ($userData['client_name'] ?? '')) ?: null;
        } elseif (is_object($userData)) {
            $clientName = trim((string) ($userData->client_name ?? '')) ?: null;
        }

        if (!$clientName && $clientId) {
            $clientName = DB::connection('idempiere')
                ->table('ad_client')
                ->where('ad_client_id', $clientId)
                ->value('name');
        }

        $clientName = $clientName ?: $tenantName;

        // Organizations
        $organizations = DB::connection('idempiere')->select("
            SELECT o.ad_org_id AS id, o.name AS text
            FROM ad_org o
            JOIN ad_role_orgaccess roa ON roa.ad_org_id = o.ad_org_id
            WHERE o.isactive = 'Y' AND roa.isactive = 'Y' AND roa.ad_role_id = ? AND o.ad_org_id <> 0
            ORDER BY o.name
        ", [$roleId]);

        if (empty($organizations)) {
            $organizations = DB::connection('idempiere')->select("
                SELECT ad_org_id AS id, name AS text FROM ad_org
                WHERE ad_client_id = ? AND isactive = 'Y' AND ad_org_id <> 0 ORDER BY name
            ", [$clientId]);
        }

        $currentOrgId = $invoice ? $invoice->ad_org_id : (count($organizations) > 0 ? $organizations[0]->id : null);

        // Vendors
        $vendors = DB::connection('idempiere')->select("
            SELECT c_bpartner_id AS id, name AS text
            FROM c_bpartner
            WHERE isactive = 'Y' AND ad_client_id = ? AND isvendor='Y'
            ORDER BY name LIMIT {$apInvoiceConfig['limits']['vendor_search']}
        ", [$clientId]);

        // Doc Types for AP Invoice
        $docTypes = DB::connection('idempiere')->select("
            SELECT dt.c_doctype_id AS id, dt.name AS text
            FROM c_doctype dt
            WHERE dt.isactive = 'Y'
            AND dt.docbasetype = ?
            AND dt.ad_client_id = ?
            ORDER BY dt.c_doctype_id ASC
        ", [$apInvoiceConfig['doc_types']['base_type'], $clientId]);

        // Payment Terms
        $paymentTerms = DB::connection('idempiere')->select("
            SELECT c_paymentterm_id AS id, name AS text, netdays
            FROM c_paymentterm
            WHERE ad_client_id = ? AND isactive = 'Y'
            ORDER BY name
        ", [$clientId]);

        // Taxes
        $taxes = DB::connection('idempiere')->select("
            SELECT c_tax_id AS id, name AS text, rate
            FROM c_tax
            WHERE ad_client_id = ? AND isactive = 'Y'
            ORDER BY name
        ", [$clientId]);

        // Currencies
        $currencies = DB::connection('idempiere')->select("
            SELECT c_currency_id AS id, iso_code AS text
            FROM c_currency
            WHERE isactive = 'Y'
            ORDER BY iso_code
        ");

        // Projects
        $projects = DB::connection('idempiere')->select("
            SELECT c_project_id AS id, value || ' - ' || name AS text
            FROM c_project
            WHERE ad_client_id = ? AND isactive='Y' AND issummary='N'
            ORDER BY value
        ", [$clientId]);

        // Users (for Checked By & Approved By)
        $users = DB::connection('idempiere')->select("
            SELECT ad_user_id AS id, name AS text
            FROM ad_user
            WHERE isactive = 'Y' AND ad_client_id = ?
            ORDER BY name
        ", [$clientId]);

        // Departments
        $departments = DB::connection('idempiere')->select("
            SELECT c_department_id AS id, name AS text
            FROM c_department
            WHERE isactive = 'Y' AND ad_client_id = ?
            ORDER BY name
        ", [$clientId]);

        // Cost Centers
        $costCenters = DB::connection('idempiere')->select("
            SELECT c_costcenter_id AS id, name AS text
            FROM c_costcenter
            WHERE isactive = 'Y' AND ad_client_id = ?
            ORDER BY name
        ", [$clientId]);

        // Invoice Lines
        if ($invoice) {
            $defaultLinePerPage = $apInvoiceConfig['limits']['line_default_per_page'];
            $linePerPageOptions = $apInvoiceConfig['limits']['line_per_page_options'];
            $linePerPage = request()->integer('per_page', $defaultLinePerPage);

            if (!in_array($linePerPage, $linePerPageOptions, true)) {
                $linePerPage = $defaultLinePerPage;
            }

            $lines = DB::connection('idempiere')
                ->table('c_invoiceline as il')
                ->leftJoin('m_product as p', 'il.m_product_id', '=', 'p.m_product_id')
                ->leftJoin('c_uom as u', 'il.c_uom_id', '=', 'u.c_uom_id')
                ->leftJoin('c_orderline as ol', 'il.c_orderline_id', '=', 'ol.c_orderline_id')
                ->leftJoin('c_order as o', 'ol.c_order_id', '=', 'o.c_order_id')
                ->leftJoin('m_inoutline as iol', 'il.m_inoutline_id', '=', 'iol.m_inoutline_id')
                ->leftJoin('m_inout as io', 'iol.m_inout_id', '=', 'io.m_inout_id')
                ->where('il.c_invoice_id', $invoice->c_invoice_id)
                ->select(
                    'il.c_invoiceline_id',
                    'il.line',
                    'il.m_product_id',
                    'il.qtyinvoiced as qty',
                    'il.priceactual as unit_price',
                    'il.linenetamt as net_amount',
                    'il.description',
                    'il.c_uom_id',
                    'il.c_orderline_id',
                    'il.m_inoutline_id',
                    'p.value as product_value',
                    'p.value as product_code',
                    'p.name as product_name',
                    'u.uomsymbol',
                    'u.name as uom_name',
                    'u.uomsymbol as uom_symbol',
                    'o.documentno as po_documentno',
                    'io.documentno as gr_documentno',
                    'io.documentno as receipt_no',
                    'io.poreference as receipt_poref',
                    'io.movementdate as receipt_date',
                    'il.iswithholding as is_withholding',
                    'il.withholdingrate as withholding_rate',
                    'il.withholdingamount as withholding_amount'
                )
                ->orderBy('il.line')
                ->paginate($linePerPage);
        } else {
            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $apInvoiceConfig['limits']['line_default_per_page']);
        }

        $statusLabel = $invoice ? $this->getStatusLabel($invoice->docstatus) : $apInvoiceConfig['defaults']['document_status_label'];

        // Check Active Workflow
        $hasActiveWorkflow = false;
        if ($invoice) {
            $hasActiveWorkflow = DB::connection('idempiere')
                ->table('ad_wf_activity')
                ->join('ad_table', 'ad_table.ad_table_id', '=', 'ad_wf_activity.ad_table_id')
                ->where('ad_table.tablename', $apInvoiceConfig['workflow']['table_name'])
                ->where('ad_wf_activity.record_id', $invoice->c_invoice_id)
                ->where('ad_wf_activity.processed', 'N')
                ->exists();
        }

        $defaultDocTypeId = count($docTypes) > 0 ? $docTypes[0]->id : null;

        // Default currency (IDR)
        $defaultCurrencyId = null;
        $idrCurrency = DB::connection('idempiere')
            ->table('c_currency')
            ->where('iso_code', $apInvoiceConfig['defaults']['currency_iso_code'])
            ->where('isactive', 'Y')
            ->first();
        if ($idrCurrency) {
            $defaultCurrencyId = $idrCurrency->c_currency_id;
        } elseif (count($currencies) > 0) {
            $defaultCurrencyId = $currencies[0]->id;
        }

        // Default payment term
        $defaultPaymentTermId = count($paymentTerms) > 0 ? $paymentTerms[0]->id : null;

        // Vendor Contacts
        $vendorContacts = [];
        if ($invoice && $invoice->c_bpartner_id) {
            $vendorContacts = DB::connection('idempiere')->select("
                SELECT u.ad_user_id AS id, u.name AS text
                FROM ad_user u
                WHERE u.c_bpartner_id = ? AND u.isactive = 'Y'
                ORDER BY u.name
            ", [$invoice->c_bpartner_id]);
        }

        $viewData = [
            'title' => $docId === 'new' ? 'Create AP Invoice' : 'Edit AP Invoice',
            'invoice' => $invoice,
            'lines' => $lines,
            'tenantName' => $tenantName,
            'clientName' => $clientName,
            'organizations' => $organizations,
            'vendors' => $vendors,
            'vendorContacts' => $vendorContacts,
            'docTypes' => $docTypes,
            'paymentTerms' => $paymentTerms,
            'taxes' => $taxes,
            'currencies' => $currencies,
            'projects' => $projects,
            'isNew' => is_null($invoice),
            'docNo' => $invoice ? $invoice->documentno : '** New **',
            'status' => $statusLabel,
            'currentOrgId' => $currentOrgId,
            'invoiceDate' => $invoice && $invoice->dateinvoiced
                ? \Carbon\Carbon::parse($invoice->dateinvoiced)->format('Y-m-d')
                : date('Y-m-d'),
            'dueDate' => $invoice && $invoice->duedate
                ? \Carbon\Carbon::parse($invoice->duedate)->format('Y-m-d')
                : date('Y-m-d'),
            'dateAcct' => $invoice && $invoice->dateacct
                ? \Carbon\Carbon::parse($invoice->dateacct)->format('Y-m-d')
                : date('Y-m-d'),
            'docIdParam' => request('document_id'),
            'isReadOnly' => $invoice && in_array($invoice->docstatus, $apInvoiceConfig['statuses']['read_only'], true),
            'isDraft' => $invoice && $invoice->docstatus === 'DR',
            'activeTab' => request('tab', 'header'),
            'hasActiveWorkflow' => $hasActiveWorkflow,
            'docTypeId' => $invoice ? $invoice->c_doctype_id : $defaultDocTypeId,
            'currencyId' => $invoice ? $invoice->c_currency_id : $defaultCurrencyId,
            'paymentTermId' => $invoice ? $invoice->c_paymentterm_id : $defaultPaymentTermId,
            'taxId' => $invoice ? $invoice->c_tax_id : null,
            'grandTotal' => $invoice ? $invoice->grandtotal : 0,
            'totalLines' => $invoice ? $invoice->totallines : 0,
            'withholdingTotal' => $invoice ? ($invoice->withholdingamount ?? 0) : 0,
            'apInvoiceConfig' => $apInvoiceConfig,
        ];

        // Tambahkan ke viewData agar tersedia di view
        $viewData['users'] = $users;
        $viewData['departments'] = $departments;
        $viewData['costCenters'] = $costCenters;

        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');
            if ($tab === 'header')
                return view('pages.ap-invoice.partials.tab-header', $viewData);
            if ($tab === 'lines')
                return view('pages.ap-invoice.partials.tab-lines', $viewData);
            if ($tab === 'attachments') {
                $attachments = [];
                if (isset($invoice)) {
                    try {
                        $url = "models/c_invoice/{$invoice->c_invoice_id}/attachments";
                        $response = $this->idempiereService->get($url);
                        if ($response->successful()) {
                            $json = $response->json();
                            if (isset($json['attachments'])) {
                                $attachments = json_decode(json_encode($json['attachments']), FALSE);
                            } elseif (isset($json['records'])) {
                                $attachments = json_decode(json_encode($json['records']), FALSE);
                            } else {
                                $attachments = is_array($json) ? json_decode(json_encode($json), FALSE) : [];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("AP Invoice: Failed to fetch attachments: " . $e->getMessage());
                        $attachments = [];
                    }
                }
                $viewData['attachments'] = $attachments;
                return view('pages.ap-invoice.partials.tab-attachments', $viewData);
            }
            if ($tab === 'journals') {
                $journals = collect();
                if (isset($invoice)) {
                    $journals = DB::connection('idempiere')
                        ->table('fact_acct as fa')
                        ->join('c_elementvalue as ev', 'ev.c_elementvalue_id', '=', 'fa.account_id')
                        ->select(
                            'ev.value as account_value',
                            'ev.name as account_name',
                            'fa.description',
                            DB::raw('COALESCE(fa.amtsourcedr, 0) as amt_source_dr'),
                            DB::raw('COALESCE(fa.amtsourcecr, 0) as amt_source_cr'),
                            DB::raw('COALESCE(fa.amtacctdr, 0) as amt_acct_dr'),
                            DB::raw('COALESCE(fa.amtacctcr, 0) as amt_acct_cr')
                        )
                        ->where('fa.ad_table_id', $apInvoiceConfig['journals']['table_id'])
                        ->where('fa.record_id', $invoice->c_invoice_id)
                        ->orderBy('fa.fact_acct_id')
                        ->paginate($apInvoiceConfig['limits']['journals_per_page'])
                        ->appends(['ajax_tab' => 'journals']);
                }
                $viewData['journals'] = $journals;
                return view('pages.ap-invoice.partials.tab-journals', $viewData);
            }
        }

        return view('pages.ap-invoice.form', $viewData);
    }

    public function create()
    {
        return redirect()->route('ap-invoice.index', ['document_id' => 'new']);
    }

    public function store(Request $request)
    {
        $apInvoiceConfig = config('idempiere.ap-invoice');

        $validated = $request->validate([
            'org_id' => 'required',
            'c_bpartner_id' => 'required',
            'invoice_date' => 'required|date_format:Y-m-d',
            'due_date' => 'required|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'doc_type_id' => 'required',
            'c_currency_id' => 'required',
            'c_paymentterm_id' => 'required',
            'po_reference' => 'nullable|string',
            'description' => 'nullable|string',
            'c_project_id' => 'nullable',
            'c_tax_id' => 'required',
            'ad_user_id' => 'required',
            'adw_ad_user_approved_id' => 'nullable|integer',
            'adw_ad_user_verification_id' => 'nullable|integer',
            'c_department_id' => 'nullable|integer',
            'c_costcenter_id' => 'nullable|integer',
        ]);

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

        $bpartnerId = (int) $validated['c_bpartner_id'];

        $location = DB::connection('idempiere')
            ->table('c_bpartner_location')
            ->where('c_bpartner_id', $bpartnerId)
            ->where('isactive', 'Y')
            ->orderBy('isshipto', 'desc')
            ->first();

        $bpartnerLocationId = $location ? $location->c_bpartner_location_id : null;

        // Get price list for this vendor
        $priceList = DB::connection('idempiere')
            ->table('m_pricelist')
            ->where('ad_client_id', $sessionClientId)
            ->where('isactive', 'Y')
            ->where('issopricelist', $apInvoiceConfig['defaults']['price_list_is_so_price_list'])
            ->orderBy('m_pricelist_id', 'asc')
            ->first();

        $priceListId = $priceList ? $priceList->m_pricelist_id : null;

        // Get a valid SalesRep from the target client to avoid cross-tenant errors
        $validSalesRep = DB::connection('idempiere')
            ->table('ad_user')
            ->where('ad_client_id', $sessionClientId)
            ->where('isactive', 'Y')
            ->orderBy('ad_user_id', 'asc')
            ->value('ad_user_id');

        $salesRepId = $validSalesRep ?? $sessionUserId;

        $payload = [
            'AD_Client_ID' => (int) $sessionClientId,
            'AD_Org_ID' => (int) $validated['org_id'],
            'C_DocTypeTarget_ID' => (int) $validated['doc_type_id'],
            'C_DocType_ID' => (int) $validated['doc_type_id'],
            'DateInvoiced' => $validated['invoice_date'],
            // 'DueDate' => $validated['due_date'],
            'DateAcct' => $validated['date_acct'] ?? $validated['invoice_date'],
            'IsSOTrx' => $apInvoiceConfig['defaults']['is_so_trx'],
            'C_BPartner_ID' => $bpartnerId,
            'C_Currency_ID' => (int) $validated['c_currency_id'],
            'C_PaymentTerm_ID' => (int) $validated['c_paymentterm_id'],
            'Description' => $validated['description'] ?? null,
            'POReference' => $validated['po_reference'] ?? null,
            'SalesRep_ID' => (int) $salesRepId,
        ];

        if (!empty($validated['c_tax_id'])) {
            $payload['C_Tax_ID'] = (int) $validated['c_tax_id'];
        }

        if ($bpartnerLocationId) {
            $payload['C_BPartner_Location_ID'] = (int) $bpartnerLocationId;
        }

        if ($priceListId) {
            $payload['M_PriceList_ID'] = (int) $priceListId;
        }

        if (!empty($validated['c_project_id'])) {
            $payload['C_Project_ID'] = (int) $validated['c_project_id'];
        }

        if (!empty($validated['ad_user_id'])) {
            $payload['AD_User_ID'] = (int) $validated['ad_user_id'];
        }

        if (!empty($validated['adw_ad_user_approved_id'])) {
            $payload['ADW_AD_User_Approved_ID'] = (int) $validated['adw_ad_user_approved_id'];
        }

        if (!empty($validated['adw_ad_user_verification_id'])) {
            $payload['ADW_AD_User_Verification_ID'] = (int) $validated['adw_ad_user_verification_id'];
        }

        if (!empty($validated['c_department_id'])) {
            $payload['C_Department_ID'] = (int) $validated['c_department_id'];
        }

        if (!empty($validated['c_costcenter_id'])) {
            $payload['C_CostCenter_ID'] = (int) $validated['c_costcenter_id'];
        }

        Log::info('AP Invoice Create Payload:', $payload);

        try {
            $response = $this->idempiereService->post('models/c_invoice', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['C_Invoice_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'AP Invoice created successfully',
                    'data' => [
                        'c_invoice_id' => $id,
                        'encrypted_id' => Crypt::encryptString($id),
                    ]
                ]);
            } elseif ($response->status() === 401) {
                return response()->json(['message' => 'Session expired. Please logout and login again.'], 401);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('AP Invoice Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create AP Invoice: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'org_id' => 'nullable',
            'c_bpartner_id' => 'nullable',
            'invoice_date' => 'nullable|date_format:Y-m-d',
            'due_date' => 'nullable|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'doc_type_id' => 'nullable',
            'c_currency_id' => 'nullable',
            'c_paymentterm_id' => 'nullable',
            'po_reference' => 'nullable|string',
            'description' => 'nullable|string',
            'c_project_id' => 'nullable',
            'c_tax_id' => 'nullable|integer',
            'ad_user_id' => 'nullable|integer',
            'adw_ad_user_approved_id' => 'nullable|integer',
            'adw_ad_user_verification_id' => 'nullable|integer',
            'c_department_id' => 'nullable|integer',
            'c_costcenter_id' => 'nullable|integer',
        ]);

        $payload = [];
        if (!empty($validated['org_id']))
            $payload['AD_Org_ID'] = (int) $validated['org_id'];
        if (!empty($validated['c_bpartner_id']))
            $payload['C_BPartner_ID'] = (int) $validated['c_bpartner_id'];
        if (!empty($validated['invoice_date']))
            $payload['DateInvoiced'] = $validated['invoice_date'];
        if (!empty($validated['due_date']))
            $payload['DueDate'] = $validated['due_date'];
        if (!empty($validated['date_acct']))
            $payload['DateAcct'] = $validated['date_acct'];
        if (!empty($validated['doc_type_id']))
            $payload['C_DocType_ID'] = (int) $validated['doc_type_id'];
        if (!empty($validated['c_currency_id']))
            $payload['C_Currency_ID'] = (int) $validated['c_currency_id'];
        if (!empty($validated['c_paymentterm_id']))
            $payload['C_PaymentTerm_ID'] = (int) $validated['c_paymentterm_id'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        if (isset($validated['po_reference']))
            $payload['POReference'] = $validated['po_reference'];
        if (!empty($validated['c_project_id']))
            $payload['C_Project_ID'] = (int) $validated['c_project_id'];
        if (!empty($validated['c_tax_id']))
            $payload['C_Tax_ID'] = (int) $validated['c_tax_id'];
        if (!empty($validated['ad_user_id']))
            $payload['AD_User_ID'] = (int) $validated['ad_user_id'];
        if (array_key_exists('adw_ad_user_approved_id', $validated))
            $payload['ADW_AD_User_Approved_ID'] = $validated['adw_ad_user_approved_id'] ? (int) $validated['adw_ad_user_approved_id'] : null;
        if (array_key_exists('adw_ad_user_verification_id', $validated))
            $payload['ADW_AD_User_Verification_ID'] = $validated['adw_ad_user_verification_id'] ? (int) $validated['adw_ad_user_verification_id'] : null;
        if (array_key_exists('c_department_id', $validated))
            $payload['C_Department_ID'] = $validated['c_department_id'] ? (int) $validated['c_department_id'] : null;
        if (array_key_exists('c_costcenter_id', $validated))
            $payload['C_CostCenter_ID'] = $validated['c_costcenter_id'] ? (int) $validated['c_costcenter_id'] : null;

        try {
            $response = $this->idempiereService->put("models/c_invoice/{$id}", $payload);
            if ($response->successful()) {
                return response()->json(['message' => 'AP Invoice updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('AP Invoice Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update: ' . $e->getMessage()], 500);
        }
    }

    public function storeLine(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'line_id' => 'nullable',
            'm_product_id' => 'required',
            'qty' => 'required|numeric|gt:0',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'c_orderline_id' => 'nullable|numeric',
            'm_inoutline_id' => 'nullable|numeric',
            'c_tax_id' => 'nullable|integer',
            'is_withholding' => 'nullable',
            'withholding_rate' => 'nullable|numeric|min:0',
        ]);

        try {
            $invoiceId = Crypt::decryptString($validated['document_id']);
            $sessionClientId = Session::get('idempiere_client');

            $invoice = DB::connection('idempiere')
                ->table('c_invoice')
                ->where('c_invoice_id', $invoiceId)
                ->select('ad_org_id', 'c_currency_id', 'c_doctype_id', 'c_tax_id')
                ->first();

            if (!$invoice) {
                return response()->json(['message' => 'Invoice not found.'], 404);
            }

            $resolvedTaxId = $validated['c_tax_id'] ?? $invoice->c_tax_id ?? null;
            $taxRate = 0.0;
            if ($resolvedTaxId) {
                $taxRate = (float) (DB::connection('idempiere')
                    ->table('c_tax')
                    ->where('c_tax_id', $resolvedTaxId)
                    ->value('rate') ?? 0);
            }

            $qty = (float) $validated['qty'];
            $unitPrice = (float) $validated['unit_price'];
            $netAmt = round($qty * $unitPrice, 2);
            $taxAmt = round($netAmt * ($taxRate / 100), 2);
            $lineTotalAmt = round($netAmt + $taxAmt, 2);

            // Withholding Tax (PPh23)
            $isWithholding = $request->boolean('is_withholding');
            $withholdingRate = (float) ($validated['withholding_rate'] ?? 0);
            $withholdingAmt = $isWithholding ? round($netAmt * $withholdingRate / 100, 2) : 0;

            // Get UOM from product if not provided
            $uomId = DB::connection('idempiere')
                ->table('m_product')
                ->where('m_product_id', (int) $validated['m_product_id'])
                ->value('c_uom_id');

            if (!empty($validated['line_id'])) {
                // UPDATE — keep financial fields in sync so tax totals follow the header selection
                $updatePayload = [
                    'QtyEntered' => $qty,
                    'QtyInvoiced' => $qty,
                    'PriceEntered' => $unitPrice,
                    'PriceActual' => $unitPrice,
                    'LineNetAmt' => $netAmt,
                    'TaxAmt' => $taxAmt,
                    'LineTotalAmt' => $lineTotalAmt,
                    'Description' => $validated['description'] ?? null,
                    'IsWithholding' => $isWithholding ? 'Y' : 'N',
                    'WithholdingRate' => $withholdingRate,
                    'WithholdingAmount' => $withholdingAmt,
                ];
                if ($uomId)
                    $updatePayload['C_UOM_ID'] = (int) $uomId;
                if ($resolvedTaxId)
                    $updatePayload['C_Tax_ID'] = (int) $resolvedTaxId;

                $lineId = $validated['line_id'];
                $response = $this->idempiereService->put("models/c_invoiceline/{$lineId}", $updatePayload);
                $action = 'updated';
            } else {
                // CREATE — include all fields iDempiere needs for a new line
                $createPayload = [
                    'AD_Client_ID' => (int) $sessionClientId,
                    'C_Invoice_ID' => (int) $invoiceId,
                    'M_Product_ID' => (int) $validated['m_product_id'],
                    'QtyEntered' => $qty,
                    'QtyInvoiced' => $qty,
                    'PriceEntered' => $unitPrice,
                    'PriceActual' => $unitPrice,
                    'LineNetAmt' => $netAmt,
                    'TaxAmt' => $taxAmt,
                    'LineTotalAmt' => $lineTotalAmt,
                    'Description' => $validated['description'] ?? null,
                    'IsWithholding' => $isWithholding ? 'Y' : 'N',
                    'WithholdingRate' => $withholdingRate,
                    'WithholdingAmount' => $withholdingAmt,
                ];
                if ($invoice)
                    $createPayload['AD_Org_ID'] = (int) $invoice->ad_org_id;
                if ($uomId)
                    $createPayload['C_UOM_ID'] = (int) $uomId;
                if ($resolvedTaxId)
                    $createPayload['C_Tax_ID'] = (int) $resolvedTaxId;
                if (!empty($validated['c_orderline_id']))
                    $createPayload['C_OrderLine_ID'] = (int) $validated['c_orderline_id'];
                if (!empty($validated['m_inoutline_id']))
                    $createPayload['M_InOutLine_ID'] = (int) $validated['m_inoutline_id'];

                $response = $this->idempiereService->post('models/c_invoiceline', $createPayload);
                $action = 'created';
            }

            if ($response->successful()) {
                // Sync withholding total on invoice header
                $totalWithholding = DB::connection('idempiere')
                    ->table('c_invoiceline')
                    ->where('c_invoice_id', $invoiceId)
                    ->sum('withholdingamount');

                DB::connection('idempiere')
                    ->table('c_invoice')
                    ->where('c_invoice_id', $invoiceId)
                    ->update(['withholdingamount' => $totalWithholding ?? 0]);

                // Fetch updated invoice totals to return
                $updatedInvoice = DB::connection('idempiere')
                    ->table('c_invoice')
                    ->where('c_invoice_id', $invoiceId)
                    ->select('grandtotal', 'totallines', 'withholdingamount')
                    ->first();

                return response()->json([
                    'message' => "Line {$action} successfully",
                    'total_lines' => number_format($updatedInvoice->totallines ?? 0, 2),
                    'grandtotal' => number_format($updatedInvoice->grandtotal ?? 0, 2),
                    'tax_amount' => number_format(($updatedInvoice->grandtotal ?? 0) - ($updatedInvoice->totallines ?? 0), 2),
                    'withholding_total' => number_format($updatedInvoice->withholdingamount ?? 0, 2),
                    'grand_total_net' => number_format(($updatedInvoice->grandtotal ?? 0) - ($updatedInvoice->withholdingamount ?? 0), 2),
                ]);
            } else {
                Log::error("AP Invoice Line Error ({$action}): " . $response->body());
                return response()->json(['message' => "Failed to {$action} line: " . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('AP Invoice Line Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroyLine(Request $request)
    {
        $validated = $request->validate([
            'line_ids' => 'required|array',
            'document_id' => 'required',
        ]);

        try {
            $invoiceId = Crypt::decryptString($validated['document_id']);

            foreach ($validated['line_ids'] as $lineId) {
                $lineId = (int) $lineId;
                $response = $this->idempiereService->delete("models/c_invoiceline/{$lineId}");

                if (!$response->successful()) {
                    $errorBody = $response->json('detail') ?? $response->body();
                    Log::error("AP Invoice Line Delete Failed (line_id={$lineId}): " . $response->body());
                    return response()->json([
                        'message' => "Failed to delete line #{$lineId}: {$errorBody}"
                    ], $response->status() ?: 500);
                }
            }

            // Sync withholding total on invoice header
            $totalWithholding = DB::connection('idempiere')
                ->table('c_invoiceline')
                ->where('c_invoice_id', $invoiceId)
                ->sum('withholdingamount');

            DB::connection('idempiere')
                ->table('c_invoice')
                ->where('c_invoice_id', $invoiceId)
                ->update(['withholdingamount' => $totalWithholding ?? 0]);

            $updatedInvoice = DB::connection('idempiere')
                ->table('c_invoice')
                ->where('c_invoice_id', $invoiceId)
                ->select('grandtotal', 'totallines', 'withholdingamount')
                ->first();

            return response()->json([
                'message' => 'Line(s) deleted successfully',
                'total_lines' => number_format($updatedInvoice->totallines ?? 0, 2),
                'grandtotal' => number_format($updatedInvoice->grandtotal ?? 0, 2),
                'tax_amount' => number_format(($updatedInvoice->grandtotal ?? 0) - ($updatedInvoice->totallines ?? 0), 2),
                'withholding_total' => number_format($updatedInvoice->withholdingamount ?? 0, 2),
                'grand_total_net' => number_format(($updatedInvoice->grandtotal ?? 0) - ($updatedInvoice->withholdingamount ?? 0), 2),
            ]);

        } catch (\Exception $e) {
            Log::error('AP Invoice Line Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function process(Request $request)
    {
        $apInvoiceConfig = config('idempiere.ap-invoice');

        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:' . implode(',', $apInvoiceConfig['workflow']['allowed_actions']),
        ]);

        try {
            $invoiceId = Crypt::decryptString($validated['document_id']);
            $payload = ['doc-action' => $validated['doc_action']];

            Log::info('Processing AP Invoice', [
                'invoice_id' => $invoiceId,
                'action' => $validated['doc_action']
            ]);

            $response = $this->idempiereService->put("models/c_invoice/{$invoiceId}", $payload);

            if ($response->successful()) {
                if ($validated['doc_action'] === 'CO') {
                    // Manual DB update to ensure custom columns are cleared
                    DB::connection('idempiere')
                        ->table('c_invoice')
                        ->where('c_invoice_id', $invoiceId)
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
            Log::error('AP Invoice Process Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate(['document_id' => 'required']);

        try {
            $invoiceId = Crypt::decryptString($validated['document_id']);

            $invoice = DB::connection('idempiere')
                ->table('c_invoice')
                ->where('c_invoice_id', $invoiceId)
                ->select('docstatus', 'documentno')
                ->first();

            if (!$invoice) {
                return response()->json(['message' => 'AP Invoice not found.'], 404);
            }

            if ($invoice->docstatus !== 'DR') {
                return response()->json(['message' => 'Only Draft invoices can be deleted.'], 422);
            }

            $response = $this->idempiereService->delete("models/c_invoice/{$invoiceId}");

            if (!$response->successful()) {
                $errorBody = $response->json('detail') ?? $response->body();
                return response()->json(['message' => 'Failed to delete: ' . $errorBody], $response->status() ?: 500);
            }

            return response()->json(['message' => 'AP Invoice ' . $invoice->documentno . ' deleted successfully.']);

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID.'], 400);
        } catch (\Exception $e) {
            Log::error('AP Invoice Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'document_id' => 'required',
            'file' => 'required|file|max:10240'
        ]);

        try {
            $docId = Crypt::decryptString($request->document_id);
            $file = $request->file('file');
            $response = $this->idempiereService->uploadFile(
                "models/c_invoice/{$docId}/attachments",
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
            $encodedId = rawurlencode($request->attachment_id);
            $response = $this->idempiereService->delete("models/c_invoice/{$docId}/attachments/{$encodedId}");

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
            $url = "models/c_invoice/{$docId}/attachments/{$encodedFileName}";
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
            Log::error('AP Invoice View Attachment Error: ' . $e->getMessage());
            abort(500, 'Error viewing attachment');
        }
    }

    public function repost(Request $request, $id)
    {
        try {
            $decryptedId = Crypt::decryptString($id);
            $invoice = CInvoice::findOrFail($decryptedId);

            // Trigger re-post by setting posted to N and processing to N
            $invoice->posted = 'N';
            $invoice->processing = 'N';
            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked for re-posting successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error("AP Invoice Repost Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger re-post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportJournals($id)
    {
        try {
            $apInvoiceConfig = config('idempiere.ap-invoice');
            $decryptedId = Crypt::decryptString($id);
            $invoice = CInvoice::findOrFail($decryptedId);

            $journals = DB::connection('idempiere')
                ->table('fact_acct as fa')
                ->join('c_elementvalue as ev', 'ev.c_elementvalue_id', '=', 'fa.account_id')
                ->select(
                    'ev.value as account_value',
                    'ev.name as account_name',
                    DB::raw('COALESCE(fa.amtacctdr, 0) as amt_acct_dr'),
                    DB::raw('COALESCE(fa.amtacctcr, 0) as amt_acct_cr')
                )
                ->where('fa.ad_table_id', $apInvoiceConfig['journals']['table_id'])
                ->where('fa.record_id', $invoice->c_invoice_id)
                ->orderBy('fa.fact_acct_id')
                ->get();

            $filename = 'journal_' . $invoice->documentno . '_' . date('Ymd_His') . '.xls';

            $headers = [
                "Content-Type" => "application/vnd.ms-excel",
                "Content-Disposition" => "attachment; filename=\"$filename\"",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ];

            return response()->stream(function () use ($journals, $invoice) {
                echo "<html><head><meta charset='UTF-8'></head><body>";
                echo "<h3>Journal Entries for Invoice: " . $invoice->documentno . "</h3>";
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
                    echo "<td>" . ($row->account_value ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row->account_name ?? '') . "</td>";
                    echo "<td style='text-align:right;'>" . number_format($row->amt_acct_dr, 2) . "</td>";
                    echo "<td style='text-align:right;'>" . number_format($row->amt_acct_cr, 2) . "</td>";
                    echo "</tr>";
                    $totalDr += $row->amt_acct_dr;
                    $totalCr += $row->amt_acct_cr;
                }

                echo "<tr>";
                echo "<td colspan='2' style='font-weight:bold; text-align:right;'>Total</td>";
                echo "<td style='font-weight:bold; text-align:right;'>" . number_format($totalDr, 2) . "</td>";
                echo "<td style='font-weight:bold; text-align:right;'>" . number_format($totalCr, 2) . "</td>";
                echo "</tr>";

                echo "</tbody></table></body></html>";
            }, 200, $headers);

        } catch (\Exception $e) {
            Log::error("AP Invoice Export Journals Error: " . $e->getMessage());
            abort(500, 'Error exporting journals');
        }
    }

    // API: Get Receipt Lines for "From Receipt" modal
    public function getReceiptLines(Request $request)
    {
        $apInvoiceConfig = config('idempiere.ap-invoice');
        $clientId = Session::get('idempiere_client');
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = $apInvoiceConfig['limits']['receipt_modal'];
        $vendorId = $request->get('vendor_id'); // optional: filter by vendor from the invoice

        if (strlen($search) < $apInvoiceConfig['limits']['link_min_search_length']) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
                'total' => 0,
                'per_page' => $perPage,
            ]);
        }

        $query = DB::connection('idempiere')
            ->table('m_inoutline as il')
            ->join('m_inout as io', 'io.m_inout_id', '=', 'il.m_inout_id')
            ->join('m_product as p', 'p.m_product_id', '=', 'il.m_product_id')
            ->leftJoin('c_uom as u', 'u.c_uom_id', '=', 'il.c_uom_id')
            ->leftJoin('c_orderline as ol', 'ol.c_orderline_id', '=', 'il.c_orderline_id')
            ->where('io.ad_client_id', $clientId)
            ->where('io.docstatus', $apInvoiceConfig['receipt_filters']['doc_status'])
            ->where('io.movementtype', $apInvoiceConfig['receipt_filters']['movement_type'])
            ->where('io.issotrx', $apInvoiceConfig['receipt_filters']['is_so_trx'])
            // Exclude lines already fully invoiced
            ->whereRaw("il.movementqty - COALESCE((SELECT SUM(inl.qtyinvoiced) FROM c_invoiceline inl JOIN c_invoice i ON i.c_invoice_id = inl.c_invoice_id WHERE i.docstatus NOT IN ('VO','RE') AND inl.m_inoutline_id = il.m_inoutline_id), 0) > 0")
            ->select(
                'il.m_inoutline_id as id',
                'il.c_orderline_id',
                'il.movementqty as qty',
                DB::raw("(il.movementqty - COALESCE((SELECT SUM(inl.qtyinvoiced) FROM c_invoiceline inl JOIN c_invoice i ON i.c_invoice_id = inl.c_invoice_id WHERE i.docstatus NOT IN ('VO','RE') AND inl.m_inoutline_id = il.m_inoutline_id), 0)) as remaining_qty"),
                'il.m_product_id',
                'io.documentno as receipt_no',
                'io.poreference',
                'io.c_bpartner_id as vendor_id',
                'p.name as product_name',
                'p.value as product_value',
                'u.uomsymbol as uom_symbol',
                DB::raw('COALESCE(ol.priceactual, 0) as unit_price')
            );

        if ($vendorId) {
            // If the invoice has a vendor, restrict to receipts from that vendor
            $query->where('io.c_bpartner_id', (int) $vendorId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('io.documentno', 'ilike', "%{$search}%")
                    ->orWhere('p.name', 'ilike', "%{$search}%")
                    ->orWhere('io.poreference', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });
        }

        $results = $query->orderBy('io.created', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $results->map(function ($item) {
                return [
                    'id' => $item->id,               // This is m_inoutline_id
                    'receipt_no' => $item->receipt_no,
                    'poreference' => $item->poreference,
                    'product_name' => $item->product_name,
                    'product_code' => $item->product_value,
                    'm_product_id' => $item->m_product_id,
                    'qty' => $item->qty,
                    'remaining_qty' => $item->remaining_qty,
                    'uom_symbol' => $item->uom_symbol,
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'c_orderline_id' => $item->c_orderline_id,
                ];
            }),
            'pagination' => ['more' => $results->hasMorePages()],
            'total' => $results->total(),
            'per_page' => $results->perPage(),
        ]);
    }


    // API: Get GR Lines for linking
    public function getGrLines(Request $request)
    {
        $apInvoiceConfig = config('idempiere.ap-invoice');
        $search = $request->get('q', '');
        $perPage = $apInvoiceConfig['limits']['gr_modal'];
        $page = $request->get('page', 1);
        $clientId = Session::get('idempiere_client');
        $vendorId = $request->get('vendor_id');

        if (strlen($search) < $apInvoiceConfig['limits']['link_min_search_length']) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }

        $query = DB::connection('idempiere')
            ->table('m_inoutline as il')
            ->join('m_inout as io', 'io.m_inout_id', '=', 'il.m_inout_id')
            ->join('m_product as p', 'p.m_product_id', '=', 'il.m_product_id')
            ->leftJoin('c_uom as u', 'u.c_uom_id', '=', 'il.c_uom_id')
            ->leftJoin('c_orderline as ol', 'ol.c_orderline_id', '=', 'il.c_orderline_id')
            ->where('io.ad_client_id', $clientId)
            ->where('io.docstatus', $apInvoiceConfig['receipt_filters']['doc_status'])
            ->where('io.movementtype', $apInvoiceConfig['receipt_filters']['movement_type'])
            ->where('io.issotrx', $apInvoiceConfig['receipt_filters']['is_so_trx'])
            ->where(function ($q) use ($search) {
                $q->where('io.documentno', 'ilike', "%{$search}%")
                    ->orWhere('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });

        if ($vendorId) {
            $query->where('io.c_bpartner_id', (int) $vendorId);
        }

        $results = $query->select(
            'il.m_inoutline_id',
            'il.c_orderline_id',
            'il.movementqty as qty',
            'il.m_product_id',
            'io.documentno as gr_no',
            'p.name as product_name',
            'p.value as product_code',
            'u.uomsymbol as uom_symbol',
            DB::raw('COALESCE(ol.priceactual, 0) as unit_price')
        )
            ->orderBy('io.created', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $results->map(function ($item) {
                return [
                    'id' => $item->m_inoutline_id,
                    'text' => $item->gr_no . ' - ' . $item->product_code . ' - ' . $item->product_name . ' (Qty: ' . $item->qty . ')',
                    'm_product_id' => $item->m_product_id,
                    'product_name' => $item->product_name,
                    'product_code' => $item->product_code,
                    'qty' => $item->qty,
                    'uom_symbol' => $item->uom_symbol,
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'c_orderline_id' => $item->c_orderline_id,
                ];
            }),
            'pagination' => ['more' => $results->hasMorePages()],
        ]);
    }

    // API: Get Products
    public function getProducts(Request $request)
    {
        $apInvoiceConfig = config('idempiere.ap-invoice');
        $search = $request->get('q', '');
        $perPage = $apInvoiceConfig['limits']['products_per_page'];
        $page = $request->get('page', 1);
        $clientId = Session::get('idempiere_client');

        $query = DB::connection('idempiere')
            ->table('m_product as p')
            ->leftJoin('c_uom as u', 'u.c_uom_id', '=', 'p.c_uom_id')
            ->where('p.ad_client_id', $clientId)
            ->where('p.isactive', 'Y')
            ->where('p.ispurchased', 'Y');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });
        }

        $results = $query->select(
            'p.m_product_id',
            'p.name as product_name',
            'p.value as product_code',
            'p.c_uom_id',
            'u.uomsymbol as uom_symbol',
            'u.name as uom_name'
        )
            ->orderBy('p.name')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $results->map(function ($item) {
                return [
                    'id' => $item->m_product_id,
                    'text' => $item->product_code . ' - ' . $item->product_name,
                    'c_uom_id' => $item->c_uom_id,
                    'uom_symbol' => $item->uom_symbol,
                    'uom_name' => $item->uom_name,
                ];
            }),
            'pagination' => ['more' => $results->hasMorePages()],
        ]);
    }

    // API: Get Vendor Contacts
    public function getVendorContacts(Request $request)
    {
        $vendorId = $request->get('vendor_id');

        if (!$vendorId) {
            return response()->json([
                'results' => [],
            ]);
        }

        $contacts = DB::connection('idempiere')
            ->table('ad_user as u')
            ->where('u.c_bpartner_id', (int) $vendorId)
            ->where('u.isactive', 'Y')
            ->select(
                'u.ad_user_id as id',
                'u.name as text'
            )
            ->orderBy('u.name')
            ->get();

        return response()->json([
            'results' => $contacts->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text,
                ];
            }),
        ]);
    }

    public function print($id)
    {
        try {
            $decryptedId = Crypt::decryptString($id);
            $invoice = CInvoice::find($decryptedId);

            if (!$invoice) {
                abort(404);
            }

            // Fetch Vendor with location and contact info
            $vendor = DB::connection('idempiere')
                ->table('c_bpartner as bp')
                ->leftJoin('c_bpartner_location as bpl', function ($join) use ($invoice) {
                    $join->on('bpl.c_bpartner_id', '=', 'bp.c_bpartner_id')
                        ->where('bpl.c_bpartner_location_id', '=', $invoice->c_bpartner_location_id);
                })
                ->leftJoin('c_location as loc', 'loc.c_location_id', '=', 'bpl.c_location_id')
                ->leftJoin('ad_user as u', function ($join) use ($invoice) {
                    $join->on('u.c_bpartner_id', '=', 'bp.c_bpartner_id')
                        ->where('u.ad_user_id', '=', $invoice->ad_user_id ?? 0);
                })
                ->where('bp.c_bpartner_id', $invoice->c_bpartner_id)
                ->select(
                    'bp.c_bpartner_id',
                    'bp.name as vendor_name',
                    'bp.taxid',
                    DB::raw("COALESCE(loc.address1, '') as address1"),
                    DB::raw("COALESCE(loc.address2, '') as address2"),
                    DB::raw("COALESCE(loc.city, '') as city"),
                    DB::raw("COALESCE(u.name, '') as contact_name"),
                    DB::raw("COALESCE(u.phone, '') as phone")
                )
                ->first();

            // Fetch Client Name
            $clientName = DB::connection('idempiere')
                ->table('ad_client')
                ->where('ad_client_id', $invoice->ad_client_id)
                ->value('name');

            // Fetch Org address via c_bpartner → c_bpartner_location → c_location
            $orgInfo = null;
            try {
                $orgInfo = DB::connection('idempiere')
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
                Log::warning('AP Invoice Org info fetch warning: ' . $e->getMessage());
            }

            // Fetch PO DocumentNo if linked
            $poDocumentNo = null;
            if ($invoice->c_order_id) {
                $poDocumentNo = DB::connection('idempiere')
                    ->table('c_order')
                    ->where('c_order_id', $invoice->c_order_id)
                    ->value('documentno');
            }

            // Fetch Payment Term
            $paymentTerm = DB::connection('idempiere')
                ->table('c_paymentterm')
                ->where('c_paymentterm_id', $invoice->c_paymentterm_id)
                ->value('name');

            // Fetch Signers Names from Custom Columns
            $checkedBy = $invoice->adw_ad_user_verification_id ?
                DB::connection('idempiere')->table('ad_user')->where('ad_user_id', $invoice->adw_ad_user_verification_id)->value('name') : null;

            $approvedBy = $invoice->adw_ad_user_approved_id ?
                DB::connection('idempiere')->table('ad_user')->where('ad_user_id', $invoice->adw_ad_user_approved_id)->value('name') : null;

            // Creator as Prepared By
            $preparedBy = DB::connection('idempiere')
                ->table('ad_user')
                ->where('ad_user_id', $invoice->createdby)
                ->value('name');

            $preparedDate = date('d M Y H:i', strtotime($invoice->created));
            $preparedQr = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode("Prepared by " . $preparedBy . " on " . $invoice->created);

            // Checked By (Verification) — status logic
            $checkedQr = null;
            $checkedDate = 'Pending'; // Default when not yet actioned
            if ($checkedBy) {
                if ($invoice->adw_checked_isapproved == 'AP' && $invoice->adw_checked_date) {
                    $checkedDate = date('d M Y H:i', strtotime($invoice->adw_checked_date));
                    $checkedQr = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode("Checked by " . $checkedBy . " on " . $invoice->adw_checked_date);
                } elseif ($invoice->adw_checked_isapproved == 'RE') {
                    $checkedDate = 'Rejected';
                }
            }

            // Approved By — status logic
            $approvedQr = null;
            $approvedDate = 'Pending'; // Default when not yet actioned
            if ($approvedBy) {
                if ($invoice->adw_approve_isapproved == 'AP' && $invoice->adw_approved_date) {
                    $approvedDate = date('d M Y H:i', strtotime($invoice->adw_approved_date));
                    $approvedQr = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode("Approved by " . $approvedBy . " on " . $invoice->adw_approved_date);
                } elseif ($invoice->adw_approve_isapproved == 'RE') {
                    $approvedDate = 'Rejected';
                }
            }

            // Fetch Logo
            $logoBase64 = null;
            try {
                $clientInfo = DB::connection('idempiere')
                    ->table('ad_clientinfo')
                    ->where('ad_client_id', $invoice->ad_client_id)
                    ->first();

                if ($clientInfo && isset($clientInfo->logo_id)) {
                    $image = DB::connection('idempiere')
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
                Log::warning('AP Invoice Logo fetch warning: ' . $e->getMessage());
            }

            // Fallback to local logo
            if (!$logoBase64) {
                $logoPath = public_path('assets/media/logos/logo-long.png');
                if (file_exists($logoPath)) {
                    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($logoPath));
                }
            }

            // Fetch Lines
            $lines = DB::connection('idempiere')
                ->table('c_invoiceline as il')
                ->leftJoin('m_product as p', 'il.m_product_id', '=', 'p.m_product_id')
                ->leftJoin('c_uom as u', 'il.c_uom_id', '=', 'u.c_uom_id')
                ->where('il.c_invoice_id', $invoice->c_invoice_id)
                ->select(
                    'il.*',
                    'p.value as product_value',
                    'p.name as product_name',
                    'u.uomsymbol',
                    'u.name as uom_name'
                )
                ->orderBy('il.line')
                ->get();

            // Fetch Tax Amount from c_invoicetax
            $taxAmount = DB::connection('idempiere')
                ->table('c_invoicetax as it')
                ->where('it.c_invoice_id', $invoice->c_invoice_id)
                ->sum('it.taxamt');

            // Fetch Tax Name (optional, for display)
            $taxName = DB::connection('idempiere')
                ->table('c_invoicetax as it')
                ->join('c_tax as t', 'it.c_tax_id', '=', 't.c_tax_id')
                ->where('it.c_invoice_id', $invoice->c_invoice_id)
                ->value('t.name');

            $taxRate = DB::connection('idempiere')
                ->table('c_invoicetax as it')
                ->join('c_tax as t', 'it.c_tax_id', '=', 't.c_tax_id')
                ->where('it.c_invoice_id', $invoice->c_invoice_id)
                ->value('t.rate');

            // Calculate Grand Total for spelling conversion
            $subTotal = 0;
            foreach ($lines as $line) {
                $subTotal += ($line->qtyentered * $line->priceentered);
            }
            $withholdingTotal = $invoice->withholdingamount ?? 0;
            $grandTotal = $subTotal + ($taxAmount ?? 0) - $withholdingTotal;
            $grandTotalWords = \App\Http\Controllers\HelperController::numberToWordsEnglish($grandTotal);

            $pdf = Pdf::loadView('pages.ap-invoice.pdf', [
                'invoice' => $invoice,
                'vendor' => $vendor,
                'poDocumentNo' => $poDocumentNo,
                'paymentTerm' => $paymentTerm,
                'preparedBy' => $preparedBy,
                'preparedDate' => $preparedDate,
                'preparedQr' => $preparedQr,
                'checkedBy' => $checkedBy,
                'checkedDate' => $checkedDate,
                'checkedQr' => $checkedQr,
                'approvedBy' => $approvedBy,
                'approvedDate' => $approvedDate,
                'approvedQr' => $approvedQr,
                'logoBase64' => $logoBase64,
                'lines' => $lines,
                'clientName' => $clientName,
                'orgInfo' => $orgInfo,
                'taxAmount' => $taxAmount ?? 0,
                'taxName' => $taxName ?? 'PPN',
                'taxRate' => $taxRate ?? 0,
                'withholdingTotal' => $withholdingTotal,
                'grandTotalNet' => $grandTotal,
                'grandTotalWords' => $grandTotalWords,
            ])->setOptions(['isRemoteEnabled' => true]);

            $filename = 'AP-Invoice-' . str_replace(['/', '\\'], '-', $invoice->documentno) . '.pdf';
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('AP Invoice Print Error: ' . $e->getMessage());
            abort(500, 'Error generating PDF');
        }
    }

    private function getStatusLabel(?string $status): string
    {
        $statusLabels = config('idempiere.ap-invoice.statuses.labels', []);

        return $statusLabels[$status] ?? ($status ?? 'Unknown');
    }
}
