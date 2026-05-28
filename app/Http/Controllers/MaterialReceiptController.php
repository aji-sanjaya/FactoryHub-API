<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use App\Models\Idempiere\MInOut;
use App\Models\Idempiere\MInOutLine;
use Barryvdh\DomPDF\Facade\Pdf;

class MaterialReceiptController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    public function index()
    {
        $materialReceiptConfig = config('idempiere.create-gr');

        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        if (request()->has('document_id')) {
            return $this->showForm(request('document_id'));
        }

        $perPage = (int) request()->get('per_page', $materialReceiptConfig['limits']['list_per_page']);
        $page = request()->get('page', 1);
        $status = request()->get('status', 'all');
        $search = request()->get('search', '');

        $clientId = Session::get('idempiere_client');

        $query = MInOut::where('ad_client_id', $clientId)
            ->where('movementtype', $materialReceiptConfig['defaults']['movement_type'])
            ->where('issotrx', $materialReceiptConfig['defaults']['is_so_trx'])
            ->where('isactive', 'Y');

        if ($status !== 'all') {
            $query->where('docstatus', $status);
        }
        if ($search) {
            $query->where('documentno', 'ilike', "%{$search}%");
        }

        $query->orderBy('created', 'desc');
        $receipts = $query->paginate($perPage);

        $countAll = MInOut::where('ad_client_id', $clientId)->where('movementtype', $materialReceiptConfig['defaults']['movement_type'])->where('issotrx', $materialReceiptConfig['defaults']['is_so_trx'])->where('isactive', 'Y')->count();
        $countDraft = MInOut::where('ad_client_id', $clientId)->where('movementtype', $materialReceiptConfig['defaults']['movement_type'])->where('issotrx', $materialReceiptConfig['defaults']['is_so_trx'])->where('isactive', 'Y')->whereIn('docstatus', $materialReceiptConfig['statuses']['draft'])->count();
        $countInProgress = MInOut::where('ad_client_id', $clientId)->where('movementtype', $materialReceiptConfig['defaults']['movement_type'])->where('issotrx', $materialReceiptConfig['defaults']['is_so_trx'])->where('isactive', 'Y')->whereIn('docstatus', $materialReceiptConfig['statuses']['in_progress'])->count();
        $countCompleted = MInOut::where('ad_client_id', $clientId)->where('movementtype', $materialReceiptConfig['defaults']['movement_type'])->where('issotrx', $materialReceiptConfig['defaults']['is_so_trx'])->where('isactive', 'Y')->whereIn('docstatus', $materialReceiptConfig['statuses']['completed'])->count();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.material-receipt.receipt-table', [
                    'receipts' => $receipts,
                ])->render(),
            ]);
        }

        return view('pages.material-receipt.index', [
            'title' => 'Material Receipt',
            'receipts' => $receipts,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted,
        ]);
    }

    private function showForm($docId)
    {
        $materialReceiptConfig = config('idempiere.create-gr');

        $receipt = null;
        if ($docId !== 'new') {
            try {
                $decryptedId = Crypt::decryptString($docId);
                $receipt = MInOut::findOrFail($decryptedId);
            } catch (\Exception $e) {
                return redirect()->route('material-receipt.index')->with('error', 'Invalid Link');
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

        $currentOrgId = $receipt ? $receipt->ad_org_id : (count($organizations) > 0 ? $organizations[0]->id : null);

        // Warehouses
        $warehouses = DB::connection('idempiere')->select("
            SELECT DISTINCT w.m_warehouse_id AS id, w.name AS text
            FROM m_warehouse w
            WHERE w.isactive = 'Y' AND w.ad_org_id = ?
            ORDER BY w.name
        ", [$currentOrgId]);

        // Vendors
        $vendors = DB::connection('idempiere')->select("
            SELECT c_bpartner_id AS id, name AS text
            FROM c_bpartner
            WHERE isactive = 'Y' AND ad_client_id = ? AND isvendor='Y'
            ORDER BY name LIMIT {$materialReceiptConfig['limits']['vendor_search']}
        ", [$clientId]);

        // DocTypes for Material Receipt (MMR = Material Movement Receipt)
        $docTypes = DB::connection('idempiere')->select("
            SELECT dt.c_doctype_id AS id, dt.name AS text
            FROM c_doctype dt
            WHERE dt.isactive = 'Y'
            AND dt.docbasetype = ?
            AND dt.ad_client_id = ?
            AND dt.ad_org_id IN (0, ?)
            ORDER BY dt.c_doctype_id DESC
        ", [
            $materialReceiptConfig['doc_types']['base_type'],
            $clientId,
            $clientId,
        ]);

        // Projects
        $projects = DB::connection('idempiere')->select("
            SELECT c_project_id AS id, value || ' - ' || name AS text
            FROM c_project
            WHERE ad_client_id = ? AND isactive='Y' AND issummary='N'
            ORDER BY value
        ", [$clientId]);

        $users = DB::connection('idempiere')->select("
            SELECT ad_user_id AS id, name AS text
            FROM ad_user
            WHERE ad_client_id = ? AND isactive = 'Y'
            ORDER BY name
        ", [$clientId]);

        // Lines
        if ($receipt) {
            $linePerPage = $materialReceiptConfig['limits']['line_default_per_page'];

            $lines = DB::connection('idempiere')
                ->table('m_inoutline as il')
                ->leftJoin('m_product as p', 'il.m_product_id', '=', 'p.m_product_id')
                ->leftJoin('c_uom as u', 'il.c_uom_id', '=', 'u.c_uom_id')
                ->leftJoin('c_orderline as ol', 'il.c_orderline_id', '=', 'ol.c_orderline_id')
                ->leftJoin('c_order as o', 'ol.c_order_id', '=', 'o.c_order_id')
                ->where('il.m_inout_id', $receipt->m_inout_id)
                ->select(
                    'il.m_inoutline_id',
                    'il.line',
                    'il.m_product_id',
                    'il.movementqty as qty',
                    'il.description',
                    'il.c_orderline_id',
                    'p.name as product_name',
                    'p.value as product_code',
                    'u.name as uom_name',
                    'u.uomsymbol as uom_symbol',
                    'o.documentno as po_documentno',
                    'ol.line as po_line'
                )
                ->orderBy('il.line')
                ->paginate($linePerPage);
        } else {
            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $materialReceiptConfig['limits']['line_default_per_page']);
        }

        $statusLabel = $receipt ? $this->getStatusLabel($receipt->docstatus) : $materialReceiptConfig['defaults']['document_status_label'];

        // Check Active Workflow
        $hasActiveWorkflow = false;
        if ($receipt) {
            $hasActiveWorkflow = DB::connection('idempiere')
                ->table('ad_wf_activity')
                ->join('ad_table', 'ad_table.ad_table_id', '=', 'ad_wf_activity.ad_table_id')
                ->where('ad_table.tablename', $materialReceiptConfig['workflow']['table_name'])
                ->where('ad_wf_activity.record_id', $receipt->m_inout_id)
                ->where('ad_wf_activity.processed', 'N')
                ->exists();
        }

        $viewData = [
            'title' => $docId === 'new' ? 'Create Material Receipt' : 'Edit Material Receipt',
            'receipt' => $receipt,
            'lines' => $lines,
            'organizations' => $organizations,
            'warehouses' => $warehouses,
            'vendors' => $vendors,
            'docTypes' => $docTypes,
            'projects' => $projects,
            'users' => $users,
            'tenantName' => $tenantName,
            'clientName' => $clientName,
            'isNew' => is_null($receipt),
            'docNo' => $receipt ? $receipt->documentno : '** New **',
            'status' => $statusLabel,
            'currentOrgId' => $currentOrgId,
            'movementDate' => $receipt && $receipt->movementdate
                ? \Illuminate\Support\Carbon::parse($receipt->movementdate)->format('Y-m-d')
                : date('Y-m-d'),
            'dateAcct' => $receipt && $receipt->dateacct
                ? \Illuminate\Support\Carbon::parse($receipt->dateacct)->format('Y-m-d')
                : date('Y-m-d'),
            'docIdParam' => request('document_id'),
            'isReadOnly' => $receipt && in_array($receipt->docstatus, $materialReceiptConfig['statuses']['read_only'], true),
            'isDraft' => $receipt && $receipt->docstatus === 'DR',
            'activeTab' => request('tab', 'header'),
            'hasActiveWorkflow' => $hasActiveWorkflow,
            'materialReceiptConfig' => $materialReceiptConfig,
        ];

        // docTypeId
        if ($receipt && isset($receipt->c_doctype_id)) {
            $viewData['docTypeId'] = $receipt->c_doctype_id;
        } else {
            $viewData['docTypeId'] = count($docTypes) > 0 ? $docTypes[0]->id : null;
        }

        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');
            if ($tab === 'header')
                return view('pages.material-receipt.partials.tab-header', $viewData);
            if ($tab === 'lines')
                return view('pages.material-receipt.partials.tab-lines', $viewData);
            if ($tab === 'attachments') {
                $attachments = [];
                if (isset($receipt)) {
                    try {
                        $url = "models/m_inout/{$receipt->m_inout_id}/attachments";
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
                        Log::error("GR: Failed to fetch attachments: " . $e->getMessage());
                        $attachments = [];
                    }
                }
                $viewData['attachments'] = $attachments;
                return view('pages.material-receipt.partials.tab-attachments', $viewData);
            }
            if ($tab === 'journals') {
                $journals = collect();
                if (isset($receipt)) {
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
                        ->where('fa.ad_table_id', $materialReceiptConfig['journals']['table_id'])
                        ->where('fa.record_id', $receipt->m_inout_id)
                        ->orderBy('fa.fact_acct_id')
                        ->paginate($materialReceiptConfig['limits']['journals_per_page'])
                        ->appends(['ajax_tab' => 'journals']);
                }
                $viewData['journals'] = $journals;
                return view('pages.material-receipt.partials.tab-journals', $viewData);
            }
        }

        return view('pages.material-receipt.form', $viewData);
    }

    public function create()
    {
        return redirect()->route('material-receipt.index', ['document_id' => 'new']);
    }

    public function printDocument()
    {
        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        $docId = request('document_id');
        if (!$docId || $docId === 'new') {
            abort(404, 'Document not found.');
        }

        try {
            $decryptedId = Crypt::decryptString($docId);
        } catch (\Exception $e) {
            abort(404, 'Invalid document link.');
        }

        $receipt = MInOut::findOrFail($decryptedId);

        // All lines, no pagination
        $lines = DB::connection('idempiere')
            ->table('m_inoutline as il')
            ->leftJoin('m_product as p', 'il.m_product_id', '=', 'p.m_product_id')
            ->leftJoin('c_uom as u', 'il.c_uom_id', '=', 'u.c_uom_id')
            ->where('il.m_inout_id', $receipt->m_inout_id)
            ->select(
                'il.m_inoutline_id',
                'il.line',
                'il.movementqty as qty',
                'il.description',
                'p.name as product_name',
                'u.name as uom_name',
                'u.uomsymbol as uom_symbol'
            )
            ->orderBy('il.line')
            ->get();

        // Vendor name + code
        $vendorName = '-';
        $vendorCode = '-';
        if ($receipt->c_bpartner_id) {
            $vendor = DB::connection('idempiere')
                ->table('c_bpartner')
                ->where('c_bpartner_id', $receipt->c_bpartner_id)
                ->select('name', 'value')
                ->first();
            if ($vendor) {
                $vendorName = $vendor->name;
                $vendorCode = $vendor->value;
            }
        }

        // Supplier DN No. (poreference)
        $supplierDNNo = $receipt->poreference ?? '-';

        // Warehouse name + address
        $warehouseName = '-';
        $warehouseAddress = null;
        if ($receipt->m_warehouse_id) {
            $wh = DB::connection('idempiere')
                ->table('m_warehouse')
                ->where('m_warehouse_id', $receipt->m_warehouse_id)
                ->select('name', 'description')
                ->first();
            if ($wh) {
                $warehouseName = $wh->name;
                $warehouseAddress = $wh->description ?: $wh->name;
            }
        }

        // Received by (created by user name)
        $receivedByName = '';
        if (isset($receipt->createdby)) {
            $adUser = DB::connection('idempiere')
                ->table('ad_user')
                ->where('ad_user_id', $receipt->createdby)
                ->select('name')
                ->first();
            if ($adUser) {
                $receivedByName = $adUser->name;
            }
        }

        // Updated by (last action / completed by user name)
        $updatedByName = '';
        if (isset($receipt->updatedby)) {
            $updatedByUser = DB::connection('idempiere')
                ->table('ad_user')
                ->where('ad_user_id', $receipt->updatedby)
                ->select('name')
                ->first();
            if ($updatedByUser) {
                $updatedByName = $updatedByUser->name;
            }
        }

        $checkedByLabel = 'QC Incomming';
        if (!empty($receipt->adw_ad_user_checked_id)) {
            $checkedByUser = DB::connection('idempiere')
                ->table('ad_user')
                ->where('ad_user_id', $receipt->adw_ad_user_checked_id)
                ->select('description', 'name')
                ->first();
            if ($checkedByUser) {
                $checkedByLabel = trim((string) ($checkedByUser->description ?: $checkedByUser->name)) ?: 'QC Incomming';
            }
        }

        $approvedByLabel = 'Purchasing';
        if (!empty($receipt->adw_ad_user_approved_id)) {
            $approvedByUser = DB::connection('idempiere')
                ->table('ad_user')
                ->where('ad_user_id', $receipt->adw_ad_user_approved_id)
                ->select('description', 'name')
                ->first();
            if ($approvedByUser) {
                $approvedByLabel = trim((string) ($approvedByUser->description ?: $approvedByUser->name)) ?: 'Purchasing';
            }
        }

        // QR Codes for LEGALIZATION footer

        // QC Incoming QR — adw_ad_user_checked_id (checked/step-1)
        $qcIncomingQr = null;
        if (!empty($receipt->adw_ad_user_checked_id)) {
            $checkedByName = DB::connection('idempiere')
                ->table('ad_user')
                ->where('ad_user_id', $receipt->adw_ad_user_checked_id)
                ->value('name');
            if ($receipt->adw_checked_isapproved === 'AP' && !empty($receipt->adw_checked_date)) {
                $qcIncomingQrData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate('Checked by ' . $checkedByName . ' on ' . $receipt->adw_checked_date);
                $qcIncomingQr = "data:image/svg+xml;base64," . base64_encode($qcIncomingQrData);
            }
        }

        // Purchasing QR — adw_ad_user_approved_id (approved/step-2)
        $purchasingQr = null;
        if (!empty($receipt->adw_ad_user_approved_id)) {
            $approvedByName = DB::connection('idempiere')
                ->table('ad_user')
                ->where('ad_user_id', $receipt->adw_ad_user_approved_id)
                ->value('name');
            if ($receipt->adw_approve_isapproved === 'AP' && !empty($receipt->adw_approved_date)) {
                $purchasingQrData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate('Approved by ' . $approvedByName . ' on ' . $receipt->adw_approved_date);
                $purchasingQr = "data:image/svg+xml;base64," . base64_encode($purchasingQrData);
            }
        }

        // User QR — show when document is Complete (CO)
        $userQr = null;
        if (strtoupper(trim((string) $receipt->docstatus)) === 'CO' && $updatedByName && !empty($receipt->updated)) {
            $userQrData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate('Completed by ' . $updatedByName . ' on ' . $receipt->updated);
            $userQr = "data:image/svg+xml;base64," . base64_encode($userQrData);
        }

        // Doc type code / name
        $docTypeCode = null;
        if (isset($receipt->c_doctype_id)) {
            $dt = DB::connection('idempiere')
                ->table('c_doctype')
                ->where('c_doctype_id', $receipt->c_doctype_id)
                ->select('name', 'printname')
                ->first();
            if ($dt) {
                $docTypeCode = $dt->printname ?: $dt->name;
            }
        }

        // Client Name
        $clientName = DB::connection('idempiere')
            ->table('ad_client')
            ->where('ad_client_id', $receipt->ad_client_id)
            ->value('name');

        // Org address via c_bpartner → c_bpartner_location → c_location
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
                ->select('locbp.address1', 'locbp.address2', 'locbp.address3', 'locbp.city', 'locbp.postal')
                ->first();
        } catch (\Exception $e) {
            // non-fatal
        }

        // Logo (iDempiere DB → fallback to local file)
        $logoBase64 = null;
        try {
            $clientInfo = DB::connection('idempiere')
                ->table('ad_clientinfo')
                ->where('ad_client_id', $receipt->ad_client_id)
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
            // non-fatal
        }

        if (!$logoBase64) {
            $logoPath = public_path('images/user/logo_nav.png');
            if (file_exists($logoPath)) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
        }

        $pdf = Pdf::loadView('pages.material-receipt.print', [
            'receipt' => $receipt,
            'lines' => $lines,
            'clientName' => $clientName,
            'orgInfo' => $orgInfo,
            'vendorName' => $vendorName,
            'vendorCode' => $vendorCode,
            'supplierDNNo' => $supplierDNNo,
            'warehouseName' => $warehouseName,
            'warehouseAddress' => $warehouseAddress,
            'receivedByName' => $receivedByName,
            'updatedByName' => $updatedByName,
            'checkedByLabel' => $checkedByLabel,
            'approvedByLabel' => $approvedByLabel,
            'purchasingQr' => $purchasingQr,
            'qcIncomingQr' => $qcIncomingQr,
            'userQr' => $userQr,
            'docTypeCode' => $docTypeCode,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'portrait')->setOptions(['isRemoteEnabled' => true]);

        $safeDocNo = str_replace(['/', '\\', ' '], ['-', '-', '_'], $receipt->documentno ?? 'document');
        $filename = 'GR_' . $safeDocNo . '.pdf';

        return $pdf->stream($filename);
    }

    public function store(Request $request)
    {
        $materialReceiptConfig = config('idempiere.create-gr');

        $validated = $request->validate([
            'org_id' => 'required',
            'warehouse_id' => 'required',
            'c_bpartner_id' => 'required',
            'movement_date' => 'required|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'doc_type_id' => 'required',
            'description' => 'nullable|string',
            'c_project_id' => 'nullable',
            'adw_ad_user_checked_id' => 'nullable',
            'adw_ad_user_approved_id' => 'nullable',
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

        $payload = [
            'AD_Client_ID' => (int) $sessionClientId,
            'AD_Org_ID' => (int) $validated['org_id'],
            'M_Warehouse_ID' => (int) $validated['warehouse_id'],
            'C_DocType_ID' => (int) $validated['doc_type_id'],
            'MovementDate' => $validated['movement_date'],
            'DateAcct' => $validated['date_acct'] ?? $validated['movement_date'],
            'MovementType' => $materialReceiptConfig['defaults']['movement_type'],
            'IsSOTrx' => $materialReceiptConfig['defaults']['is_so_trx'],
            'C_BPartner_ID' => $bpartnerId,
            'Description' => $validated['description'] ?? null,
            'SalesRep_ID' => (int) $sessionUserId,
        ];

        if ($bpartnerLocationId) {
            $payload['C_BPartner_Location_ID'] = (int) $bpartnerLocationId;
        }

        if (!empty($validated['c_project_id']))
            $payload['C_Project_ID'] = (int) $validated['c_project_id'];

        Log::info('GR Create Payload:', $payload);

        try {
            $response = $this->idempiereService->post('models/m_inout', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['M_InOut_ID'] ?? $data['recordID'] ?? null;

                if ($id && (!empty($validated['adw_ad_user_checked_id']) || !empty($validated['adw_ad_user_approved_id']))) {
                    DB::connection('idempiere')
                        ->table('m_inout')
                        ->where('m_inout_id', $id)
                        ->update([
                            'adw_ad_user_checked_id' => !empty($validated['adw_ad_user_checked_id']) ? (int) $validated['adw_ad_user_checked_id'] : null,
                            'adw_ad_user_approved_id' => !empty($validated['adw_ad_user_approved_id']) ? (int) $validated['adw_ad_user_approved_id'] : null,
                        ]);
                }

                return response()->json([
                    'message' => 'Material Receipt created successfully',
                    'data' => [
                        'm_inout_id' => $id,
                        'encrypted_id' => Crypt::encryptString($id),
                    ]
                ]);
            } elseif ($response->status() === 401) {
                return response()->json(['message' => 'Session expired. Please logout and login again to continue.'], 401);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('GR Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create GR: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'org_id' => 'nullable',
            'warehouse_id' => 'nullable',
            'c_bpartner_id' => 'nullable',
            'movement_date' => 'nullable|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'doc_type_id' => 'nullable',
            'description' => 'nullable|string',
            'c_project_id' => 'nullable',
            'adw_ad_user_checked_id' => 'nullable',
            'adw_ad_user_approved_id' => 'nullable',
        ]);

        $payload = [];
        if (!empty($validated['org_id']))
            $payload['AD_Org_ID'] = (int) $validated['org_id'];
        if (!empty($validated['warehouse_id']))
            $payload['M_Warehouse_ID'] = (int) $validated['warehouse_id'];
        if (!empty($validated['c_bpartner_id']))
            $payload['C_BPartner_ID'] = (int) $validated['c_bpartner_id'];
        if (!empty($validated['movement_date']))
            $payload['MovementDate'] = $validated['movement_date'];
        if (!empty($validated['date_acct']))
            $payload['DateAcct'] = $validated['date_acct'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        if (!empty($validated['doc_type_id']))
            $payload['C_DocType_ID'] = (int) $validated['doc_type_id'];
        if (!empty($validated['c_project_id']))
            $payload['C_Project_ID'] = (int) $validated['c_project_id'];

        try {
            $response = $this->idempiereService->put("models/m_inout/{$id}", $payload);
            if ($response->successful()) {
                if (array_key_exists('adw_ad_user_checked_id', $validated) || array_key_exists('adw_ad_user_approved_id', $validated)) {
                    DB::connection('idempiere')
                        ->table('m_inout')
                        ->where('m_inout_id', $id)
                        ->update([
                            'adw_ad_user_checked_id' => !empty($validated['adw_ad_user_checked_id']) ? (int) $validated['adw_ad_user_checked_id'] : null,
                            'adw_ad_user_approved_id' => !empty($validated['adw_ad_user_approved_id']) ? (int) $validated['adw_ad_user_approved_id'] : null,
                        ]);
                }

                return response()->json(['message' => 'Material Receipt updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('GR Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update GR: ' . $e->getMessage()], 500);
        }
    }

    public function storeLine(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'line_id' => 'nullable',
            'm_product_id' => 'required',
            'qty' => 'required|numeric|gt:0',
            'description' => 'nullable|string',
            'c_orderline_id' => 'nullable|numeric',
        ]);

        try {
            $inoutId = Crypt::decryptString($validated['document_id']);

            $userData = Session::get('user_data');
            $sessionClientId = Session::get('idempiere_client');

            $qty = (float) $validated['qty'];

            // Get default locator from warehouse
            $inout = DB::connection('idempiere')
                ->table('m_inout')
                ->where('m_inout_id', $inoutId)
                ->select('m_warehouse_id', 'ad_org_id')
                ->first();

            $locatorId = null;
            if ($inout) {
                $locator = DB::connection('idempiere')
                    ->table('m_locator')
                    ->where('m_warehouse_id', $inout->m_warehouse_id)
                    ->where('isactive', 'Y')
                    ->where('isdefault', 'Y')
                    ->first();
                if (!$locator) {
                    $locator = DB::connection('idempiere')
                        ->table('m_locator')
                        ->where('m_warehouse_id', $inout->m_warehouse_id)
                        ->where('isactive', 'Y')
                        ->first();
                }
                if ($locator) {
                    $locatorId = $locator->m_locator_id;
                }
            }

            // Validate PO line qty if linked
            if (!empty($validated['c_orderline_id'])) {
                $orderLineId = (int) $validated['c_orderline_id'];

                $orderLine = DB::connection('idempiere')
                    ->table('c_orderline')
                    ->where('c_orderline_id', $orderLineId)
                    ->select('qtyentered')
                    ->first();

                if (!$orderLine) {
                    return response()->json(['message' => 'PO line not found.'], 422);
                }

                $poQty = (float) $orderLine->qtyentered;

                $usedQtyQuery = DB::connection('idempiere')
                    ->table('m_inoutline')
                    ->where('c_orderline_id', $orderLineId);

                if (!empty($validated['line_id'])) {
                    $usedQtyQuery->where('m_inoutline_id', '!=', (int) $validated['line_id']);
                }

                $usedQty = (float) ($usedQtyQuery->sum('movementqty') ?? 0);
                $totalAfterSave = $usedQty + $qty;

                if ($totalAfterSave > $poQty) {
                    $remaining = $poQty - $usedQty;
                    return response()->json([
                        'message' => "Qty exceeds PO line limit. "
                            . "PO Qty: {$poQty}, "
                            . "Already received: {$usedQty}, "
                            . "Remaining: " . max(0, $remaining) . "."
                    ], 422);
                }
            }

            $payload = [
                'M_Product_ID' => (int) $validated['m_product_id'],
                'MovementQty' => $qty,
                'QtyEntered' => $qty,
                'Description' => $validated['description'] ?? null,
            ];

            if ($locatorId) {
                $payload['M_Locator_ID'] = $locatorId;
            }

            if (!empty($validated['c_orderline_id'])) {
                $payload['C_OrderLine_ID'] = (int) $validated['c_orderline_id'];
            }

            if (!empty($validated['line_id'])) {
                $lineId = $validated['line_id'];
                $response = $this->idempiereService->put("models/m_inoutline/{$lineId}", $payload);
                $action = 'updated';
            } else {
                $payload['AD_Client_ID'] = (int) $sessionClientId;
                $payload['M_InOut_ID'] = (int) $inoutId;
                if ($inout) {
                    $payload['AD_Org_ID'] = (int) $inout->ad_org_id;
                }
                $response = $this->idempiereService->post("models/m_inoutline", $payload);
                $action = 'created';
            }

            if ($response->successful()) {
                return response()->json(['message' => "Line {$action} successfully"]);
            } else {
                Log::error("GR Line Error ({$action}): " . $response->body());
                return response()->json(['message' => "Failed to {$action} line: " . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('GR Line Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroyLine(Request $request)
    {
        $validated = $request->validate(['line_ids' => 'required|array']);

        try {
            foreach ($validated['line_ids'] as $lineId) {
                $lineId = (int) $lineId;
                $response = $this->idempiereService->delete("models/m_inoutline/{$lineId}");

                if (!$response->successful()) {
                    $errorBody = $response->json('detail') ?? $response->body();
                    Log::error("GR Line Delete Failed (line_id={$lineId}): " . $response->body());
                    return response()->json([
                        'message' => "Failed to delete line #{$lineId}: {$errorBody}"
                    ], $response->status() ?: 500);
                }
            }

            return response()->json(['message' => 'Line(s) deleted successfully']);

        } catch (\Exception $e) {
            Log::error('GR Line Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function process(Request $request)
    {
        $materialReceiptConfig = config('idempiere.create-gr');
        $workflowConfig = $materialReceiptConfig['workflow'] ?? [];
        $reactivateAction = $workflowConfig['reactivate_action'] ?? 'RE';
        $reactivateFrom = $workflowConfig['reactivate_from'] ?? ['CO'];

        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:' . implode(',', $materialReceiptConfig['workflow']['allowed_actions']),
        ]);

        try {
            $inoutId = Crypt::decryptString($validated['document_id']);

            if ($validated['doc_action'] === $reactivateAction) {
                $originalDoc = DB::connection('idempiere')
                    ->table('m_inout')
                    ->where('m_inout_id', $inoutId)
                    ->first();

                if (!$originalDoc) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Material Receipt not found'
                    ], 404);
                }

                if (!in_array($originalDoc->docstatus, $reactivateFrom, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Re-Active hanya bisa dilakukan dari status yang diizinkan.'
                    ], 422);
                }

                $originalLines = DB::connection('idempiere')
                    ->table('m_inoutline')
                    ->where('m_inout_id', $inoutId)
                    ->where('isactive', 'Y')
                    ->orderBy('line')
                    ->get();

                $newPayload = [
                    'AD_Client_ID' => (int) $originalDoc->ad_client_id,
                    'AD_Org_ID' => (int) $originalDoc->ad_org_id,
                    'M_Warehouse_ID' => (int) $originalDoc->m_warehouse_id,
                    'C_DocType_ID' => (int) $originalDoc->c_doctype_id,
                    'MovementDate' => $originalDoc->movementdate,
                    'DateAcct' => $originalDoc->dateacct ?? $originalDoc->movementdate,
                    'MovementType' => $originalDoc->movementtype,
                    'IsSOTrx' => $originalDoc->issotrx,
                    'C_BPartner_ID' => $originalDoc->c_bpartner_id,
                    'C_BPartner_Location_ID' => $originalDoc->c_bpartner_location_id,
                    'Description' => $originalDoc->description,
                    'SalesRep_ID' => $originalDoc->salesrep_id,
                    'POReference' => $originalDoc->poreference,
                    'C_Project_ID' => $originalDoc->c_project_id,
                    'C_Order_ID' => $originalDoc->c_order_id,
                ];

                $newPayload = array_filter($newPayload, fn ($value) => !is_null($value));

                Log::info('Re-Active Material Receipt: creating copied header', [
                    'source_m_inout_id' => $inoutId,
                    'payload' => $newPayload,
                ]);

                $newDocResponse = $this->idempiereService->post('models/m_inout', $newPayload);

                if (!$newDocResponse->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create copied receipt: ' . $newDocResponse->body(),
                    ], $newDocResponse->status() ?: 500);
                }

                $newDocData = $newDocResponse->json();
                $newMInOutId = $newDocData['id'] ?? $newDocData['M_InOut_ID'] ?? $newDocData['recordID'] ?? null;

                if (!$newMInOutId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create copied receipt: new document ID not returned.',
                    ], 500);
                }

                foreach ($originalLines as $line) {
                    $linePayload = [
                        'AD_Client_ID' => (int) $line->ad_client_id,
                        'AD_Org_ID' => (int) $line->ad_org_id,
                        'M_InOut_ID' => (int) $newMInOutId,
                        'Line' => $line->line,
                        'M_Locator_ID' => $line->m_locator_id,
                        'M_Product_ID' => $line->m_product_id,
                        'C_UOM_ID' => $line->c_uom_id,
                        'MovementQty' => $line->movementqty,
                        'QtyEntered' => $line->qtyentered,
                        'Description' => $line->description,
                        'C_OrderLine_ID' => $line->c_orderline_id,
                        'IsActive' => true,
                    ];

                    $linePayload = array_filter($linePayload, fn ($value) => !is_null($value));

                    $lineResponse = $this->idempiereService->post('models/m_inoutline', $linePayload);
                    if (!$lineResponse->successful()) {
                        $this->idempiereService->delete("models/m_inout/{$newMInOutId}");

                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to copy receipt lines: ' . $lineResponse->body(),
                        ], $lineResponse->status() ?: 500);
                    }
                }

                $reversePayload = ['doc-action' => 'RC'];

                Log::info('Re-Active Material Receipt: reversing original document', [
                    'm_inout_id' => $inoutId,
                    'action' => 'RC',
                ]);

                $reverseResponse = $this->idempiereService->put("models/m_inout/{$inoutId}", $reversePayload);

                if (!$reverseResponse->successful()) {
                    $this->idempiereService->delete("models/m_inout/{$newMInOutId}");

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to reverse original receipt: ' . $reverseResponse->body(),
                    ], $reverseResponse->status() ?: 500);
                }

                $originalDocNo = $originalDoc->documentno;
                $newDocRecord = DB::connection('idempiere')
                    ->table('m_inout')
                    ->where('m_inout_id', $newMInOutId)
                    ->first();

                $newDocNo = $newDocRecord->documentno ?? null;

                if ($originalDocNo && $newDocNo) {
                    DB::connection('idempiere')
                        ->table('m_inout')
                        ->where('m_inout_id', $inoutId)
                        ->update(['documentno' => $newDocNo]);

                    DB::connection('idempiere')
                        ->table('m_inout')
                        ->where('m_inout_id', $newMInOutId)
                        ->update(['documentno' => $originalDocNo]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Document re-activated successfully',
                    'data' => $reverseResponse->json(),
                    'new_document_id' => Crypt::encryptString($newMInOutId),
                ]);
            }

            $payload = ['doc-action' => $validated['doc_action']];

            Log::info('Processing Material Receipt', [
                'inout_id' => $inoutId,
                'action' => $validated['doc_action']
            ]);

            $response = $this->idempiereService->put("models/m_inout/{$inoutId}", $payload);

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
            Log::error('GR Process Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate(['document_id' => 'required']);

        try {
            $inoutId = Crypt::decryptString($validated['document_id']);

            $receipt = DB::connection('idempiere')
                ->table('m_inout')
                ->where('m_inout_id', $inoutId)
                ->select('docstatus', 'documentno')
                ->first();

            if (!$receipt) {
                return response()->json(['message' => 'Material Receipt not found.'], 404);
            }

            if ($receipt->docstatus !== 'DR') {
                return response()->json(['message' => 'Only Draft receipts can be deleted.'], 422);
            }

            $response = $this->idempiereService->delete("models/m_inout/{$inoutId}");

            if (!$response->successful()) {
                $errorBody = $response->json('detail') ?? $response->body();
                return response()->json(['message' => 'Failed to delete: ' . $errorBody], $response->status() ?: 500);
            }

            return response()->json(['message' => 'Material Receipt ' . $receipt->documentno . ' deleted successfully.']);

        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID.'], 400);
        } catch (\Exception $e) {
            Log::error('GR Delete Error: ' . $e->getMessage());
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
                "models/m_inout/{$docId}/attachments",
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

            $response = $this->idempiereService->delete("models/m_inout/{$docId}/attachments/{$encodedId}");

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
            Log::error('GR View Attachment Error: ' . $e->getMessage());
            abort(500, 'Error viewing attachment');
        }
    }

    // API: Get Warehouses by Org
    public function getWarehouses(Request $request)
    {
        $orgId = $request->get('org_id');
        if (!$orgId)
            return response()->json([]); 
        $warehouses = DB::connection('idempiere')->select("
            SELECT w.m_warehouse_id AS id, w.name AS text
            FROM m_warehouse w
            WHERE w.isactive = 'Y' AND w.ad_org_id = ?
            ORDER BY w.name", [$orgId]); 
        return response()->json($warehouses);
    }

    // API: Get Products
    public function getProducts(Request $request)
    {
        $materialReceiptConfig = config('idempiere.create-gr');
        $search = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $materialReceiptConfig['limits']['products_per_page'];
        $clientId = Session::get('idempiere_client');

        if (!$clientId)
            return response()->json(['results' => [], 'pagination' => ['more' => false]]);

        $query = DB::connection('idempiere')
            ->table('m_product as p')
            ->leftJoin('c_uom as u', 'p.c_uom_id', '=', 'u.c_uom_id')
            ->select(
                'p.m_product_id as id',
                DB::raw("p.value || ' - ' || p.name as text"),
                'u.name as uom_name',
                'u.uomsymbol as uom_symbol'
            )
            ->where('p.isactive', 'Y')
            ->where('p.issummary', 'N')
            ->where('p.ad_client_id', $clientId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });
        }

        $offset = ($page - 1) * $perPage;
        $products = $query->offset($offset)->limit($perPage + 1)->orderBy('p.name')->get();
        $hasMore = count($products) > $perPage;
        if ($hasMore)
            $products = $products->slice(0, $perPage)->values();

        return response()->json(['results' => $products, 'pagination' => ['more' => $hasMore]]);
    }

    // API: Get PO Lines for linking (completed POs with remaining qty only)
    public function getPoLines(Request $request)
    {
        $materialReceiptConfig = config('idempiere.create-gr');
        $clientId = Session::get('idempiere_client');
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = $materialReceiptConfig['limits']['po_modal'];

        $query = DB::connection('idempiere')
            ->table('c_orderline as ol')
            ->join('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
            ->join('m_product as p', 'p.m_product_id', '=', 'ol.m_product_id')
            ->leftJoin('c_bpartner as bp', 'bp.c_bpartner_id', '=', 'o.c_bpartner_id')
            ->leftJoin('c_uom as u', 'ol.c_uom_id', '=', 'u.c_uom_id')
            ->where('o.docstatus', 'CO')
            ->where('o.issotrx', $materialReceiptConfig['purchase_order']['is_so_trx'])
            ->where('ol.ad_client_id', $clientId)
            ->whereRaw('COALESCE(ol.qtyentered, 0) - COALESCE(ol.qtydelivered, 0) > 0')
            ->select(
                'ol.c_orderline_id',
                'o.documentno as po_documentno',
                'ol.line as po_line',
                'bp.name as vendor_name',
                'p.name as product_name',
                'p.value as product_code',
                'p.m_product_id',
                'ol.qtyentered as ordered_qty',
                'ol.qtydelivered as received_qty',
                DB::raw('(COALESCE(ol.qtyentered, 0) - COALESCE(ol.qtydelivered, 0)) as remaining_qty'),
                'u.uomsymbol as uom_symbol',
                'u.name as uom_name'
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('o.documentno', 'ilike', "%{$search}%")
                    ->orWhere('p.name', 'ilike', "%{$search}%")
                    ->orWhere('p.value', 'ilike', "%{$search}%");
            });
        }

        $documentId = $request->get('document_id');
        if ($documentId) {
            try {
                $inoutId = Crypt::decryptString($documentId);
                $inout = DB::connection('idempiere')
                    ->table('m_inout')
                    ->where('m_inout_id', $inoutId)
                    ->select('c_bpartner_id')
                    ->first();

                if ($inout && $inout->c_bpartner_id) {
                    $query->where('o.c_bpartner_id', (int) $inout->c_bpartner_id);
                }
            } catch (\Exception $e) {
                // Ignore exception, can't filter by bpartner
            }
        }

        $results = $query->orderBy('o.documentno', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $results->values(),
            'pagination' => ['more' => $results->hasMorePages()],
            'total' => $results->total(),
        ]);
    }

    public function repost(Request $request, $id)
    {
        try {
            $decryptedId = Crypt::decryptString($id);
            $receipt = MInOut::findOrFail($decryptedId);

            // Trigger re-post by setting posted to N and processing to N
            $receipt->posted = 'N';
            $receipt->processing = 'N';
            $receipt->save();

            return response()->json([
                'success' => true,
                'message' => 'Material Receipt marked for re-posting successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error("Material Receipt Repost Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger re-post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportJournals($id)
    {
        try {
            $materialReceiptConfig = config('idempiere.create-gr');
            $decryptedId = Crypt::decryptString($id);
            $receipt = MInOut::findOrFail($decryptedId);

            $journals = DB::connection('idempiere')
                ->table('fact_acct as fa')
                ->join('c_elementvalue as ev', 'ev.c_elementvalue_id', '=', 'fa.account_id')
                ->select(
                    'ev.value as account_value',
                    'ev.name as account_name',
                    DB::raw('COALESCE(fa.amtacctdr, 0) as amt_acct_dr'),
                    DB::raw('COALESCE(fa.amtacctcr, 0) as amt_acct_cr')
                )
                ->where('fa.ad_table_id', $materialReceiptConfig['journals']['table_id'])
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
                echo "<h3>Journal Entries for Material Receipt: " . $receipt->documentno . "</h3>";
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
            Log::error("Material Receipt Export Journals Error: " . $e->getMessage());
            abort(500, 'Failed to export journals: ' . $e->getMessage());
        }
    }

    private function getStatusLabel(?string $status): string
    {
        $statusLabels = config('idempiere.create-gr.statuses.labels', []);

        return $statusLabels[$status] ?? ($status ?? 'Unknown');
    }
}
