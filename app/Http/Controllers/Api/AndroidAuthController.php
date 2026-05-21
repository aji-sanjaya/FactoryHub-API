<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AndroidAuthController extends Controller
{
    public function getClients(Request $request)
    {
        $clients = DB::connection('idempiere')->select("
            SELECT ad_client_id as id, name as text 
            FROM ad_client 
            WHERE isactive = 'Y' 
              AND ad_client_id > 0 
            ORDER BY name
        ");

        return response()->json($clients);
    }

    public function getRoles(Request $request)
    {
        $clientId = $request->query('client_id');
        $userId = $request->query('user_id');

        if (!$userId || !$clientId) {
            return response()->json([]);
        }

        $roles = DB::connection('idempiere')->select("
            SELECT DISTINCT r.ad_role_id as id, r.name as text
            FROM ad_role r
            JOIN ad_user_roles ur ON ur.ad_role_id = r.ad_role_id
            JOIN ad_user u ON u.ad_user_id = ur.ad_user_id
            WHERE r.isactive = 'Y'
              AND ur.isactive = 'Y'
              AND u.isactive = 'Y'
              AND u.ad_user_id = ?
              AND r.ad_client_id IN (0, ?)
            ORDER BY r.name
        ", [$userId, $clientId]);

        return response()->json($roles);
    }

    public function getOrgs(Request $request)
    {
        $clientId = $request->query('client_id');
        $roleId = $request->query('role_id');
        $userId = $request->query('user_id');

        if (!$userId || !$clientId || !$roleId) {
            return response()->json([]);
        }

        $orgs = DB::connection('idempiere')->select("
            SELECT DISTINCT o.ad_org_id as id, o.name as text
            FROM ad_org o
            JOIN ad_role_orgaccess roa ON roa.ad_org_id = o.ad_org_id
            JOIN ad_user_roles ur ON ur.ad_role_id = roa.ad_role_id
            JOIN ad_user u ON u.ad_user_id = ur.ad_user_id
            WHERE o.isactive = 'Y'
              AND roa.isactive = 'Y'
              AND u.isactive = 'Y'
              AND u.ad_user_id = ?
              AND ur.ad_role_id = ?
              AND o.ad_client_id = ?
            ORDER BY o.ad_org_id
        ", [$userId, $roleId, $clientId]);

        // Add wildcard option
        array_unshift($orgs, (object) ['id' => 0, 'text' => '*']);

        return response()->json($orgs);
    }

    public function getWarehouses(Request $request)
    {
        $roleId = $request->query('role_id');
        $orgId = $request->query('org_id');

        if (!$roleId || !$orgId) {
            return response()->json([]);
        }

        $warehouses = DB::connection('idempiere')->select("
            SELECT DISTINCT w.m_warehouse_id as id, w.name as text
            FROM m_warehouse w
            JOIN ad_role_orgaccess roa ON roa.ad_org_id = w.ad_org_id
            WHERE w.isactive = 'Y'
              AND roa.isactive = 'Y'
              AND roa.ad_role_id = ?
              AND w.ad_org_id = ?
            ORDER BY w.m_warehouse_id
        ", [$roleId, $orgId]);

        return response()->json($warehouses);
    }
}
