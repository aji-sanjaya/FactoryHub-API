<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use App\Models\Idempiere\CPayment;
use App\Models\Idempiere\CPaymentAllocate;

class ArReceiptController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    public function index()
    {
        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        $arReceiptConfig = config('idempiere.ar-receipt');

        if (request()->has('document_id')) {
            return $this->showForm(request('document_id'));
        }

        $perPage = request()->get('per_page', $arReceiptConfig['limits']['per_page']);
        $status = request()->get('status', $arReceiptConfig['statuses']['default_list']);
        $search = request()->get('search', '');
        $dateStart = request()->get('date_start', '');
        $dateEnd = request()->get('date_end', '');

        $clientId = Session::get('idempiere_client');

        $query = CPayment::where('c_payment.ad_client_id', $clientId)
            ->where('c_payment.isreceipt', $arReceiptConfig['defaults']['is_receipt'])
            ->where('c_payment.isactive', $arReceiptConfig['defaults']['active_flag'])
            ->where('c_payment.c_doctype_id', $arReceiptConfig['doc_types']['receipt']);

        $query->join('c_doctype as dt', 'dt.c_doctype_id', '=', 'c_payment.c_doctype_id')
            ->where('dt.docbasetype', $arReceiptConfig['doc_types']['base_type'])
            ->select('c_payment.*');

        if ($status !== 'all' && !empty($status)) {
            $statusArray = is_array($status) ? $status : explode(',', $status);
            $query->whereIn('c_payment.docstatus', $statusArray);
        }
        if ($search) {
            $query->where('c_payment.documentno', 'ilike', "%{$search}%");
        }
        if ($dateStart) {
            $query->whereDate('c_payment.datetrx', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->whereDate('c_payment.datetrx', '<=', $dateEnd);
        }

        $query->orderBy('c_payment.created', 'desc');
        $payments = $query->paginate($perPage);

        $baseQuery = CPayment::where('c_payment.ad_client_id', $clientId)
            ->where('c_payment.isreceipt', $arReceiptConfig['defaults']['is_receipt'])
            ->where('c_payment.isactive', $arReceiptConfig['defaults']['active_flag'])
            ->where('c_payment.c_doctype_id', $arReceiptConfig['doc_types']['receipt'])
            ->join('c_doctype as dt', 'dt.c_doctype_id', '=', 'c_payment.c_doctype_id')
            ->where('dt.docbasetype', $arReceiptConfig['doc_types']['base_type']);

        $countAll = (clone $baseQuery)->count();
        $countDraft = (clone $baseQuery)->where('c_payment.docstatus', $arReceiptConfig['statuses']['draft'])->count();
        $countInProgress = (clone $baseQuery)->where('c_payment.docstatus', $arReceiptConfig['statuses']['in_progress'])->count();
        $countCompleted = (clone $baseQuery)->whereIn('c_payment.docstatus', $arReceiptConfig['statuses']['completed'])->count();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.ar-receipt.payment-table', [
                    'payments' => $payments,
                ])->render(),
            ]);
        }

        return view('pages.ar-receipt.index', [
            'title' => 'AR Receipt',
            'payments' => $payments,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted,
        ]);
    }

    private function showForm($docId)
    {
        $arReceiptConfig = config('idempiere.ar-receipt');
        $payment = null;
        if ($docId !== 'new') {
            try {
                $decryptedId = Crypt::decryptString($docId);
                $payment = CPayment::findOrFail($decryptedId);
            } catch (\Exception $e) {
                return redirect()->route('ar-receipt.index')->with('error', 'Invalid Link');
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

        $currentOrgId = $payment ? $payment->ad_org_id : (count($organizations) > 0 ? $organizations[0]->id : null);

        // Customers
        $customers = DB::connection('idempiere')->select("
            SELECT c_bpartner_id AS id, name AS text
            FROM c_bpartner
            WHERE isactive = 'Y' AND ad_client_id = ? AND iscustomer=?
            ORDER BY name LIMIT ?
        ", [$clientId, $arReceiptConfig['filters']['is_customer'], $arReceiptConfig['limits']['customer_search']]);

        // Doc Types for AR Receipt
        $docTypes = DB::connection('idempiere')->select("
            SELECT dt.c_doctype_id AS id, dt.name AS text
            FROM c_doctype dt
            WHERE dt.isactive = 'Y'
            AND dt.docbasetype = ?
            AND dt.ad_client_id = ?
            ORDER BY dt.c_doctype_id ASC
        ", [$arReceiptConfig['doc_types']['base_type'], $clientId]);

        // Currencies
        $currencies = DB::connection('idempiere')->select("
            SELECT c_currency_id AS id, iso_code AS text
            FROM c_currency
            WHERE isactive = 'Y'
            ORDER BY iso_code
        ");

        // Bank Accounts
        $bankAccounts = DB::connection('idempiere')->select("
            SELECT ba.c_bankaccount_id AS id,
                   b.name || ' - ' || ba.accountno AS text,
                   ba.accountno,
                   ba.c_currency_id
            FROM c_bankaccount ba
            JOIN c_bank b ON b.c_bank_id = ba.c_bank_id
            WHERE ba.ad_client_id = ? AND ba.isactive = 'Y'
            ORDER BY b.name, ba.accountno
        ", [$clientId]);

        // Payment Rules (Tender Types)
        $paymentRules = $arReceiptConfig['payment_rules'];

        // Customer Contacts
        $customerContacts = [];
        if ($payment && $payment->c_bpartner_id) {
            $customerContacts = DB::connection('idempiere')->select("
                SELECT u.ad_user_id AS id, u.name AS text
                FROM ad_user u
                WHERE u.c_bpartner_id = ? AND u.isactive = 'Y'
                ORDER BY u.name
            ", [$payment->c_bpartner_id]);
        }

        $statusLabel = $payment ? $this->getStatusLabel($payment->docstatus) : 'Draft';

        // Check Active Workflow
        $hasActiveWorkflow = false;
        if ($payment) {
            $hasActiveWorkflow = DB::connection('idempiere')
                ->table('ad_wf_activity')
                ->join('ad_table', 'ad_table.ad_table_id', '=', 'ad_wf_activity.ad_table_id')
                ->where('ad_table.tablename', $arReceiptConfig['workflow']['table_name'])
                ->where('ad_wf_activity.record_id', $payment->c_payment_id)
                ->where('ad_wf_activity.processed', 'N')
                ->exists();
        }

        $defaultDocTypeId = count($docTypes) > 0 ? $docTypes[0]->id : null;

        // Default currency (IDR)
        $defaultCurrencyId = null;
        $idrCurrency = DB::connection('idempiere')
            ->table('c_currency')
            ->where('iso_code', $arReceiptConfig['defaults']['currency_iso_code'])
            ->where('isactive', 'Y')
            ->first();
        if ($idrCurrency) {
            $defaultCurrencyId = $idrCurrency->c_currency_id;
        } elseif (count($currencies) > 0) {
            $defaultCurrencyId = $currencies[0]->id;
        }

        $viewData = [
            'title' => $docId === 'new' ? 'Create AR Receipt' : 'Edit AR Receipt',
            'payment' => $payment,
            'organizations' => $organizations,
            'customers' => $customers,
            'customerContacts' => $customerContacts,
            'clientName' => $clientName,
            'docTypes' => $docTypes,
            'currencies' => $currencies,
            'bankAccounts' => $bankAccounts,
            'paymentRules' => $paymentRules,
            'isNew' => is_null($payment),
            'docNo' => $payment ? $payment->documentno : '** New **',
            'status' => $statusLabel,
            'currentOrgId' => $currentOrgId,
            'paymentDate' => $payment && $payment->datetrx
                ? \Carbon\Carbon::parse($payment->datetrx)->format('Y-m-d')
                : date('Y-m-d'),
            'dateAcct' => $payment && $payment->dateacct
                ? \Carbon\Carbon::parse($payment->dateacct)->format('Y-m-d')
                : date('Y-m-d'),
            'docIdParam' => request('document_id'),
            'isReadOnly' => $payment && in_array($payment->docstatus, $arReceiptConfig['statuses']['read_only']),
            'isDraft' => $payment && $payment->docstatus === $arReceiptConfig['statuses']['draft'],
            'activeTab' => request('tab', 'header'),
            'hasActiveWorkflow' => $hasActiveWorkflow,
            'docTypeId' => $payment ? $payment->c_doctype_id : $defaultDocTypeId,
            'currencyId' => $payment ? $payment->c_currency_id : $defaultCurrencyId,
            'payAmt' => $payment ? $payment->payamt : 0,
            'paymentRule' => $payment ? ($payment->tendertype ?? $payment->paymentrule ?? $arReceiptConfig['defaults']['payment_rule']) : $arReceiptConfig['defaults']['payment_rule'],
            'bankAccountId' => $payment ? $payment->c_bankaccount_id : null,
        ];

        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');
            if ($tab === 'header')
                return view('pages.ar-receipt.partials.tab-header', $viewData);
            if ($tab === 'attachments') {
                $attachments = [];
                if (isset($payment)) {
                    try {
                        $url = "models/c_payment/{$payment->c_payment_id}/attachments";
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
                        Log::error("AR Receipt: Failed to fetch attachments: " . $e->getMessage());
                        $attachments = [];
                    }
                }
                $viewData['attachments'] = $attachments;
                return view('pages.ar-receipt.partials.tab-attachments', $viewData);
            }
            if ($tab === 'allocate') {
                $allocations = collect();
                if (isset($payment)) {
                    $allocations = DB::connection('idempiere')
                        ->table('c_paymentallocate as pa')
                        ->leftJoin('c_invoice as i', 'i.c_invoice_id', '=', 'pa.c_invoice_id')
                        ->select(
                            'pa.c_paymentallocate_id',
                            'pa.c_invoice_id',
                            'pa.amount',
                            'pa.discountamt',
                            'pa.writeoffamt',
                            'pa.overunderamt',
                            'i.documentno as invoice_documentno',
                            'i.dateinvoiced',
                            'i.grandtotal as invoice_grandtotal'
                        )
                        ->where('pa.c_payment_id', $payment->c_payment_id)
                        ->where('pa.isactive', 'Y')
                        ->orderBy('pa.c_paymentallocate_id', 'desc')
                        ->get();
                }
                $viewData['allocations'] = $allocations;
                return view('pages.ar-receipt.partials.tab-allocate', $viewData);
            }
            if ($tab === 'journals') {
                $journals = collect();
                if (isset($payment)) {
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
                        ->where('fa.ad_table_id', $arReceiptConfig['journals']['table_id'])
                        ->where('fa.record_id', $payment->c_payment_id)
                        ->orderBy('fa.fact_acct_id')
                        ->paginate($arReceiptConfig['limits']['journals_per_page'])
                        ->appends(['ajax_tab' => 'journals']);
                }
                $viewData['journals'] = $journals;
                return view('pages.ar-receipt.partials.tab-journals', $viewData);
            }
        }

        return view('pages.ar-receipt.form', $viewData);
    }

    public function create()
    {
        return redirect()->route('ar-receipt.index', ['document_id' => 'new']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'org_id' => 'required',
            'c_bpartner_id' => 'required',
            'payment_date' => 'required|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'doc_type_id' => 'required',
            'c_currency_id' => 'required',
            'payment_rule' => 'required',
            'c_bankaccount_id' => 'nullable',
            'pay_amt' => 'required|numeric|min:0',
            'description' => 'nullable|string',
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

        $payload = [
            'AD_Client_ID' => (int) $sessionClientId,
            'AD_Org_ID' => (int) $validated['org_id'],
            'C_DocType_ID' => (int) $validated['doc_type_id'],
            'DateTrx' => $validated['payment_date'],
            'DateAcct' => $validated['date_acct'] ?? $validated['payment_date'],
            'IsReceipt' => config('idempiere.ar-receipt.defaults.is_receipt'),
            'C_BPartner_ID' => $bpartnerId,
            'C_Currency_ID' => (int) $validated['c_currency_id'],
            'TenderType' => $validated['payment_rule'],
            'PayAmt' => (float) $validated['pay_amt'],
            'Description' => $validated['description'] ?? null,
        ];

        if (!empty($validated['c_bankaccount_id'])) {
            $payload['C_BankAccount_ID'] = (int) $validated['c_bankaccount_id'];
        }

        Log::info('AR Receipt Create Payload:', $payload);

        try {
            $response = $this->idempiereService->post('models/c_payment', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['C_Payment_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'AR Receipt created successfully',
                    'data' => [
                        'c_payment_id' => $id,
                        'encrypted_id' => Crypt::encryptString($id),
                    ]
                ]);
            } elseif ($response->status() === 401) {
                return response()->json(['message' => 'Session expired. Please logout and login again.'], 401);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('AR Receipt Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create AR Receipt: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'org_id' => 'nullable',
            'c_bpartner_id' => 'nullable',
            'payment_date' => 'nullable|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'doc_type_id' => 'nullable',
            'c_currency_id' => 'nullable',
            'payment_rule' => 'nullable',
            'c_bankaccount_id' => 'nullable',
            'pay_amt' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $payload = [];
        if (!empty($validated['org_id']))
            $payload['AD_Org_ID'] = (int) $validated['org_id'];
        if (!empty($validated['c_bpartner_id']))
            $payload['C_BPartner_ID'] = (int) $validated['c_bpartner_id'];
        if (!empty($validated['payment_date']))
            $payload['DateTrx'] = $validated['payment_date'];
        if (!empty($validated['date_acct']))
            $payload['DateAcct'] = $validated['date_acct'];
        if (!empty($validated['doc_type_id']))
            $payload['C_DocType_ID'] = (int) $validated['doc_type_id'];
        if (!empty($validated['c_currency_id']))
            $payload['C_Currency_ID'] = (int) $validated['c_currency_id'];
        if (!empty($validated['payment_rule']))
            $payload['TenderType'] = $validated['payment_rule'];
        if (isset($validated['pay_amt']))
            $payload['PayAmt'] = (float) $validated['pay_amt'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        if (!empty($validated['c_bankaccount_id']))
            $payload['C_BankAccount_ID'] = (int) $validated['c_bankaccount_id'];

        try {
            $response = $this->idempiereService->put("models/c_payment/{$id}", $payload);
            if ($response->successful()) {
                return response()->json(['message' => 'AR Receipt updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('AR Receipt Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update: ' . $e->getMessage()], 500);
        }
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:CO,VO,CL,RC',
        ]);

        try {
            $paymentId = Crypt::decryptString($validated['document_id']);
            $payload = ['doc-action' => $validated['doc_action']];

            Log::info('Processing AR Receipt', [
                'payment_id' => $paymentId,
                'action' => $validated['doc_action']
            ]);

            $response = $this->idempiereService->put("models/c_payment/{$paymentId}", $payload);

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
            Log::error('AR Receipt Process Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate(['document_id' => 'required']);
        $draftStatus = config('idempiere.ar-receipt.statuses.draft');

        try {
            $paymentId = Crypt::decryptString($validated['document_id']);

            $payment = DB::connection('idempiere')
                ->table('c_payment')
                ->where('c_payment_id', $paymentId)
                ->select('docstatus', 'documentno')
                ->first();

            if (!$payment) {
                return response()->json(['message' => 'AR Receipt not found.'], 404);
            }
            if ($payment->docstatus !== $draftStatus) {
                return response()->json(['message' => 'Only Draft payments can be deleted.'], 422);
            }

            $response = $this->idempiereService->delete("models/c_payment/{$paymentId}");

            if (!$response->successful()) {
                $errorBody = $response->json('detail') ?? $response->body();
                return response()->json(['message' => 'Failed to delete: ' . $errorBody], $response->status() ?: 500);
            }

            return response()->json(['message' => 'AR Receipt ' . $payment->documentno . ' deleted successfully.']);

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID.'], 400);
        } catch (\Exception $e) {
            Log::error('AR Receipt Delete Error: ' . $e->getMessage());
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
                "models/c_payment/{$docId}/attachments",
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
            $response = $this->idempiereService->delete("models/c_payment/{$docId}/attachments/{$encodedId}");

            if ($response->successful()) {
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => 'Delete failed: ' . $response->body()], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function getCustomerContacts(Request $request)
    {
        $customerId = $request->get('customer_id');
        $clientId = Session::get('idempiere_client');

        if (!$customerId) {
            return response()->json(['results' => []]);
        }

        $contacts = DB::connection('idempiere')->select("
            SELECT u.ad_user_id AS id, u.name AS text
            FROM ad_user u
            WHERE u.c_bpartner_id = ? AND u.isactive = 'Y'
            AND u.ad_client_id = ?
            ORDER BY u.ad_user_id ASC
        ", [$customerId, $clientId]);

        return response()->json(['results' => $contacts]);
    }

    public function getOpenInvoices(Request $request)
    {
        try {
            $arReceiptConfig = config('idempiere.ar-receipt');
            $docId = Crypt::decryptString($request->document_id);
            $payment = CPayment::findOrFail($docId);
            $customerId = $payment->c_bpartner_id;
            $currencyId = $payment->c_currency_id;

            if (!$customerId) {
                return response()->json(['data' => [], 'total' => 0]);
            }

            $search = $request->get('q', '');
            $perPage = $request->get('per_page', $arReceiptConfig['limits']['open_invoices_per_page']);
            $excludedStatuses = $arReceiptConfig['filters']['excluded_allocation_statuses'];
            $excludedStatusesSql = implode("','", array_map('addslashes', $excludedStatuses));

            $query = DB::connection('idempiere')
                ->table('c_invoice')
                ->selectRaw("
                    c_invoice.c_invoice_id,
                    c_invoice.documentno,
                    c_invoice.dateinvoiced,
                    c_invoice.grandtotal,
                    c_invoice.docstatus,
                    invoiceopen(c_invoice.c_invoice_id, 0) as invoice_open_amt,
                    COALESCE(SUM(CASE WHEN p.docstatus NOT IN ('{$excludedStatusesSql}') THEN pa.amount + pa.discountamt + pa.writeoffamt ELSE 0 END), 0) as allocated_by_this_payment,
                    (invoiceopen(c_invoice.c_invoice_id, 0) - COALESCE(SUM(CASE WHEN p.docstatus NOT IN ('{$excludedStatusesSql}') THEN pa.amount + pa.discountamt + pa.writeoffamt ELSE 0 END), 0)) as open_amt
                ")
                ->leftJoin('c_paymentallocate as pa', function($join) use ($docId) {
                    $join->on('pa.c_invoice_id', '=', 'c_invoice.c_invoice_id')
                         ->where('pa.c_payment_id', '=', $docId)
                         ->where('pa.isactive', '=', 'Y');
                })
                ->leftJoin('c_payment as p', 'p.c_payment_id', '=', 'pa.c_payment_id')
                ->where('c_invoice.c_bpartner_id', $customerId)
                ->where('c_invoice.c_currency_id', $currencyId)
                ->where('c_invoice.issotrx', $arReceiptConfig['filters']['invoice_is_so_trx'])
                ->whereIn('c_invoice.docstatus', $arReceiptConfig['filters']['invoice_doc_statuses'])
                ->where('c_invoice.isactive', 'Y')
                ->groupBy(
                    'c_invoice.c_invoice_id',
                    'c_invoice.documentno',
                    'c_invoice.dateinvoiced',
                    'c_invoice.grandtotal',
                    'c_invoice.docstatus'
                )
                ->havingRaw("(invoiceopen(c_invoice.c_invoice_id, 0) - COALESCE(SUM(CASE WHEN p.docstatus NOT IN ('{$excludedStatusesSql}') THEN pa.amount + pa.discountamt + pa.writeoffamt ELSE 0 END), 0)) > 0");

            if ($search) {
                $query->where('c_invoice.documentno', 'ilike', "%{$search}%");
            }

            $invoices = $query->orderBy('c_invoice.dateinvoiced', 'asc')
                ->paginate($perPage);

            return response()->json($invoices);
        } catch (\Exception $e) {
            Log::error('AR Receipt Get Open Invoices Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching open invoices.'], 500);
        }
    }

    public function storeAllocation(Request $request)
    {
        $request->validate([
            'document_id' => 'required',
            'c_invoice_id' => 'required|integer',
            'invoice_amt' => 'required|numeric',
            'amount' => 'required|numeric',
            'discount_amt' => 'nullable|numeric',
            'writeoff_amt' => 'nullable|numeric',
            'overunder_amt' => 'nullable|numeric',
        ]);

        try {
            $paymentId = Crypt::decryptString($request->document_id);
            $payment = CPayment::findOrFail($paymentId);
            $clientId = Session::get('idempiere_client');
            
            $userData = Session::get('user_data');
            $sessionUserId = null;

            if (is_array($userData)) {
                $sessionUserId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null;
            } elseif (is_object($userData)) {
                $sessionUserId = $userData->userId ?? $userData->id ?? $userData->ad_user_id ?? null;
            }

            if (!$sessionUserId || !$clientId) {
                return response()->json(['message' => 'User Context Not Found. Please re-login.'], 401);
            }

            // Create Payment Allocate using C_PaymentAllocate model
            $payload = [
                'AD_Client_ID' => (int) $clientId,
                'AD_Org_ID' => (int) $payment->ad_org_id,
                'C_Payment_ID' => (int) $paymentId,
                'C_Invoice_ID' => (int) $request->c_invoice_id,
                'InvoiceAmt' => (float) $request->invoice_amt,
                'Amount' => (float) $request->amount,
                'DiscountAmt' => (float) ($request->discount_amt ?? 0),
                'WriteOffAmt' => (float) ($request->writeoff_amt ?? 0),
                'OverUnderAmt' => (float) ($request->overunder_amt ?? 0),
            ];

            Log::info('AR Receipt Allocate Payload:', $payload);

            $response = $this->idempiereService->post('models/c_paymentallocate', $payload);

            if (!$response->successful()) {
                Log::error('AR Receipt Allocate Error Response:', ['body' => $response->body()]);
                return response()->json(['message' => 'Failed to create payment allocation: ' . $response->body()], 400);
            }

            $this->syncHeaderPayAmt($paymentId);

            return response()->json(['message' => 'Invoice allocated successfully.']);

        } catch (\Exception $e) {
            Log::error('AR Receipt Store Allocation Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error saving allocation: ' . $e->getMessage()], 500);
        }
    }

    public function destroyAllocation(Request $request)
    {
        $request->validate([
            'document_id' => 'required',
            'line_ids' => 'required|array|min:1',
        ]);

        try {
            $paymentId = Crypt::decryptString($request->document_id);
            $deletedCount = 0;
            $errors = [];

            foreach ($request->line_ids as $allocateId) {
                // Delete C_PaymentAllocate directly
                $response = $this->idempiereService->delete("models/c_paymentallocate/{$allocateId}");
                
                if ($response->successful()) {
                    $deletedCount++;
                } else {
                    $errors[] = "Failed to delete Payment Allocate {$allocateId}: " . $response->body();
                }
            }

            if ($deletedCount > 0) {
                $this->syncHeaderPayAmt($paymentId);
            }

            if (count($errors) > 0) {
                return response()->json([
                    'message' => "Unlinked {$deletedCount} allocations. Some failed: " . implode(' | ', $errors)
                ], 207);
            }

            return response()->json(['message' => 'Allocation(s) unlinked successfully.']);

        } catch (\Exception $e) {
            Log::error('AR Receipt Delete Allocation Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting allocation: ' . $e->getMessage()], 500);
        }
    }

    public function updateAllocation(Request $request, $allocateId)
    {
        $request->validate([
            'document_id' => 'required',
            'c_invoice_id' => 'required|integer',
            'invoice_amt' => 'required|numeric',
            'amount' => 'required|numeric',
            'discount_amt' => 'nullable|numeric',
            'writeoff_amt' => 'nullable|numeric',
            'overunder_amt' => 'nullable|numeric',
        ]);

        try {
            $paymentId = Crypt::decryptString($request->document_id);
            $payment = CPayment::findOrFail($paymentId);
            $clientId = Session::get('idempiere_client');
            
            $userData = Session::get('user_data');
            $sessionUserId = null;

            if (is_array($userData)) {
                $sessionUserId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? null;
            } elseif (is_object($userData)) {
                $sessionUserId = $userData->userId ?? $userData->id ?? $userData->ad_user_id ?? null;
            }

            if (!$sessionUserId || !$clientId) {
                return response()->json(['message' => 'User Context Not Found. Please re-login.'], 401);
            }

            // Update Payment Allocate using C_PaymentAllocate model
            $payload = [
                'AD_Client_ID' => (int) $clientId,
                'AD_Org_ID' => (int) $payment->ad_org_id,
                'C_Payment_ID' => (int) $paymentId,
                'C_Invoice_ID' => (int) $request->c_invoice_id,
                'InvoiceAmt' => (float) $request->invoice_amt,
                'Amount' => (float) $request->amount,
                'DiscountAmt' => (float) ($request->discount_amt ?? 0),
                'WriteOffAmt' => (float) ($request->writeoff_amt ?? 0),
                'OverUnderAmt' => (float) ($request->overunder_amt ?? 0),
            ];

            Log::info('AR Receipt Update Allocate Payload:', ['id' => $allocateId, 'payload' => $payload]);

            $response = $this->idempiereService->put("models/c_paymentallocate/{$allocateId}", $payload);

            if (!$response->successful()) {
                Log::error('AR Receipt Update Allocate Error Response:', ['body' => $response->body()]);
                return response()->json(['message' => 'Failed to update payment allocation: ' . $response->body()], 400);
            }

            $this->syncHeaderPayAmt($paymentId);

            return response()->json(['message' => 'Allocation updated successfully.']);

        } catch (\Exception $e) {
            Log::error('AR Receipt Update Allocation Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating allocation: ' . $e->getMessage()], 500);
        }
    }
    public function viewAttachment($document_id, $file_name)
    {
        try {
            $docId = Crypt::decryptString($document_id);
            $encodedFileName = rawurlencode($file_name);

            $url = "models/c_payment/{$docId}/attachments/{$encodedFileName}";
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
    private function syncHeaderPayAmt($paymentId)
    {
        try {
            $totalAllocated = DB::connection('idempiere')
                ->table('c_paymentallocate')
                ->where('c_payment_id', $paymentId)
                ->where('isactive', 'Y')
                ->sum('amount');

            $this->idempiereService->put("models/c_payment/{$paymentId}", [
                'PayAmt' => (float) $totalAllocated
            ]);
        } catch (\Exception $e) {
            Log::error('AR Receipt Sync Header PayAmt Error: ' . $e->getMessage());
        }
    }

    private function getStatusLabel($docstatus)
    {
        $map = config('idempiere.ar-receipt.statuses.labels', []);
        return $map[$docstatus] ?? $docstatus;
    }
}
