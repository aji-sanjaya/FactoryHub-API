<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\Idempiere\DpkPettycashRequest;
use App\Models\Idempiere\DpkPettycashRequestLine;

class PettyCashRequestController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    /**
     * Display a listing of petty cash requests
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

        $query = DpkPettycashRequest::where('ad_client_id', $clientId)
            ->where('isactive', 'Y');

        // Apply Filters
        if ($status !== 'all') {
            $query->where('docstatus', $status);
        }
        if ($search) {
            $query->where('documentno', 'ilike', "%{$search}%");
        }

        $query->orderBy('created', 'desc');
        $requests = $query->paginate($perPage);

        // Calculate Counts
        $countAll = DpkPettycashRequest::where('ad_client_id', $clientId)->where('isactive', 'Y')->count();
        $countDraft = DpkPettycashRequest::where('ad_client_id', $clientId)->where('isactive', 'Y')->where('docstatus', 'DR')->count();
        $countInProgress = DpkPettycashRequest::where('ad_client_id', $clientId)->where('isactive', 'Y')->where('docstatus', 'IP')->count();
        $countCompleted = DpkPettycashRequest::where('ad_client_id', $clientId)->where('isactive', 'Y')->whereIn('docstatus', ['CO', 'CL'])->count();

        if (request()->ajax()) {
            return response()->json([
                'html' => view('components.petty-cash-request.request-table', ['requests' => $requests])->render(),
            ]);
        }

        return view('pages.petty-cash-request.index', [
            'title' => 'Petty Cash Request',
            'requests' => $requests,
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
        $request = null;
        if ($docId !== 'new') {
            try {
                $decryptedId = Crypt::decryptString($docId);
                $request = DpkPettycashRequest::findOrFail($decryptedId);
            } catch (\Exception $e) {
                return redirect()->route('petty-cash-request.index')->with('error', 'Invalid Link');
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

        $currentOrgId = $request ? $request->ad_org_id : (count($organizations) > 0 ? $organizations[0]->id : null);

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

        // Fetch DocTypes for Petty Cash Request
        $docTypes = DB::connection('idempiere')->select("
            SELECT dt.c_doctype_id AS id, dt.name AS text
            FROM c_doctype dt
            WHERE dt.isactive = 'Y'
            AND dt.ad_client_id = ?
            AND dt.ad_org_id IN (0, ?)
            ORDER BY dt.name
        ", [$clientId, $currentOrgId]);

        // Fetch Cost Centers
        $costCenters = DB::connection('idempiere')->select("
            SELECT dpk_cost_center_id AS id, name AS text
            FROM dpk_cost_center
            WHERE isactive = 'Y' AND ad_client_id = ?
            ORDER BY name
        ", [$clientId]);

        // Lines
        if ($request) {
            $lines = DB::connection('idempiere')
                ->table('dpk_pettycash_requestline')
                ->where('dpk_pettycash_request_id', $request->dpk_pettycash_request_id)
                ->where('isactive', 'Y')
                ->orderBy('line')
                ->paginate(10);
        } else {
            $lines = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        $statusLabel = $request ? $this->getStatusLabel($request->docstatus) : 'Draft';

        $viewData = [
            'title' => $docId === 'new' ? 'Create Petty Cash Request' : 'Edit Petty Cash Request',
            'request' => $request,
            'lines' => $lines,
            'organizations' => $organizations,
            'businessPartners' => $businessPartners,
            'users' => $users,
            'currencies' => $currencies,
            'docTypes' => $docTypes,
            'costCenters' => $costCenters,
            'isNew' => is_null($request),
            'docNo' => $request ? $request->documentno : '** New **',
            'status' => $statusLabel,
            'currentOrgId' => $currentOrgId,
            'defaultCurrencyId' => $defaultCurrencyId,
            'dateTrx' => $request && $request->datetrx ? \Illuminate\Support\Carbon::parse($request->datetrx)->format('Y-m-d') : date('Y-m-d'),
            'dateAcct' => $request && $request->dateacct ? \Illuminate\Support\Carbon::parse($request->dateacct)->format('Y-m-d') : date('Y-m-d'),
            'docIdParam' => request('document_id'),
            'isReadOnly' => $request && in_array($request->docstatus, ['CO', 'CL', 'VO', 'RE']),
            'isDraft' => $request && $request->docstatus === 'DR',
            'activeTab' => request('tab', 'header'),
        ];

        // Set default docTypeId
        if ($request && isset($request->c_doctypetarget_id)) {
            $viewData['docTypeId'] = $request->c_doctypetarget_id;
        } else {
            $viewData['docTypeId'] = count($docTypes) > 0 ? $docTypes[0]->id : null;
        }

        // AJAX tab loading
        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');
            if ($tab === 'header')
                return view('pages.petty-cash-request.partials.tab-header', $viewData);
            if ($tab === 'lines')
                return view('pages.petty-cash-request.partials.tab-lines', $viewData);
            if ($tab === 'attachments') {
                $attachments = [];
                if (isset($request)) {
                    try {
                        $url = "models/dpk_pettycash_request/{$request->dpk_pettycash_request_id}/attachments";
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
                return view('pages.petty-cash-request.partials.tab-attachments', $viewData);
            }
        }

        return view('pages.petty-cash-request.form', $viewData);
    }

    /**
     * Store new petty cash request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'org_id' => 'required',
            'bpartner_id' => 'nullable',
            'user_id' => 'required',
            'currency_id' => 'required',
            'doc_type_id' => 'required',
            'date_trx' => 'required|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'description' => 'nullable|string',
            // 'name' => 'nullable|string',
            // 'value' => 'nullable|string',
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
            'DateTrx' => $validated['date_trx'],
            'DateAcct' => $validated['date_acct'] ?? $validated['date_trx'],
            'Description' => $validated['description'],
            // 'Name' => $validated['name'],
            // 'Value' => $validated['value'],
        ];

        if (!empty($validated['bpartner_id'])) {
            $payload['C_BPartner_ID'] = (int) $validated['bpartner_id'];
        }

        if (!empty($validated['cost_center_id'])) {
            $payload['DPK_Cost_Center_ID'] = (int) $validated['cost_center_id'];
        }

        Log::info('Petty Cash Request Create Payload:', $payload);

        try {
            $response = $this->idempiereService->post('models/dpk_pettycash_request', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $id = $data['id'] ?? $data['DPK_PettyCash_Request_ID'] ?? $data['recordID'] ?? null;

                return response()->json([
                    'message' => 'Petty Cash Request created successfully',
                    'data' => [
                        'dpk_pettycash_request_id' => $id,
                        'encrypted_id' => Crypt::encryptString($id)
                    ]
                ]);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Petty Cash Request Create Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create request: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update petty cash request
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'org_id' => 'nullable',
            'bpartner_id' => 'nullable',
            'user_id' => 'nullable',
            'currency_id' => 'nullable',
            'doc_type_id' => 'nullable',
            'date_trx' => 'nullable|date_format:Y-m-d',
            'date_acct' => 'nullable|date_format:Y-m-d',
            'description' => 'nullable|string',
            // 'name' => 'nullable|string',
            // 'value' => 'nullable|string',
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
        if (!empty($validated['date_trx']))
            $payload['DateTrx'] = $validated['date_trx'];
        if (!empty($validated['date_acct']))
            $payload['DateAcct'] = $validated['date_acct'];
        if (isset($validated['description']))
            $payload['Description'] = $validated['description'];
        // if (isset($validated['name']))
        //     $payload['Name'] = $validated['name'];
        // if (isset($validated['value']))
        //     $payload['Value'] = $validated['value'];
        if (!empty($validated['cost_center_id']))
            $payload['DPK_Cost_Center_ID'] = (int) $validated['cost_center_id'];

        try {
            $response = $this->idempiereService->put("models/dpk_pettycash_request/{$id}", $payload);
            if ($response->successful()) {
                return response()->json(['message' => 'Petty Cash Request updated successfully']);
            } else {
                return response()->json(['message' => 'API Error: ' . $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Petty Cash Request Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update request: ' . $e->getMessage()], 500);
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
            'amount' => 'required|numeric|gt:0',
            'value' => 'required|string',
        ]);

        try {
            $requestId = Crypt::decryptString($validated['document_id']);

            $userData = Session::get('user_data');
            $sessionClientId = Session::get('idempiere_client');
            $sessionUserId = is_array($userData) ? ($userData['userId'] ?? 0) : ($userData->userId ?? 0);

            $payload = [
                'Description' => $validated['description'],
                'Amount' => (float) $validated['amount'],
                'Value' => $validated['value'] ?? null,
            ];

            if (!empty($validated['line_id'])) {
                // Update
                $lineId = $validated['line_id'];
                $response = $this->idempiereService->put("models/dpk_pettycash_requestline/{$lineId}", $payload);
                $action = 'updated';
            } else {
                // Create - Get next line number
                $lastLine = DB::connection('idempiere')
                    ->table('dpk_pettycash_requestline')
                    ->where('dpk_pettycash_request_id', $requestId)
                    ->max('line');
                $nextLine = ($lastLine ?? 0) + 10;

                $payload['AD_Client_ID'] = (int) $sessionClientId;
                $payload['DPK_PettyCash_Request_ID'] = (int) $requestId;
                $payload['Line'] = $nextLine;
                $response = $this->idempiereService->post("models/dpk_pettycash_requestline", $payload);
                $action = 'created';
            }

            if ($response->successful()) {
                // Fetch updated total
                $total = DB::connection('idempiere')
                    ->table('dpk_pettycash_requestline')
                    ->where('dpk_pettycash_request_id', $requestId)
                    ->where('isactive', 'Y')
                    ->sum('amount');

                if ($requestId) {
                    DB::connection('idempiere')
                        ->table('dpk_pettycash_request')
                        ->where('dpk_pettycash_request_id', $requestId)
                        ->update(['totallines' => $total]);
                }

                return response()->json([
                    'message' => "Line $action successfully",
                    'total' => number_format($total ?? 0, 2)
                ]);
            } else {
                Log::error("Petty Cash Line Error ($action): " . $response->body());
                return response()->json(['message' => "Failed to $action line: " . $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Petty Cash Line Error: ' . $e->getMessage());
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
            $requestId = null;
            foreach ($validated['line_ids'] as $lineId) {
                $lineId = (int) $lineId;

                if (!$requestId) {
                    $line = DB::connection('idempiere')
                        ->table('dpk_pettycash_requestline')
                        ->where('dpk_pettycash_requestline_id', $lineId)
                        ->select('dpk_pettycash_request_id')
                        ->first();
                    $requestId = $line->dpk_pettycash_request_id ?? null;
                }

                $response = $this->idempiereService->delete("models/dpk_pettycash_requestline/{$lineId}");

                if (!$response->successful()) {
                    $errorBody = $response->json('detail') ?? $response->body();
                    Log::error("Petty Cash Line Delete Failed (line_id={$lineId}): " . $response->body());
                    return response()->json([
                        'message' => "Failed to delete line #{$lineId}: {$errorBody}"
                    ], $response->status() ?: 500);
                }
            }

            // Fetch updated total
            $updatedTotals = [];
            if ($requestId) {
                $total = DB::connection('idempiere')
                    ->table('dpk_pettycash_requestline')
                    ->where('dpk_pettycash_request_id', $requestId)
                    ->where('isactive', 'Y')
                    ->sum('amount');
                DB::connection('idempiere')
                    ->table('dpk_pettycash_request')
                    ->where('dpk_pettycash_request_id', $requestId)
                    ->update(['totallines' => $total]);

                $updatedTotals['total'] = number_format($total ?? 0, 2);
            }

            return response()->json(array_merge(['message' => 'Line(s) deleted successfully'], $updatedTotals));

        } catch (\Exception $e) {
            Log::error('Petty Cash Line Delete Error: ' . $e->getMessage());
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
            $requestId = Crypt::decryptString($validated['document_id']);

            $payload = [
                'doc-action' => $validated['doc_action']
            ];

            Log::info('Processing Petty Cash Request', [
                'request_id' => $requestId,
                'action' => $validated['doc_action']
            ]);

            $response = $this->idempiereService->put("models/dpk_pettycash_request/{$requestId}", $payload);

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
            Log::error('Petty Cash Process Error: ' . $e->getMessage());
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
                "models/dpk_pettycash_request/{$docId}/attachments",
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
                ->table('dpk_pettycash_request')
                ->where('dpk_pettycash_request_id', $docId)
                ->select('docstatus', 'documentno')
                ->first();

            if (!$doc) {
                return response()->json(['message' => 'Petty Cash Request not found.'], 404);
            }

            if ($doc->docstatus !== 'DR') {
                return response()->json(['message' => 'Only Draft requests can be deleted.'], 422);
            }

            $response = $this->idempiereService->delete("models/dpk_pettycash_request/{$docId}");

            if (!$response->successful()) {
                $errorBody = $response->json('detail') ?? $response->body();
                return response()->json(['message' => 'Failed to delete: ' . $errorBody], $response->status() ?: 500);
            }

            return response()->json(['message' => 'Petty Cash Request ' . $doc->documentno . ' deleted successfully.']);
        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID.'], 400);
        } catch (\Exception $e) {
            Log::error('Petty Cash Request Delete Error: ' . $e->getMessage());
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

            $response = $this->idempiereService->delete("models/dpk_pettycash_request/{$docId}/attachments/{$encodedId}");

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

            $url = "models/dpk_pettycash_request/{$docId}/attachments/{$encodedFileName}";
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
        return redirect()->route('petty-cash-request.index', ['document_id' => 'new']);
    }
}
