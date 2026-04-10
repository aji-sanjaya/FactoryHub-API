<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class MInOut extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'm_inout';
    protected $primaryKey = 'm_inout_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
        'documentno',
        'docstatus',
        'docaction',
        'c_doctype_id',
        'movementdate',
        'dateacct',
        'c_bpartner_id',
        'c_bpartner_location_id',
        'm_warehouse_id',
        'movementtype',
        'issotrx',
        'description',
        'c_order_id',
        'salesrep_id',
        'dpk_ad_user_checked_id',
        'dpk_ad_user_approved_id',
        'dpk_checked_date',
        'dpk_approved_date',
        'dpk_checked_isapproved',
        'dpk_approve_isapproved',
    ];

    public function getStatusLabelAttribute()
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
        ];
        return $map[$this->docstatus] ?? $this->docstatus;
    }

    public function lines()
    {
        return $this->hasMany(MInOutLine::class, 'm_inout_id', 'm_inout_id');
    }
}
