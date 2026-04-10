<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idempiere\MRequisition;
use Illuminate\Support\Facades\Crypt;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\Session;

class ApprovalPrController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    public function index(Request $request)
    {
        // Check Authentication
        if (!Session::has('api_token')) {
            return redirect()->route('signin');
        }

        $clientId = Session::get('idempiere_client');
        $orgId = Session::get('idempiere_org');

        // Fetch Selected Filter Data for pre-filling Select2
        $selectedSupplier = null;
        if ($request->has('c_bpartner_id') && $request->c_bpartner_id) {
            $selectedSupplier = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_bpartner')
                ->where('c_bpartner_id', $request->c_bpartner_id)
                ->select('c_bpartner_id as id', 'name as text')
                ->first();
        }

        $selectedCostCenter = null;
        if ($request->has('dpk_cost_center_id') && $request->dpk_cost_center_id) {
            $selectedCostCenter = \Illuminate\Support\Facades\DB::connection('idempiere')->table('dpk_cost_center')
                ->where('dpk_cost_center_id', $request->dpk_cost_center_id)
                ->select('dpk_cost_center_id as id', 'name as text')
                ->first();
        }

        // Query with Joins
        // Note: c_bpartner_id is usually on Lines, not Header. dpk_cost_center_id is assumed on Header.
        $query = MRequisition::query()
            ->select(
                'm_requisition.*',
                'cc.name as cost_center_name',
                \Illuminate\Support\Facades\DB::raw("(SELECT cb.name FROM m_requisitionline rl JOIN c_bpartner cb ON cb.c_bpartner_id = rl.c_bpartner_id WHERE rl.m_requisition_id = m_requisition.m_requisition_id AND rl.isactive='Y' LIMIT 1) as bpartner_name")
            );

        // Check for User's Approval Task
        $userData = Session::get('user_data');
        $userId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? 0;
        $roleId = Session::get('idempiere_role');

        $query->addSelect([
            'is_my_approval' => \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('ad_wf_process as p')
                ->join('ad_wf_activity as a', 'a.ad_wf_process_id', '=', 'p.ad_wf_process_id')
                ->join('ad_wf_responsible as wr', 'wr.ad_wf_responsible_id', '=', 'a.ad_wf_responsible_id')
                ->whereColumn('p.record_id', 'm_requisition.m_requisition_id')
                ->where('p.ad_table_id', 702) // M_Requisition
                ->where('a.wfstate', 'OS')
                ->where('a.isactive', 'Y')
                // Check if responsibility is assigned to current Role
                ->where('wr.ad_role_id', $roleId)
                ->selectRaw('COUNT(*)')
        ]);

        $query->leftJoin('dpk_cost_center as cc', 'cc.dpk_cost_center_id', '=', 'm_requisition.dpk_cost_center_id')
            ->whereNotIn('m_requisition.docstatus', ['DR', 'CL']); // Exclude Draft and Closed

        if ($clientId) {
            $query->where('m_requisition.ad_client_id', $clientId);
        }

        if ($orgId && $orgId > 0) {
            $query->where('m_requisition.ad_org_id', $orgId);
        }

        // Stats Calculation (Current Month)
        // Using base query with simple filters
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        $statsQuery = MRequisition::query();
        if ($clientId)
            $statsQuery->where('ad_client_id', $clientId);
        if ($orgId && $orgId > 0)
            $statsQuery->where('ad_org_id', $orgId);

        $statsBase = clone $statsQuery;
        $statsBase->whereBetween('datedoc', [$startOfMonth, $endOfMonth]);

        $countPending = (clone $statsBase)->where('docstatus', 'IP')->count();
        $countApproved = (clone $statsBase)->whereIn('docstatus', ['CO', 'CL'])->count();
        $countRejected = (clone $statsBase)->where('docstatus', 'VO')->count();
        $countAll = $statsBase->count();

        // Filtering List
        $status = $request->get('status', 'IP');
        if ($status !== 'ALL') {
            // Handle status (assuming column exists)
            if ($status === 'APPROVED') {
                $query->whereIn('m_requisition.docstatus', ['CO', 'CL']);
            } elseif ($status === 'REJECTED') {
                $query->where('m_requisition.docstatus', 'VO');
            } else {
                $query->where('m_requisition.docstatus', $status);
            }
        }

        if ($request->has('search') && $request->search) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('m_requisition.documentno', 'ilike', "%{$term}%")
                    ->orWhere('m_requisition.description', 'ilike', "%{$term}%")
                    // Search subquery is hard, maybe ignore supplier search or use whereExists
                    ->orWhereExists(function ($sub) use ($term) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('m_requisitionline as rlsearch')
                            ->join('c_bpartner as cbsearch', 'cbsearch.c_bpartner_id', '=', 'rlsearch.c_bpartner_id')
                            ->whereColumn('rlsearch.m_requisition_id', 'm_requisition.m_requisition_id')
                            ->where('cbsearch.name', 'ilike', "%{$term}%");
                    });
            });
        }

        if ($request->has('c_bpartner_id') && $request->c_bpartner_id) {
            // Filter by Lines existence
            $query->whereExists(function ($sub) use ($request) {
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('m_requisitionline as rlfilter')
                    ->whereColumn('rlfilter.m_requisition_id', 'm_requisition.m_requisition_id')
                    ->where('rlfilter.c_bpartner_id', $request->c_bpartner_id);
            });
        }
        if ($request->has('dpk_cost_center_id') && $request->dpk_cost_center_id) {
            $query->where('m_requisition.dpk_cost_center_id', $request->dpk_cost_center_id);
        }

        $requisitions = $query->orderBy('m_requisition.datedoc', 'desc')->paginate(10);

        if ($request->ajax()) {
            return view('pages.approval-pr.partials.table', compact('requisitions'));
        }

        return view('pages.approval-pr.index', compact(
            'requisitions',
            'countPending',
            'countApproved',
            'countRejected',
            'countAll',
            'selectedSupplier',
            'selectedCostCenter'
        ));
    }

    public function getSuppliers(Request $request)
    {
        $clientId = Session::get('idempiere_client');
        $search = $request->term;
        $page = $request->page ?? 1;
        $perPage = 10;

        $query = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_bpartner')
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

    public function getCostCenters(Request $request)
    {
        $clientId = Session::get('idempiere_client');
        $search = $request->term;
        $page = $request->page ?? 1;
        $perPage = 10;

        $query = \Illuminate\Support\Facades\DB::connection('idempiere')->table('dpk_cost_center')
            ->where('isactive', 'Y')
            ->where('ad_client_id', $clientId)
            ->select('dpk_cost_center_id as id', 'name as text');

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
        // Decrypt ID
        try {
            $decryptedId = Crypt::decryptString($id);
            $requisition = MRequisition::find($decryptedId);
        } catch (\Exception $e) {
            // Fallback for non-encrypted IDs (dev/test)
            $requisition = MRequisition::find($id);
        }

        if (!$requisition) {
            return redirect()->route('approval-pr.index')->with('error', 'Requisition not found');
        }

        // Handle AJAX Tab Requests (Attachments)
        if (request()->ajax() && request()->has('ajax_tab')) {
            $tab = request()->get('ajax_tab');

            if ($tab === 'attachments') {
                // Fetch attachments
                $url = "models/m_requisition/{$requisition->m_requisition_id}/attachments";
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

                return view('pages.requisition.partials.tab-attachments', [
                    'attachments' => $attachments,
                    'isReadOnly' => true, // Approvers view attachments as read-only typically
                    'docIdParam' => $id, // Pass the encrypted/original ID from URL
                    'requisition' => $requisition
                ]);
            }
        }

        // Check for User's Approval Task to control button visibility
        $userData = Session::get('user_data');
        // $userId = $userData['userId'] ?? ... (Not used for role check)
        $roleId = Session::get('idempiere_role');

        $isMyApproval = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('ad_wf_process as p')
            ->join('ad_wf_activity as a', 'a.ad_wf_process_id', '=', 'p.ad_wf_process_id')
            ->join('ad_wf_responsible as wr', 'wr.ad_wf_responsible_id', '=', 'a.ad_wf_responsible_id')
            ->where('p.record_id', $requisition->m_requisition_id)
            ->where('p.ad_table_id', 702) // M_Requisition
            ->where('a.wfstate', 'OS')
            ->where('a.isactive', 'Y')
            ->where('wr.ad_role_id', $roleId)
            ->exists();

        // Basic View Data
        return view('pages.approval-pr.show', [
            'requisition' => $requisition,
            'encryptedId' => Crypt::encryptString($requisition->m_requisition_id),
            'title' => 'Approval PR: ' . $requisition->documentno,
            'isMyApproval' => $isMyApproval
        ]);
    }

    public function process(Request $request, $id)
    {
        try {
            $decryptedId = Crypt::decryptString($id);
        } catch (\Exception $e) {
            $decryptedId = $id;
        }

        $action = $request->input('action');
        $comment = $request->input('comment');

        // Fetch the AD_WF_Activity_ID for this record and user/role
        $roleId = Session::get('idempiere_role');

        $activity = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('ad_wf_process as p')
            ->join('ad_wf_activity as a', 'a.ad_wf_process_id', '=', 'p.ad_wf_process_id')
            ->join('ad_wf_responsible as wr', 'wr.ad_wf_responsible_id', '=', 'a.ad_wf_responsible_id')
            ->where('p.record_id', $decryptedId) // M_Requisition_ID
            ->where('p.ad_table_id', 702)
            ->where('a.wfstate', 'OS')
            ->where('a.isactive', 'Y')
            ->where('wr.ad_role_id', $roleId)
            ->select('a.ad_wf_activity_id')
            ->first();

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'No active workflow activity found for your role on this document.'
            ], 404);
        }

        $activityId = $activity->ad_wf_activity_id;
        $endpoint = $action === 'APPROVE' ? "workflow/approve/{$activityId}" : "workflow/reject/{$activityId}";

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
                $userData = \Illuminate\Support\Facades\Session::get('user_data');
                $userId = $userData['userId'] ?? $userData['id'] ?? $userData['ad_user_id'] ?? 0;

                // Determine Status Code
                $statusCode = ($action === 'APPROVE') ? 'AP' : 'RE';
                $now = date('Y-m-d H:i:s');

                // Check Current State to decide Step 1 vs Step 2
                $req = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_requisition')
                    ->where('m_requisition_id', $decryptedId)
                    ->select('dpk_checked_isapproved', 'dpk_checked_date')
                    ->first();

                $updateData = [];

                // Logic: If Step 1 (Checked) is empty, fill it. Else fill Step 2 (Approved).
                // Note: If Step 1 was rejected, flow likely stops, but if we are here approving, 
                // it implies we are at the right step? 
                // Wait, if I am Approver 2, Step 1 should be 'AP'.
                if (!$req || is_null($req->dpk_checked_date)) {
                    // Step 1: Checked
                    $updateData = [
                        'dpk_ad_user_checked_id' => $userId,
                        'dpk_checked_date' => $now,
                        'dpk_checked_isapproved' => $statusCode
                    ];
                } else {
                    // Step 2: Approved
                    $updateData = [
                        'dpk_ad_user_approved_id' => $userId,
                        'dpk_approved_date' => $now,
                        'dpk_approve_isapproved' => $statusCode
                    ];
                }

                if (!empty($updateData)) {
                    \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('m_requisition')
                        ->where('m_requisition_id', $decryptedId)
                        ->update($updateData);
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to update Custom Approval Columns: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => $action === 'APPROVE' ? 'Approved successfully.' : 'Rejected successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Action failed: ' . $response->body()
        ], 400);
    }
}
