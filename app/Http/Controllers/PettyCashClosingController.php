<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\Idempiere\DpkPettycashClosing;
use App\Models\Idempiere\DpkPettycashClosingLine;
use App\Models\Idempiere\DpkPettycashRequest;

class PettyCashClosingController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    /**
     * Display a listing of petty cash closings
     */
    public function index()
    {
        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        // Handle Detail View if document_id is present
        if (request()->has('document_id')) {
            return $this->showForm(request('document_id'));
        }

        // List View Logic
        $perPage = request()->get('per_page', 10);
        $page = request()->get('page', 1);
        $status = request()->get('status', 'all');
        $search = request()->get('search', '');

        $clientId = Session::get('idempiere_client');

        $query = DpkPettycashClosing::with('pettyCashRequest')
            ->where('ad_client_id', $clientId)
            ->where('isactive', 'Y');

        // Apply Filters
        if ($status !== 'all') {
            $query->where('docstatus', $status);
        }
        if ($search) {
            $query->where('documentno', 'ilike', "%{$search}%");
        }

        $query->orderBy('created', 'desc');
        $closings = $query->paginate($perPage);

        // Calculate Counts
        $countAll = DpkPettycashClosing::where('ad_client_id', $clientId)->where('isactive', 'Y')->count();
        $countDraft = DpkPettycashClosing::where('ad_client_id', $clientId)->where('isactive', 'Y')->where('docstatus', 'DR')->count();
        $countInProgress = DpkPettycashClosing::where('ad_client_id', $clientId)->where('isactive', 'Y')->where('docstatus', 'IP')->count();
        $countCompleted = DpkPettycashClosing::where('ad_client_id', $clientId)->where('isactive', 'Y')->whereIn('docstatus', ['CO', 'CL'])->count();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.petty-cash-closing.closing-table', ['closings' => $closings])->render(),
            ]);
        }

        return view('pages.petty-cash-closing.index', [
            'title' => 'Petty Cash Closing',
            'closings' => $closings,
            'countAll' => $countAll,
            'countDraft' => $countDraft,
            'countInProgress' => $countInProgress,
            'countCompleted' => $countCompleted
        ]);
    }

    /**
     * Show form for create/edit
     */
    private function showForm($docId)
    {
        $requestData = null;
        if ($docId !== 'new') {
            try {
                $decryptedId = Crypt::decryptString($docId);
                $requestData = DpkPettycashClosing::findOrFail($decryptedId);
            } catch (\Exception $e) {
                return redirect()->route('petty-cash-closing.index')->with('error', 'Invalid Link');
            }
        }

        $roleId = Session::get('idempiere_role');
        $clientId = Session::get('idempiere_client');

        // Fetch Organizations
        $organizations = DB::connection('idempiere')->select("
            SELECT o.ad_org_id AS id, o.name AS text
            FROM ad_org o
            JOIN ad_role_orgaccess roa ON roa.ad_org_id = o.ad_org_id
            WHERE o.isactive = 'Y' AND roa.isactive = 'Y' AND roa.ad_role_id = ? AND o.ad_org_id <> 0
            ORDER BY o.name
        ", [$roleId]);

        if (empty($organizations)) {
            $organizations = DB::connection('idempiere')->select("
                SELECT ad_org_id AS id, name AS text
                FROM ad_org
                WHERE ad_client_id = ? AND isactive = 'Y' AND ad_org_id <> 0
                ORDER BY name
            ", [$clientId]);
        }

        $currentOrgId = $requestData ? $requestData->ad_org_id : (count($organizations) > 0 ? $organizations[0]->id : null);

        // Fetch Business Partners
        $businessPartners = DB::connection('idempiere')->select("
            SELECT c_bpartner_id AS id, name AS text
            FROM c_bpartner
            WHERE isactive = 'Y' AND ad_client_id = ?
            ORDER BY name
        ", [$clientId]);

        // Fetch Users
        $users = DB::connection('idempiere')->select("
            SELECT DISTINCT u.ad_user_id AS id, u.name AS text
            FROM ad_user u
            WHERE u.isactive = 'Y' AND u.ad_client_id = ?
            ORDER BY u.name
        ", [$clientId]);

        // Fetch Currencies
        $currencies = DB::connection('idempiere')->select("
            SELECT c_currency_id AS id, iso_code AS text, cursymbol, iso_code
            FROM c_currency
            WHERE isactive = 'Y'
            ORDER BY iso_code
        ");

        // Find default currency (IDR)
        $defaultCurrencyId = null;
        foreach ($currencies as $curr) {
            if ($curr->iso_code === 'IDR') {
                $defaultCurrencyId = $curr->id;
                break;
            }
        }

        // Fetch DocTypes for Petty Cash Closing
        $docTypes = DB::connection('idempiere')->select("
            SELECT dt.c_doctype_id AS id, dt.name AS text
            FROM c_doctype dt
            WHERE dt.isactive = 'Y'
            AND dt.ad_client_id = ?
            AND dt.ad_org_id IN (0, ?)
            ORDER BY dt.name
        ", [$clientId, $currentOrgId]);

        // Fetch Petty Cash Requests for linking
        // Ideally only requests that are Completed or Closed and haven't been fully closed out yet
        $pettyCashRequestsQuery = "
            SELECT dpk_pettycash_request_id AS id, documentno || ' - ' || COALESCE(description, '') AS text
            FROM dpk_pettycash_request
            WHERE isactive = 'Y' AND ad_client_id = ? AND (docstatus = 'CO'";

        $queryParams = [$clientId];

        if ($requestData && $requestData->dpk_pettycash_request_id) {
            $pettyCashRequestsQuery .= " OR dpk_pettycash_request_id = ?)";
            $queryParams[] = $requestData->dpk_pettycash_request_id;
        } else {
            $pettyCashRequestsQuery .= ")";
        }

        $pettyCashRequestsQuery .= " ORDER BY documentno DESC";

        $pettyCashRequests = DB::connection('idempiere')->select($pettyCashRequestsQuery, $queryParams);

        // Fetch Cost Centers
        $costCenters = DB::connection('idempiere')->select("
            SELECT dpk_cost_center_id AS id, name AS text
            FROM dpk_cost_center
            WHERE isactive = 'Y' AND ad_client_id = ?
            ORDER BY name
        ", [$clientId]);

        // Lines
        if ($requestData) {
            $lines = DB::connection('idempiere')
                ->table('dpk_pettycash_closingline as pcl')
                ->leftJoin('dpk_pettycash_requestline as drl', 'pcl.dpk_pettycash_requestline_id', '=', 'drl.dpk_pettycash_requestline_id')
                ->leftJoin('dpk_pettycash_request as dr', 'drl.dpk_pettycash_request_id', '=', 'dr.dpk_pettycash_request_id')
                ->where('pcl.dpk_pettycash_closing_id', $requestData->dpk_pettycash_closing_id)
                ->where('pcl.isactive', 'Y')
                ->select(
                    'pcl.*',
                    'dr.documentno as request_documentno',
                    'drl.line as request_line'
                )
                ->orderBy('pcl.dpk_pettycash_closingline_id')
                ->paginate(10);
        } else {
            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        $statusLabel = $requestData ? $this->getStatusLabel($requestData->docstatus) : 'Draft';

        $viewData = [
            'title' => $docId === 'new' ? 'Create Petty Cash Closing' : 'Edit Petty Cash Closing',
            'request' => $requestData,
            'lines' => $lines,
            'organizations' => $organizations,
            'businessPartners' => $businessPartners,
            'users' => $users,
            'currencies' => $currencies,
            'docTypes' => $docTypes,
            'pettyCashRequests' => $pettyCashRequests,
            'costCenters' => $costCenters,
            'isNew' => is_null($requestData),
            'docNo' => $requestData ? $requestData->documentno : '** New **',
            'status' => $statusLabel,
            'currentOrgId' => $currentOrgId,
            'defaultCurrencyId' => $defaultCurrencyId,
            'dateTrx' => $requestData && $requestData->datetrx ? \Illuminate\Support\Carbon::parse($requestData->datetrx)->format('Y-m-d') : date('Y-m-d'),
            'dateAcct' => $requestData && $requestData->dateacct ? \Illuminate\Support\Carbon::parse($requestData->dateacct)->format('Y-m-d') : date('Y-m-d'),
            'docIdParam' => request('document_id'),
            'isReadOnly' => $requestData && in_array($requestData->docstatus, ['CO', 'CL', 'VO', 'RE']),
            'isDraft' => $requestData && $requestData->docstatus === 'DR',
            'hasLines' => $lines->total() > 0,
            'activeTab' => request('tab', 'header'),
        ];

        // Set default docTypeId
        if ($requestData && isset($requestData->c_doctypetarget_id)) {
            $viewData['docTypeId'] = $requestData->c_doctypetarget_id;
        } else {
            $viewData['docTypeId'] = count($docTypes) > 0 ? $docTypes[0]->id : null;
        }

        // AJAX tab loading
        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');
            if ($tab === 'header')
                return view('pages.petty-cash-closing.partials.tab-header', $viewData);
            if ($tab === 'lines')
                return view('pages.petty-cash-closing.partials.tab-lines', $viewData);
            if ($tab === 'attachments') {
                $attachments = [];
                if (isset($requestData)) {
                    try {
                        $url = "models/dpk_pettycash_closing/{$requestData->dpk_pettycash_closing_id}/attachments";
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
                        Log::error("Failed to fetch attachments: " . $e->getMessage());
                        $attachments = [];
                    }
                }
                $viewData['attachments'] = $attachments;
                return view('pages.petty-cash-closing.partials.tab-attachments', $viewData);
            }
        }

        return view('pages.petty-cash-closing.form', $viewData);
    }

    /**
     * Store new petty cash closing
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'org_id' => 'required',
            'bpartner_id' => 'nullable',
            'user_id' => 'required',
            'currency_id' => 'required',
            'doc_type_id' => 'required',
            'dpk_pettycash_request_id' => 'required',
            'date_trx' => 'required|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'description' => 'nullable|string',
            'cost_center_id' => 'nullable',
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

        $payload = [
            'AD_Client_ID' => (int) $sessionClientId,
            'AD_Org_ID' => (int) $validated['org_id'],
            'AD_User_ID' => (int) $validated['user_id'],
            'C_Currency_ID' => (int) $validated['currency_id'],
            'C_DocTypeTarget_ID' => (int) $validated['doc_type_id'],
            'DPK_PettyCash_Request_ID' => (int) $validated['dpk_pettycash_request_id'],
            'DateTrx' => $validated['date_trx'],
            'DateAcct' => $validated['date_acct'] ?? $validated['date_trx'],
            'Description' => $validated['description'],
        ];

        if (!empty($validated['bpartner_id'])) {
            $payload['C_BPartner_ID'] = (int) $validated['bpartner_id'];
        }

        if (!empty($validated['cost_center_id'])) {
            $payload['DPK_Cost_Center_ID'] = (int) $validated['cost_center_id'];
        }

        Log::info('Petty Cash Closing Create Payload:', $payload);

        try {
            $response = $this->idempiereService->post('models/dpk_pettycash_closing', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['DPK_PettyCash_Closing_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'Petty Cash Closing created successfully',
                    'data' => [
                        'dpk_pettycash_closing_id' => $id,
                        'encrypted_id' => Crypt::encryptString($id)
                    ]
                ]);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Petty Cash Closing Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create closing: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update petty cash closing
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'org_id' => 'nullable',
            'bpartner_id' => 'nullable',
            'user_id' => 'nullable',
            'currency_id' => 'nullable',
            'doc_type_id' => 'nullable',
            'dpk_pettycash_request_id' => 'nullable',
            'date_trx' => 'nullable|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'description' => 'nullable|string',
            'cost_center_id' => 'nullable',
        ]);

        $payload = [];
        if (!empty($validated['org_id']))
            $payload['AD_Org_ID'] = (int) $validated['org_id'];
        if (!empty($validated['bpartner_id']))
            $payload['C_BPartner_ID'] = (int) $validated['bpartner_id'];
        if (!empty($validated['user_id']))
            $payload['AD_User_ID'] = (int) $validated['user_id'];
        if (!empty($validated['currency_id']))
            $payload['C_Currency_ID'] = (int) $validated['currency_id'];
        if (!empty($validated['doc_type_id']))
            $payload['C_DocTypeTarget_ID'] = (int) $validated['doc_type_id'];
        if (!empty($validated['dpk_pettycash_request_id']))
            $payload['DPK_PettyCash_Request_ID'] = (int) $validated['dpk_pettycash_request_id'];
        if (!empty($validated['date_trx']))
            $payload['DateTrx'] = $validated['date_trx'];
        if (!empty($validated['date_acct']))
            $payload['DateAcct'] = $validated['date_acct'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        if (!empty($validated['cost_center_id']))
            $payload['DPK_Cost_Center_ID'] = (int) $validated['cost_center_id'];

        try {
            $response = $this->idempiereService->put("models/dpk_pettycash_closing/{$id}", $payload);
            if ($response->successful()) {
                return response()->json(['message' => 'Petty Cash Closing updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Petty Cash Closing Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update closing: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store/Update line
     */
    public function storeLine(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'line_id' => 'nullable',
            'description' => 'nullable|string',
            'name' => 'nullable|string',
            'amount' => 'required|numeric|gt:0',
        ]);

        try {
            $closingId = Crypt::decryptString($validated['document_id']);
            $closingData = DpkPettycashClosing::find($closingId);

            if (!$closingData) {
                return response()->json(['message' => 'Closing header not found'], 404);
            }

            $userData = Session::get('user_data');
            $sessionClientId = Session::get('idempiere_client');
            $sessionUserId = is_array($userData) ? ($userData['userId'] ?? 0) : ($userData->userId ?? 0);

            $payload = [
                'Description' => $validated['description'],
                'Name' => $validated['name'],
                'Amount' => (float) $validated['amount'],
            ];

            if (!empty($validated['line_id'])) {
                // Update
                $lineId = $validated['line_id'];
                $response = $this->idempiereService->put("models/dpk_pettycash_closingline/{$lineId}", $payload);
                $action = 'updated';
            } else {
                // Create
                $payload['AD_Client_ID'] = (int) $sessionClientId;
                $payload['DPK_PettyCash_Closing_ID'] = (int) $closingId;

                // Also copy the request ID
                if ($closingData->dpk_pettycash_request_id) {
                    $payload['DPK_PettyCash_Request_ID'] = (int) $closingData->dpk_pettycash_request_id;
                }

                $response = $this->idempiereService->post("models/dpk_pettycash_closingline", $payload);
                $action = 'created';
            }

            if ($response->successful()) {
                // Fetch updated total
                $total = DB::connection('idempiere')
                    ->table('dpk_pettycash_closingline')
                    ->where('dpk_pettycash_closing_id', $closingId)
                    ->where('isactive', 'Y')
                    ->sum('amount');

                if ($closingId) {
                    DB::connection('idempiere')
                        ->table('dpk_pettycash_closing')
                        ->where('dpk_pettycash_closing_id', $closingId)
                        ->update(['totallines' => $total]);
                }

                return response()->json([
                    'message' => "Line $action successfully",
                    'total' => number_format($total ?? 0, 2)
                ]);
            } else {
                Log::error("Petty Cash Closing Line Error ($action): " . $response->body());
                return response()->json(['message' => "Failed to $action line: " . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Petty Cash Closing Line Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete line(s)
     */
    public function destroyLine(Request $request)
    {
        $validated = $request->validate(['line_ids' => 'required|array']);

        try {
            $closingId = null;
            foreach ($validated['line_ids'] as $lineId) {
                $lineId = (int) $lineId;

                if (!$closingId) {
                    $line = DB::connection('idempiere')
                        ->table('dpk_pettycash_closingline')
                        ->where('dpk_pettycash_closingline_id', $lineId)
                        ->select('dpk_pettycash_closing_id')
                        ->first();
                    $closingId = $line->dpk_pettycash_closing_id ?? null;
                }

                $response = $this->idempiereService->delete("models/dpk_pettycash_closingline/{$lineId}");

                if (!$response->successful()) {
                    $errorBody = $response->json('detail') ?? $response->body();
                    Log::error("Petty Cash Closing Line Delete Failed (line_id={$lineId}): " . $response->body());
                    return response()->json([
                        'message' => "Failed to delete line #{$lineId}: {$errorBody}"
                    ], $response->status() ?: 500);
                }
            }

            // Fetch updated total
            $updatedTotals = [];
            if ($closingId) {
                $total = DB::connection('idempiere')
                    ->table('dpk_pettycash_closingline')
                    ->where('dpk_pettycash_closing_id', $closingId)
                    ->where('isactive', 'Y')
                    ->sum('amount');
                DB::connection('idempiere')
                    ->table('dpk_pettycash_closing')
                    ->where('dpk_pettycash_closing_id', $closingId)
                    ->update(['totallines' => $total]);

                $updatedTotals['total'] = number_format($total ?? 0, 2);
            }

            return response()->json(array_merge(['message' => 'Line(s) deleted successfully'], $updatedTotals));

        } catch (\Exception $e) {
            Log::error('Petty Cash Closing Line Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process document (Complete, Void, etc.)
     */
    public function process(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'doc_action' => 'required|in:CO,PR,VO,CL,RE',
        ]);

        try {
            $closingId = Crypt::decryptString($validated['document_id']);

            $payload = [
                'doc-action' => $validated['doc_action']
            ];

            Log::info('Processing Petty Cash Closing', [
                'closing_id' => $closingId,
                'action' => $validated['doc_action']
            ]);

            $response = $this->idempiereService->put("models/dpk_pettycash_closing/{$closingId}", $payload);

            if ($response->successful()) {
                // If we are completing the closing document, we should also close the associated request
                if ($validated['doc_action'] === 'CO') {
                    $closingDoc = DB::connection('idempiere')
                        ->table('dpk_pettycash_closing')
                        ->where('dpk_pettycash_closing_id', $closingId)
                        ->select('dpk_pettycash_request_id')
                        ->first();

                    if ($closingDoc && $closingDoc->dpk_pettycash_request_id) {
                        Log::info('Automatically closing associated Petty Cash Request', [
                            'request_id' => $closingDoc->dpk_pettycash_request_id
                        ]);

                        $reqPayload = ['doc-action' => 'CL'];
                        $reqResponse = $this->idempiereService->put("models/dpk_pettycash_request/{$closingDoc->dpk_pettycash_request_id}", $reqPayload);

                        if (!$reqResponse->successful()) {
                            Log::error('Failed to auto-close Petty Cash Request: ' . $reqResponse->body());
                        }
                    }
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
            Log::error('Petty Cash Closing Process Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload attachment
     */
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
                "models/dpk_pettycash_closing/{$docId}/attachments",
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

    public function destroy(Request $request)
    {
        $validated = $request->validate(['document_id' => 'required']);

        try {
            $docId = Crypt::decryptString($validated['document_id']);

            $doc = DB::connection('idempiere')
                ->table('dpk_pettycash_closing')
                ->where('dpk_pettycash_closing_id', $docId)
                ->select('docstatus', 'documentno')
                ->first();

            if (!$doc) {
                return response()->json(['message' => 'Petty Cash Closing not found.'], 404);
            }

            if ($doc->docstatus !== 'DR') {
                return response()->json(['message' => 'Only Draft closings can be deleted.'], 422);
            }

            // Delete existing lines first to avoid foreign key/custom mapping errors
            $lines = DB::connection('idempiere')
                ->table('dpk_pettycash_closingline')
                ->where('dpk_pettycash_closing_id', $docId)
                ->get();

            foreach ($lines as $line) {
                $lineResponse = $this->idempiereService->delete("models/dpk_pettycash_closingline/{$line->dpk_pettycash_closingline_id}");
                if (!$lineResponse->successful()) {
                    Log::error("Failed to delete line {$line->dpk_pettycash_closingline_id} during closing deletion");
                }
            }

            // Now delete the header document
            $response = $this->idempiereService->delete("models/dpk_pettycash_closing/{$docId}");

            if (!$response->successful()) {
                $errorBody = $response->json('detail') ?? $response->body();
                return response()->json(['message' => 'Failed to delete: ' . $errorBody], $response->status() ?: 500);
            }

            return response()->json(['message' => 'Petty Cash Closing ' . $doc->documentno . ' deleted successfully.']);
        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID.'], 400);
        } catch (\Exception $e) {
            Log::error('Petty Cash Closing Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete attachment
     */
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

            $response = $this->idempiereService->delete("models/dpk_pettycash_closing/{$docId}/attachments/{$encodedId}");

            if ($response->successful()) {
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => 'Delete failed: ' . $response->body()], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * View attachment
     */
    public function viewAttachment($document_id, $file_name)
    {
        try {
            $docId = Crypt::decryptString($document_id);
            $encodedFileName = rawurlencode($file_name);

            $url = "models/dpk_pettycash_closing/{$docId}/attachments/{$encodedFileName}";
            $response = $this->idempiereService->get($url);

            if ($response->successful()) {
                $content = $response->body();
                $contentType = $response->header('Content-Type') ?? 'application/octet-stream';

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

    /**
     * Get status label
     */
    private function getStatusLabel($docStatus)
    {
        $labels = [
            'DR' => 'Draft',
            'IP' => 'In Progress',
            'CO' => 'Completed',
            'CL' => 'Closed',
            'VO' => 'Voided',
            'RE' => 'Reversed',
        ];

        return $labels[$docStatus] ?? 'Unknown';
    }

    /**
     * Create route redirect
     */
    public function create()
    {
        return redirect()->route('petty-cash-closing.index', ['document_id' => 'new']);
    }
    /**
     * Store bulk request lines into closing lines
     */
    public function storeBulkRequestLines(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'request_lines' => 'required|array'
        ]);

        try {
            $closingId = Crypt::decryptString($validated['document_id']);
            $closingData = DpkPettycashClosing::find($closingId);

            if (!$closingData) {
                return response()->json(['message' => 'Closing header not found'], 404);
            }

            $sessionClientId = Session::get('idempiere_client');

            $successCount = 0;
            $errorCount = 0;

            foreach ($validated['request_lines'] as $line) {
                $payload = [
                    'AD_Client_ID' => (int) $sessionClientId,
                    'DPK_PettyCash_Closing_ID' => (int) $closingId,
                    'DPK_PettyCash_Request_ID' => (int) ($closingData->dpk_pettycash_request_id ?? 0),
                    'DPK_PettyCash_RequestLine_ID' => (int) $line['dpk_pettycash_requestline_id'],
                    'Name' => current(array_filter([$line['value'] ?? null, $line['name'] ?? null, '-'])), // Using fallback based on name/value
                    'Description' => $line['description'] ?? null,
                    'Amount' => (float) $line['amount'],
                ];

                $response = $this->idempiereService->post("models/dpk_pettycash_closingline", $payload);
                if ($response->successful()) {
                    $successCount++;
                } else {
                    $errorCount++;
                    Log::error("Failed to add closing line: " . $response->body());
                }
            }

            // Fetch updated total
            $total = DB::connection('idempiere')
                ->table('dpk_pettycash_closingline')
                ->where('dpk_pettycash_closing_id', $closingId)
                ->where('isactive', 'Y')
                ->sum('amount');

            if ($closingId) {
                DB::connection('idempiere')
                    ->table('dpk_pettycash_closing')
                    ->where('dpk_pettycash_closing_id', $closingId)
                    ->update(['totallines' => $total]);
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => "Added $successCount lines. Failed: $errorCount",
                'total' => number_format($total ?? 0, 2)
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk Store Lines Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get unclosed petty cash request lines for linking
     */
    public function getRequestLines(Request $request)
    {
        $clientId = Session::get('idempiere_client');
        $documentId = $request->get('document_id');
        $q = $request->get('q', '');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 10);

        if (!$documentId) {
            return response()->json(['results' => [], 'total' => 0]);
        }

        try {
            $closingId = Crypt::decryptString($documentId);
            $closingData = DpkPettycashClosing::find($closingId);

            if (!$closingData || !$closingData->dpk_pettycash_request_id) {
                return response()->json(['results' => [], 'total' => 0]);
            }

            $requestId = $closingData->dpk_pettycash_request_id;

            $query = DB::connection('idempiere')->table('dpk_pettycash_requestline as rl')
                ->where('rl.dpk_pettycash_request_id', $requestId)
                ->where('rl.isactive', 'Y')
                ->whereNotExists(function ($qBuilder) use ($closingId) {
                    $qBuilder->select(DB::raw(1))
                        ->from('dpk_pettycash_closingline as cl')
                        ->whereColumn('cl.dpk_pettycash_requestline_id', 'rl.dpk_pettycash_requestline_id')
                        ->where('cl.dpk_pettycash_closing_id', $closingId)
                        ->where('cl.isactive', 'Y');
                });

            if ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('rl.name', 'ilike', '%' . $q . '%')
                        ->orWhere('rl.value', 'ilike', '%' . $q . '%')
                        ->orWhere('rl.description', 'ilike', '%' . $q . '%');
                });
            }

            $total = $query->count();

            $lines = $query->orderBy('rl.line', 'asc')
                ->forPage($page, $perPage)
                ->get();

            return response()->json([
                'results' => $lines,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pagination' => ['more' => ($page * $perPage) < $total]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get specific petty cash request details
     */
    public function getRequestInfo($id)
    {
        try {
            $request = DpkPettycashRequest::find($id);
            if (!$request) {
                return response()->json(['message' => 'Request not found'], 404);
            }

            return response()->json([
                'c_bpartner_id' => $request->c_bpartner_id,
                'ad_user_id' => $request->ad_user_id,
                'dpk_cost_center_id' => $request->dpk_cost_center_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
