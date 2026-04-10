<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class CInOut extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'm_inout';
    protected $primaryKey = 'm_inout_id';
    public $timestamps = false; // iDempiere standard

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
        'movementtype',
        'm_warehouse_id',
        'c_bpartner_id',
        'c_bpartner_location_id',
        'c_order_id',
        'ad_user_id',
        'description',
        'freightamt',
        'issotrx',
        'dateordered',
        'datereceived',
        'deliveryviarule',
        'freightcostrule',
        'm_shipper_id',
        'salesrep_id',
        'c_charge_id',
        'chargeamt',
        'priorityrule',
        'dateprinted',
        'isprinted',
        'po_reference',
        'ref_inout_id',
        'trxname',
        'volume',
        'weight',
        'pickdate',
        'shipdate',
        'trackingno',
    ];

    // Accessors
    public function getStatusLabelAttribute()
    {
        // Simple mapping, can be expanded
        $map = [
            'DR' => 'Drafted',
            'IP' => 'In Progress',
            'CO' => 'Completed',
            'CL' => 'Closed',
            'VO' => 'Voided',
            'RE' => 'Reversed',
            'NA' => 'Not Approved',
            'IN' => 'Invalid'
        ];
        return $map[$this->docstatus] ?? $this->docstatus;
    }

    // Relationships
    public function lines()
    {
        return $this->hasMany(CInOutLine::class, 'm_inout_id', 'm_inout_id');
    }
}
