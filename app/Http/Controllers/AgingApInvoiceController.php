<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idempiere\CInvoice;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgingApInvoiceController extends Controller
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
            $selectedSupplier = DB::connection('idempiere')->table('c_bpartner')
                ->where('c_bpartner_id', $request->c_bpartner_id)
                ->select('c_bpartner_id as id', 'name as text')
                ->first();
        }

        // Get current date for aging calculation
        $today = Carbon::today();

        // Query for AP Invoices (issotrx = 'N' means AP/Vendor Invoice)
        $query = CInvoice::query()
            ->select(
                'c_invoice.*',
                DB::raw("(SELECT cb.name FROM c_bpartner cb WHERE cb.c_bpartner_id = c_invoice.c_bpartner_id) as bpartner_name"),
                DB::raw("(SELECT pt.name FROM c_paymentterm pt WHERE pt.c_paymentterm_id = c_invoice.c_paymentterm_id) as paymentterm_name"),
                // Calculate due date based on payment term
                DB::raw("(
                    CASE 
                        WHEN c_invoice.c_paymentterm_id IS NOT NULL THEN 
                            c_invoice.dateinvoiced + (
                                SELECT COALESCE(netdays, 0) 
                                FROM c_paymentterm 
                                WHERE c_paymentterm_id = c_invoice.c_paymentterm_id
                            )
                        ELSE c_invoice.dateinvoiced
                    END
                ) as due_date"),
                // Calculate days overdue (extract days from interval)
                DB::raw("EXTRACT(DAY FROM (
                    CASE 
                        WHEN c_invoice.c_paymentterm_id IS NOT NULL THEN 
                            CURRENT_DATE - (c_invoice.dateinvoiced + (
                                SELECT COALESCE(netdays, 0) 
                                FROM c_paymentterm 
                                WHERE c_paymentterm_id = c_invoice.c_paymentterm_id
                            ))
                        ELSE CURRENT_DATE - c_invoice.dateinvoiced
                    END
                ))::INTEGER as days_overdue"),
                // Calculate outstanding amount (for unpaid invoices, it's the grandtotal)
                DB::raw("CASE WHEN c_invoice.ispaid = 'N' THEN c_invoice.grandtotal ELSE 0 END as outstanding_amount")
            )
            ->where('issotrx', 'N') // AP Invoice (vendor invoice)
            ->where('docstatus', 'CO') // Completed invoices only
            ->where('ispaid', 'N'); // Unpaid invoices only

        if ($clientId) {
            $query->where('c_invoice.ad_client_id', $clientId);
        }

        if ($orgId && $orgId > 0) {
            $query->where('c_invoice.ad_org_id', $orgId);
        }

        // Calculate Summary Stats by Aging Buckets
        $statsQuery = clone $query;
        $allInvoices = $statsQuery->get();

        $count030 = 0;
        $count3160 = 0;
        $count6190 = 0;
        $count90Plus = 0;
        $countAll = $allInvoices->count();

        $amount030 = 0;
        $amount3160 = 0;
        $amount6190 = 0;
        $amount90Plus = 0;

        foreach ($allInvoices as $invoice) {
            $daysOverdue = $invoice->days_overdue;
            $amount = $invoice->outstanding_amount;

            if ($daysOverdue <= 30) {
                $count030++;
                $amount030 += $amount;
            } elseif ($daysOverdue <= 60) {
                $count3160++;
                $amount3160 += $amount;
            } elseif ($daysOverdue <= 90) {
                $count6190++;
                $amount6190 += $amount;
            } else {
                $count90Plus++;
                $amount90Plus += $amount;
            }
        }

        // Filtering
        $agingPeriod = $request->get('aging_period', 'ALL');
        if ($agingPeriod !== 'ALL') {
            // Use full expression instead of alias because PostgreSQL doesn't support aliases in HAVING
            $daysOverdueExpression = "EXTRACT(DAY FROM (
                CASE 
                    WHEN c_invoice.c_paymentterm_id IS NOT NULL THEN 
                        CURRENT_DATE - (c_invoice.dateinvoiced + (
                            SELECT COALESCE(netdays, 0) 
                            FROM c_paymentterm 
                            WHERE c_paymentterm_id = c_invoice.c_paymentterm_id
                        ))
                    ELSE CURRENT_DATE - c_invoice.dateinvoiced
                END
            ))::INTEGER";
            
            $query->havingRaw(match($agingPeriod) {
                '0-30' => "$daysOverdueExpression <= 30",
                '31-60' => "$daysOverdueExpression > 30 AND $daysOverdueExpression <= 60",
                '61-90' => "$daysOverdueExpression > 60 AND $daysOverdueExpression <= 90",
                '90+' => "$daysOverdueExpression > 90",
                default => '1=1'
            });
        }

        if ($request->has('search') && $request->search) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('c_invoice.documentno', 'ilike', "%{$term}%")
                    ->orWhere('c_invoice.description', 'ilike', "%{$term}%")
                    ->orWhereExists(function ($sub) use ($term) {
                        $sub->select(DB::raw(1))
                            ->from('c_bpartner as cbsearch')
                            ->whereColumn('cbsearch.c_bpartner_id', 'c_invoice.c_bpartner_id')
                            ->where('cbsearch.name', 'ilike', "%{$term}%");
                    });
            });
        }

        if ($request->has('c_bpartner_id') && $request->c_bpartner_id) {
            $query->where('c_invoice.c_bpartner_id', $request->c_bpartner_id);
        }

        $invoices = $query->orderByRaw('days_overdue DESC')->paginate(10);

        if ($request->ajax()) {
            return view('pages.aging-ap-invoice-report.partials.table', compact('invoices'));
        }

        return view('pages.aging-ap-invoice-report.index', compact(
            'invoices',
            'count030',
            'count3160',
            'count6190',
            'count90Plus',
            'countAll',
            'amount030',
            'amount3160',
            'amount6190',
            'amount90Plus',
            'selectedSupplier'
        ));
    }

    public function getSuppliers(Request $request)
    {
        $clientId = Session::get('idempiere_client');
        $search = $request->term;
        $page = $request->page ?? 1;
        $perPage = 10;

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
}
