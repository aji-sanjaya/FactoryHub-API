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

class ArInvoiceController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    public function index()
    {
        $arInvoiceConfig = config('idempiere.ar-invoice');

        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        if (request()->has('document_id')) {
            return $this->showForm(request('document_id'));
        }

        $perPage = request()->get('per_page', 10);
        $status = request()->get('status', $arInvoiceConfig['statuses']['default_list']);
        $search = request()->get('search', '');

        $clientId = Session::get('idempiere_client');

        $query = CInvoice::where('c_invoice.ad_client_id', $clientId)
            ->where('c_invoice.issotrx', $arInvoiceConfig['defaults']['is_so_trx'])
            ->where('c_invoice.isactive', 'Y');

        // Filter to AR Invoice doc base type (API) via join
        $query->join('c_doctype as dt', 'dt.c_doctype_id', '=', 'c_invoice.c_doctype_id')
            ->where('dt.docbasetype', $arInvoiceConfig['doc_types']['base_type'])
            ->select('c_invoice.*');

        if ($status !== 'all' && !empty($status)) {
            $statusArray = is_array($status) ? $status : explode(',', $status);
            $query->whereIn('c_invoice.docstatus', $statusArray);
        }
        if ($search) {
            $query->where('c_invoice.documentno', 'ilike', "%{$search}%");
        }

        $query->orderBy('c_invoice.created', 'desc');
        $invoices = $query->paginate($perPage);

        $baseQuery = CInvoice::where('c_invoice.ad_client_id', $clientId)
            ->where('c_invoice.issotrx', $arInvoiceConfig['defaults']['is_so_trx'])
            ->where('c_invoice.isactive', 'Y')
            ->join('c_doctype as dt', 'dt.c_doctype_id', '=', 'c_invoice.c_doctype_id')
            ->where('dt.docbasetype', $arInvoiceConfig['doc_types']['base_type']);

        $countAll = (clone $baseQuery)->count();
        $countDraft = (clone $baseQuery)->where('c_invoice.docstatus', $arInvoiceConfig['statuses']['draft'])->count();
        $countInProgress = (clone $baseQuery)->where('c_invoice.docstatus', $arInvoiceConfig['statuses']['in_progress'])->count();
        $countCompleted = (clone $baseQuery)->whereIn('c_invoice.docstatus', $arInvoiceConfig['statuses']['completed'])->count();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.ar-invoice.invoice-table', [
                    'invoices' => $invoices,
                ])->render(),
            ]);
        }

        return view('pages.ar-invoice.index', [
            'title' => 'AR Invoice',
            'invoices' => $invoices,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted,
        ]);
    }

    private function showForm($docId)
    {
        $arInvoiceConfig = config('idempiere.ar-invoice');

        $invoice = null;
        if ($docId !== 'new') {
            try {
                $decryptedId = Crypt::decryptString($docId);
                $invoice = CInvoice::findOrFail($decryptedId);
            } catch (\Exception $e) {
                return redirect()->route('ar-invoice.index')->with('error', 'Invalid Link');
            }
        }

        $roleId = Session::get('idempiere_role');
        $clientId = Session::get('idempiere_client');

        $userData = Session::get('user_data');
        $tenantName = config('idempiere.tenant.name');
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

        // Customers
        $customers = DB::connection('idempiere')->select("
            SELECT c_bpartner_id AS id, name AS text
            FROM c_bpartner
            WHERE isactive = 'Y' AND ad_client_id = ? AND iscustomer=?
            ORDER BY name LIMIT {$arInvoiceConfig['limits']['customer_search']}
        ", [$clientId, $arInvoiceConfig['filters']['is_customer']]);

        // Doc Types for AR Invoice
        $docTypes = DB::connection('idempiere')->select("
            SELECT dt.c_doctype_id AS id, dt.name AS text
            FROM c_doctype dt
            WHERE dt.isactive = 'Y'
            AND dt.docbasetype = ?
            AND dt.ad_client_id = ?
            ORDER BY dt.c_doctype_id ASC
        ", [$arInvoiceConfig['doc_types']['base_type'], $clientId]);

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

        // Invoice Lines
        if ($invoice) {
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
                    'io.documentno as shipment_no',
                    'io.poreference as shipment_poref',
                    'io.movementdate as shipment_date'
                )
                ->orderBy('il.line')
                ->paginate(10);
        } else {
            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        $statusLabel = $invoice ? $this->getStatusLabel($invoice->docstatus) : 'Draft';

        // Check Active Workflow
        $hasActiveWorkflow = false;
        if ($invoice) {
            $hasActiveWorkflow = DB::connection('idempiere')
                ->table('ad_wf_activity')
                ->join('ad_table', 'ad_table.ad_table_id', '=', 'ad_wf_activity.ad_table_id')
                ->where('ad_table.tablename', 'C_Invoice')
                ->where('ad_wf_activity.record_id', $invoice->c_invoice_id)
                ->where('ad_wf_activity.processed', 'N')
                ->exists();
        }

        $defaultDocTypeId = count($docTypes) > 0 ? $docTypes[0]->id : null;

        // Default currency (IDR)
        $defaultCurrencyId = null;
        $idrCurrency = DB::connection('idempiere')
            ->table('c_currency')
            ->where('iso_code', $arInvoiceConfig['defaults']['currency_iso_code'])
            ->where('isactive', 'Y')
            ->first();
        if ($idrCurrency) {
            $defaultCurrencyId = $idrCurrency->c_currency_id;
        } elseif (count($currencies) > 0) {
            $defaultCurrencyId = $currencies[0]->id;
        }

        // Default payment term
        $defaultPaymentTermId = count($paymentTerms) > 0 ? $paymentTerms[0]->id : null;

        // Customer Contacts
        $customerContacts = [];
        if ($invoice && $invoice->c_bpartner_id) {
            $customerContacts = DB::connection('idempiere')->select("
                SELECT u.ad_user_id AS id, u.name AS text
                FROM ad_user u
                WHERE u.c_bpartner_id = ? AND u.isactive = 'Y'
                ORDER BY u.name
            ", [$invoice->c_bpartner_id]);
        }

        $viewData = [
            'title' => $docId === 'new' ? 'Create AR Invoice' : 'Edit AR Invoice',
            'invoice' => $invoice,
            'lines' => $lines,
            'organizations' => $organizations,
            'customers' => $customers,
            'customerContacts' => $customerContacts,
            'clientName' => $clientName,
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
            'isReadOnly' => $invoice && in_array($invoice->docstatus, $arInvoiceConfig['statuses']['read_only']),
            'isDraft' => $invoice && $invoice->docstatus === $arInvoiceConfig['statuses']['draft'],
            'activeTab' => request('tab', 'header'),
            'hasActiveWorkflow' => $hasActiveWorkflow,
            'docTypeId' => $invoice ? $invoice->c_doctype_id : $defaultDocTypeId,
            'currencyId' => $invoice ? $invoice->c_currency_id : $defaultCurrencyId,
            'paymentTermId' => $invoice ? $invoice->c_paymentterm_id : $defaultPaymentTermId,
            'taxId' => $invoice ? $invoice->c_tax_id : null,
            'grandTotal' => $invoice ? $invoice->grandtotal : 0,
            'totalLines' => $invoice ? $invoice->totallines : 0,
        ];

        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');
            if ($tab === 'header')
                return view('pages.ar-invoice.partials.tab-header', $viewData);
            if ($tab === 'lines')
                return view('pages.ar-invoice.partials.tab-lines', $viewData);
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
                        Log::error("AR Invoice: Failed to fetch attachments: " . $e->getMessage());
                        $attachments = [];
                    }
                }
                $viewData['attachments'] = $attachments;
                return view('pages.ar-invoice.partials.tab-attachments', $viewData);
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
                        ->where('fa.ad_table_id', $arInvoiceConfig['journals']['table_id'])
                        ->where('fa.record_id', $invoice->c_invoice_id)
                        ->orderBy('fa.fact_acct_id')
                        ->paginate(10)
                        ->appends(['ajax_tab' => 'journals']);
                }
                $viewData['journals'] = $journals;
                return view('pages.ar-invoice.partials.tab-journals', $viewData);
            }
        }

        return view('pages.ar-invoice.form', $viewData);
    }

    public function create()
    {
        return redirect()->route('ar-invoice.index', ['document_id' => 'new']);
    }

    public function store(Request $request)
    {
        $arInvoiceConfig = config('idempiere.ar-invoice');

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

        // Get price list for this customer
        $priceList = DB::connection('idempiere')
            ->table('m_pricelist')
            ->where('ad_client_id', $sessionClientId)
            ->where('isactive', 'Y')
            ->where('issopricelist', $arInvoiceConfig['filters']['price_list_is_so_price_list'])
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
            'DueDate' => $validated['due_date'],
            'DateAcct' => $validated['date_acct'] ?? $validated['invoice_date'],
            'IsSOTrx' => $arInvoiceConfig['defaults']['is_so_trx'],
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

        Log::info('AR Invoice Create Payload:', $payload);

        try {
            $response = $this->idempiereService->post('models/c_invoice', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['C_Invoice_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'AR Invoice created successfully',
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
            Log::error('AR Invoice Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create AR Invoice: ' . $e->getMessage()], 500);
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
            'ad_user_id' => 'nullable|integer',
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

        try {
            $response = $this->idempiereService->put("models/c_invoice/{$id}", $payload);
            if ($response->successful()) {
                return response()->json(['message' => 'AR Invoice updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('AR Invoice Update Error: ' . $e->getMessage());
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
                return response()->json(['message' => "Line {$action} successfully"]);
            } else {
                Log::error("AR Invoice Line Error ({$action}): " . $response->body());
                return response()->json(['message' => "Failed to {$action} line: " . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('AR Invoice Line Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroyLine(Request $request)
    {
        $validated = $request->validate(['line_ids' => 'required|array']);

        try {
            foreach ($validated['line_ids'] as $lineId) {
                $lineId = (int) $lineId;
                $response = $this->idempiereService->delete("models/c_invoiceline/{$lineId}");

                if (!$response->successful()) {
                    $errorBody = $response->json('detail') ?? $response->body();
                    Log::error("AR Invoice Line Delete Failed (line_id={$lineId}): " . $response->body());
                    return response()->json([
                        'message' => "Failed to delete line #{$lineId}: {$errorBody}"
                    ], $response->status() ?: 500);
                }
            }

            return response()->json(['message' => 'Line(s) deleted successfully']);

        } catch (\Exception $e) {
            Log::error('AR Invoice Line Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:CO,PR,VO,CL,RC',
        ]);

        try {
            $invoiceId = Crypt::decryptString($validated['document_id']);
            $payload = ['doc-action' => $validated['doc_action']];

            Log::info('Processing AR Invoice', [
                'invoice_id' => $invoiceId,
                'action' => $validated['doc_action']
            ]);

            $response = $this->idempiereService->put("models/c_invoice/{$invoiceId}", $payload);

            if ($response->successful()) {
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
            Log::error('AR Invoice Process Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $arInvoiceConfig = config('idempiere.ar-invoice');

        $validated = $request->validate(['document_id' => 'required']);

        try {
            $invoiceId = Crypt::decryptString($validated['document_id']);

            $invoice = DB::connection('idempiere')
                ->table('c_invoice')
                ->where('c_invoice_id', $invoiceId)
                ->select('docstatus', 'documentno')
                ->first();

            if (!$invoice) {
                return response()->json(['message' => 'AR Invoice not found.'], 404);
            }

            if ($invoice->docstatus !== $arInvoiceConfig['statuses']['draft']) {
                return response()->json(['message' => 'Only Draft invoices can be deleted.'], 422);
            }

            $response = $this->idempiereService->delete("models/c_invoice/{$invoiceId}");

            if (!$response->successful()) {
                $errorBody = $response->json('detail') ?? $response->body();
                return response()->json(['message' => 'Failed to delete: ' . $errorBody], $response->status() ?: 500);
            }

            return response()->json(['message' => 'AR Invoice ' . $invoice->documentno . ' deleted successfully.']);

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID.'], 400);
        } catch (\Exception $e) {
            Log::error('AR Invoice Delete Error: ' . $e->getMessage());
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
            Log::error('AR Invoice View Attachment Error: ' . $e->getMessage());
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
            Log::error("AR Invoice Repost Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger re-post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportJournals($id)
    {
        try {
            $arInvoiceConfig = config('idempiere.ar-invoice');
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
                ->where('fa.ad_table_id', $arInvoiceConfig['journals']['table_id'])
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
            Log::error("AR Invoice Export Journals Error: " . $e->getMessage());
            abort(500, 'Error exporting journals');
        }
    }

    // API: Get Shipment Lines for "From Shipment" modal
    public function getShipmentLines(Request $request)
    {
        $arInvoiceConfig = config('idempiere.ar-invoice');
        $clientId = Session::get('idempiere_client');
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = $arInvoiceConfig['limits']['shipment_lines_per_page'];
        $excludedStatusesSql = implode("','", array_map(static fn ($status) => str_replace("'", "''", $status), $arInvoiceConfig['statuses']['excluded_invoiced']));

        $documentId = $request->get('document_id');
        if (!$documentId) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
                'total' => 0,
                'per_page' => $perPage,
                'error' => 'Document ID is required.'
            ]);
        }

        try {
            $decryptedId = Crypt::decryptString($documentId);
            $invoice = DB::connection('idempiere')
                ->table('c_invoice')
                ->where('c_invoice_id', $decryptedId)
                ->select('c_bpartner_id')
                ->first();

            if (!$invoice || !$invoice->c_bpartner_id) {
                return response()->json([
                    'results' => [],
                    'pagination' => ['more' => false],
                    'total' => 0,
                    'per_page' => $perPage,
                    'error' => 'Invoice or Customer not found.'
                ]);
            }
            $customerId = $invoice->c_bpartner_id;
        } catch (\Exception $e) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
                'total' => 0,
                'per_page' => $perPage,
                'error' => 'Invalid document ID.'
            ]);
        }

        if (strlen($search) < $arInvoiceConfig['limits']['lookup_min_search_length']) {
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
            ->where('io.c_bpartner_id', (int) $customerId) // Mandatory customer filter
            ->where('io.docstatus', $arInvoiceConfig['shipment_filters']['doc_status'])
            ->where('io.movementtype', $arInvoiceConfig['shipment_filters']['movement_type'])
            ->where('io.issotrx', $arInvoiceConfig['shipment_filters']['is_so_trx'])
            // Exclude lines already fully invoiced
            ->whereRaw("il.movementqty - COALESCE((SELECT SUM(inl.qtyinvoiced) FROM c_invoiceline inl JOIN c_invoice i ON i.c_invoice_id = inl.c_invoice_id WHERE i.docstatus NOT IN ('{$excludedStatusesSql}') AND inl.m_inoutline_id = il.m_inoutline_id), 0) > 0")
            ->select(
                'il.m_inoutline_id as id',
                'il.c_orderline_id',
                'il.movementqty as qty',
                DB::raw("(il.movementqty - COALESCE((SELECT SUM(inl.qtyinvoiced) FROM c_invoiceline inl JOIN c_invoice i ON i.c_invoice_id = inl.c_invoice_id WHERE i.docstatus NOT IN ('{$excludedStatusesSql}') AND inl.m_inoutline_id = il.m_inoutline_id), 0)) as remaining_qty"),
                'il.m_product_id',
                'io.documentno as shipment_no',
                'io.poreference',
                'io.c_bpartner_id as customer_id',
                'p.name as product_name',
                'p.value as product_value',
                'u.uomsymbol as uom_symbol',
                DB::raw('COALESCE(ol.priceactual, 0) as unit_price')
            );

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
                    'shipment_no' => $item->shipment_no,
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


    // API: Get Shipment Lines for linking
    public function getShipmentLinesLink(Request $request)
    {
        $arInvoiceConfig = config('idempiere.ar-invoice');
        $search = $request->get('q', '');
        $perPage = $arInvoiceConfig['limits']['shipment_link_per_page'];
        $page = $request->get('page', 1);
        $clientId = Session::get('idempiere_client');
        $customerId = $request->get('customer_id');

        if (strlen($search) < $arInvoiceConfig['limits']['lookup_min_search_length']) {
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
            ->where('io.docstatus', $arInvoiceConfig['shipment_link_filters']['doc_status'])
            ->where('io.movementtype', $arInvoiceConfig['shipment_link_filters']['movement_type'])
            ->where('io.issotrx', $arInvoiceConfig['shipment_link_filters']['is_so_trx'])
            ->where(function ($q) use ($search) {
                $q->where('io.documentno', 'ilike', "%{$search}%")
                    ->orWhere('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });

        if ($customerId) {
            $query->where('io.c_bpartner_id', (int) $customerId);
        }

        $results = $query->select(
            'il.m_inoutline_id',
            'il.c_orderline_id',
            'il.movementqty as qty',
            'il.m_product_id',
            'io.documentno as shipment_no',
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
                    'text' => $item->shipment_no . ' - ' . $item->product_code . ' - ' . $item->product_name . ' (Qty: ' . $item->qty . ')',
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
        $arInvoiceConfig = config('idempiere.ar-invoice');
        $search = $request->get('q', '');
        $perPage = $arInvoiceConfig['limits']['products_per_page'];
        $page = $request->get('page', 1);
        $clientId = Session::get('idempiere_client');

        $query = DB::connection('idempiere')
            ->table('m_product as p')
            ->leftJoin('c_uom as u', 'u.c_uom_id', '=', 'p.c_uom_id')
            ->where('p.ad_client_id', $clientId)
            ->where('p.isactive', 'Y')
            ->where('p.ispurchased', $arInvoiceConfig['filters']['product_is_purchased']);

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

    // API: Get Customer Contacts
    public function getCustomerContacts(Request $request)
    {
        $customerId = $request->get('customer_id');

        if (!$customerId) {
            return response()->json([
                'results' => [],
            ]);
        }

        $contacts = DB::connection('idempiere')
            ->table('ad_user as u')
            ->where('u.c_bpartner_id', (int) $customerId)
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

            // Fetch Customer
            $customer = DB::connection('idempiere')
                ->table('c_bpartner as bp')
                ->where('bp.c_bpartner_id', $invoice->c_bpartner_id)
                ->select('bp.name as customer_name')
                ->first();

            // Fetch PO DocumentNo if linked
            $poDocumentNo = null;
            if ($invoice->c_order_id) {
                $poDocumentNo = DB::connection('idempiere')
                    ->table('c_order')
                    ->where('c_order_id', $invoice->c_order_id)
                    ->value('documentno');
            }

            // Fetch Client Name
            $clientName = DB::connection('idempiere')
                ->table('ad_client')
                ->where('ad_client_id', $invoice->ad_client_id)
                ->value('name');

            // Fetch Org address via c_bpartner -> c_bpartner_location -> c_location
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
                Log::warning('AR Invoice Org info fetch warning: ' . $e->getMessage());
            }

            // Fetch Payment Term
            $paymentTerm = DB::connection('idempiere')
                ->table('c_paymentterm')
                ->where('c_paymentterm_id', $invoice->c_paymentterm_id)
                ->value('name');

            // Fetch Prepared By
            $preparedBy = DB::connection('idempiere')
                ->table('ad_user')
                ->where('ad_user_id', $invoice->createdby)
                ->value('name');

            // Fetch Contact Name if ad_user_id exists
            $contactName = null;
            if ($invoice->ad_user_id) {
                $contactName = DB::connection('idempiere')
                    ->table('ad_user')
                    ->where('ad_user_id', $invoice->ad_user_id)
                    ->value('name');
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
                Log::warning('AR Invoice Logo fetch warning: ' . $e->getMessage());
            }

            // Fallback to local logo
            if (!$logoBase64) {
                $logoPath = public_path('assets/media/logos/logo-long.png');
                if (file_exists($logoPath)) {
                    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($logoPath));
                }
            }

            // Calculate Grand Total spelled-out words
            $grandTotal = $invoice->grandtotal ?? 0;
            $grandTotalWords = \App\Http\Controllers\HelperController::numberToWordsEnglish($grandTotal);

            $pdf = Pdf::loadView('pages.ar-invoice.pdf', [
                'invoice' => $invoice,
                'customer' => $customer,
                'poDocumentNo' => $poDocumentNo,
                'paymentTerm' => $paymentTerm,
                'preparedBy' => $preparedBy,
                'contactName' => $contactName,
                'logoBase64' => $logoBase64,
                'grandTotalWords' => $grandTotalWords,
                'clientName' => $clientName,
                'orgInfo' => $orgInfo,
            ])->setOptions(['isRemoteEnabled' => true]);

            $filename = 'AR-Invoice-' . str_replace(['/', '\\'], '-', $invoice->documentno) . '.pdf';
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('AR Invoice Print Error: ' . $e->getMessage());
            abort(500, 'Error generating PDF');
        }
    }

    private function getStatusLabel(?string $status): string
    {
        $map = [
            'DR' => 'Draft',
            'IP' => 'In Progress',
            'CO' => 'Completed',
            'CL' => 'Closed',
            'VO' => 'Voided',
            'RE' => 'Reversed',
            'NA' => 'Not Approved',
            'IN' => 'Invalid',
            'AP' => 'Approved',
        ];
        return $map[$status] ?? ($status ?? 'Unknown');
    }
}
