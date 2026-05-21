<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idempiere\COrder;
use Illuminate\Support\Facades\Crypt;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalPoController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    public function index(Request $request)
    {
        $approvalConfig = config('idempiere.approval-po');
        $statusConfig = $approvalConfig['statuses'];
        $workflowConfig = $approvalConfig['workflow'];

        // Check Authentication
        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        $clientId = Session::get('idempiere_client');
        $orgId = Session::get('idempiere_org');

        // Fetch Selected Filter Data for pre-filling Select2
        $selectedSupplier = null;
        if ($request->has('c_bpartner_id') && $request->c_bpartner_id) {
            $selectedSupplier = DB::connection('idempiere')->table('c_bpartner')
                ->where('c_bpartner_id', $request->c_bpartner_id)
                ->select('c_bpartner_id as id', 'name as text')
                ->first();
        }

        // Query with Joins
        $query = COrder::query()
            ->select(
                'c_order.*',
                DB::raw("(SELECT cb.name FROM c_bpartner cb WHERE cb.c_bpartner_id = c_order.c_bpartner_id) as bpartner_name")
            );

        // Check for User's Approval Task
        $roleId = Session::get('idempiere_role');

        $query->addSelect([
            'is_my_approval' => DB::connection('idempiere')
                ->table('ad_wf_process as p')
                ->join('ad_wf_activity as a', 'a.ad_wf_process_id', '=', 'p.ad_wf_process_id')
                ->join('ad_wf_responsible as wr', 'wr.ad_wf_responsible_id', '=', 'a.ad_wf_responsible_id')
                ->whereColumn('p.record_id', 'c_order.c_order_id')
                ->where('p.ad_table_id', $workflowConfig['table_id'])
                ->where('a.wfstate', $workflowConfig['open_state'])
                ->where('a.isactive', 'Y')
                // Check if responsibility is assigned to current Role
                ->where('wr.ad_role_id', $roleId)
                ->selectRaw('COUNT(*)')
        ]);

        $query->where('issotrx', $approvalConfig['defaults']['is_so_trx'])
            ->whereNotIn('docstatus', $statusConfig['exclude_from_list']);

        if ($clientId) {
            $query->where('c_order.ad_client_id', $clientId);
        }

        if ($orgId && $orgId > 0) {
            $query->where('c_order.ad_org_id', $orgId);
        }

        // Stats Calculation (Current Month)
        // Using base query with simple filters
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        $statsQuery = COrder::query()->where('issotrx', $approvalConfig['defaults']['is_so_trx']);
        if ($clientId)
            $statsQuery->where('ad_client_id', $clientId);
        if ($orgId && $orgId > 0)
            $statsQuery->where('ad_org_id', $orgId);

        $statsBase = clone $statsQuery;
        $statsBase->whereBetween('dateordered', [$startOfMonth, $endOfMonth]);

        $countPending = (clone $statsBase)->where('docstatus', $statusConfig['pending'])->count();
        $countApproved = (clone $statsBase)->whereIn('docstatus', $statusConfig['approved'])->count();
        $countRejected = (clone $statsBase)->where('docstatus', $statusConfig['rejected'])->count();
        $countAll = $statsBase->count();

        // Filtering List
        $status = $request->get('status', $approvalConfig['defaults']['status_filter']);
        if ($status !== $approvalConfig['defaults']['all_filter_value']) {
            if (isset($statusConfig['filter_aliases'][$status])) {
                $query->whereIn('c_order.docstatus', $statusConfig['filter_aliases'][$status]);
            } else {
                $query->where('c_order.docstatus', $status);
            }
        }

        if ($request->has('search') && $request->search) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('c_order.documentno', 'ilike', "%{$term}%")
                    ->orWhere('c_order.description', 'ilike', "%{$term}%")
                    ->orWhereExists(function ($sub) use ($term) {
                        $sub->select(DB::raw(1))
                            ->from('c_bpartner as cbsearch')
                            ->whereColumn('cbsearch.c_bpartner_id', 'c_order.c_bpartner_id')
                            ->where('cbsearch.name', 'ilike', "%{$term}%");
                    });
            });
        }

        if ($request->has('c_bpartner_id') && $request->c_bpartner_id) {
            $query->where('c_order.c_bpartner_id', $request->c_bpartner_id);
        }

        $orders = $query->orderBy('c_order.dateordered', 'desc')->paginate($approvalConfig['limits']['list_per_page']);

        if ($request->ajax()) {
            return view('pages.approval-po.partials.table', compact('orders'));
        }

        return view('pages.approval-po.index', compact(
            'orders',
            'countPending',
            'countApproved',
            'countRejected',
            'countAll',
            'selectedSupplier'
        ));
    }

    public function getSuppliers(Request $request)
    {
        $approvalConfig = config('idempiere.approval-po');
        $clientId = Session::get('idempiere_client');
        $search = $request->term;
        $page = $request->page ?? 1;
        $perPage = $approvalConfig['limits']['select2_per_page'];

        $query = DB::connection('idempiere')->table('c_bpartner')
            ->where('isactive', 'Y')
            ->where('isvendor', 'Y')
            ->where('ad_client_id', $clientId)
            ->select('c_bpartner_id as id', 'name as text');

        if ($search) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $results = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $results->items(),
            'pagination' => ['more' => $results->hasMorePages()]
        ]);
    }

    public function show($id)
    {
        $approvalConfig = config('idempiere.approval-po');

        // Decrypt ID
        try {
            $decryptedId = Crypt::decryptString($id);
            $order = COrder::find($decryptedId);
        } catch (\Exception $e) {
            // Fallback for non-encrypted IDs (dev/test)
            $order = COrder::find($id);
        }

        if (!$order) {
            return redirect()->route('approval-po.index')->with('error', 'Order not found');
        }

        // Handle AJAX Tab Requests (Attachments)
        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');

            if ($tab === 'attachments') {
                // Fetch attachments
                $url = "models/c_order/{$order->c_order_id}/attachments";
                $response = $this->idempiereService->get($url);
                $attachments = [];

                if ($response->successful()) {
                    $json = $response->json();
                    if (isset($json['attachments'])) {
                        $attachments = json_decode(json_encode($json['attachments']), FALSE);
                    } elseif (isset($json['records'])) {
                        $attachments = json_decode(json_encode($json['records']), FALSE);
                    }
                }

                return view('pages.approval-po.partials.tab-attachments', [
                    'attachments' => $attachments,
                    'isReadOnly' => true,
                    'docIdParam' => $id,
                    'order' => $order
                ]);
            }
        }

        // Check for User's Approval Task to control button visibility
        $roleId = Session::get('idempiere_role');

        $isMyApproval = DB::connection('idempiere')
            ->table('ad_wf_process as p')
            ->join('ad_wf_activity as a', 'a.ad_wf_process_id', '=', 'p.ad_wf_process_id')
            ->join('ad_wf_responsible as wr', 'wr.ad_wf_responsible_id', '=', 'a.ad_wf_responsible_id')
            ->where('p.record_id', $order->c_order_id)
            ->where('p.ad_table_id', $approvalConfig['workflow']['table_id'])
            ->where('a.wfstate', $approvalConfig['workflow']['open_state'])
            ->where('a.isactive', 'Y')
            ->where('wr.ad_role_id', $roleId)
            ->exists();

        // Basic View Data
        return view('pages.approval-po.show', [
            'order' => $order,
            'encryptedId' => Crypt::encryptString($order->c_order_id),
            'title' => 'Approval PO: ' . $order->documentno,
            'isMyApproval' => $isMyApproval
        ]);
    }

    public function process(Request $request, $id)
    {
        $approvalConfig = config('idempiere.approval-po');
        $workflowConfig = $approvalConfig['workflow'];

        try {
            $decryptedId = Crypt::decryptString($id);
        } catch (\Exception $e) {
            $decryptedId = $id;
        }

        $validated = $request->validate([
            'action' => 'required|in:' . implode(',', $workflowConfig['allowed_actions']),
            'comment' => 'nullable|string',
        ]);

        $action = $validated['action'];
        $comment = $validated['comment'] ?? null;

        // Fetch the AD_WF_Activity_ID for this record and user/role
        $roleId = Session::get('idempiere_role');

        $activity = DB::connection('idempiere')
            ->table('ad_wf_process as p')
            ->join('ad_wf_activity as a', 'a.ad_wf_process_id', '=', 'p.ad_wf_process_id')
            ->join('ad_wf_responsible as wr', 'wr.ad_wf_responsible_id', '=', 'a.ad_wf_responsible_id')
            ->where('p.record_id', $decryptedId) // C_Order_ID
            ->where('p.ad_table_id', $workflowConfig['table_id'])
            ->where('a.wfstate', $workflowConfig['open_state'])
            ->where('a.isactive', 'Y')
            ->where('wr.ad_role_id', $roleId)
            ->select('a.ad_wf_activity_id')
            ->first();

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => $workflowConfig['no_activity_message']
            ], 404);
        }

        $activityId = $activity->ad_wf_activity_id;
        $endpoint = ($workflowConfig['endpoints'][$action] ?? $workflowConfig['endpoints']['REJECT']) . "/{$activityId}";

        // Payload
        $payload = [
            'comment' => $comment
        ];

        // Call API
        // Try PUT as POST returned 405
        $response = $this->idempiereService->put($endpoint, $payload);

        if ($response->successful()) {

            // Custom Logic: Direct DB Update for Custom Columns
            try {
                // Determine User ID (Approver)
                $userData = Session::get('user_data');
                $userId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? 0;

                // Determine Status Code
                $statusCode = $workflowConfig['custom_column_statuses'][$action] ?? null;
                $now = date('Y-m-d H:i:s');

                // Check Current State to decide Step 1 vs Step 2
                $order = DB::connection('idempiere')
                    ->table('c_order')
                    ->where('c_order_id', $decryptedId)
                    ->select('adw_checked_isapproved', 'adw_checked_date')
                    ->first();

                $updateData = [];

                // Logic matching PurchaseOrderController print/signature flow
                if (!$order || is_null($order->adw_checked_date)) {
                    // Step 1: Checked
                    $updateData = [
                        'adw_ad_user_checked_id' => $userId,
                        'adw_checked_date' => $now,
                        'adw_checked_isapproved' => $statusCode
                    ];
                } else {
                    // Step 2: Approved
                    $updateData = [
                        'adw_ad_user_approved_id' => $userId,
                        'adw_approved_date' => $now,
                        'adw_approve_isapproved' => $statusCode
                    ];
                }

                if (!empty($updateData)) {
                    DB::connection('idempiere')
                        ->table('c_order')
                        ->where('c_order_id', $decryptedId)
                        ->update($updateData);
                }

            } catch (\Exception $e) {
                Log::error('Failed to update Custom Approval PO Columns: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => $workflowConfig['success_messages'][$action] ?? 'Action completed successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Action failed: ' . $response->body()
        ], 400);
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
}
