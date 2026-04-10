<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class COrder extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'c_order';
    protected $primaryKey = 'c_order_id';
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
        'c_doctypetarget_id',
        'dateordered',
        'datepromised',
        'c_bpartner_id',
        'c_bpartner_location_id',
        'description',
        'm_warehouse_id',
        'm_pricelist_id',
        'c_currency_id',
        'paymentrule',
        'c_paymentterm_id',
        'issotrx',
        'totallines',
        'grandtotal',
        'freightamt',
        'chargeamt',
        'dpk_ad_user_checked_id',
        'dpk_ad_user_approved_id',
        'priorityrule',
        'invoicerule',
        'deliveryrule',
        'freightcostrule',
        'deliveryviarule',
        'salesrep_id',
        'ad_user_id',
        'bill_user_id',
        'bill_bpartner_id',
        'bill_location_id',
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
        return $this->hasMany(COrderLine::class, 'c_order_id', 'c_order_id');
    }
}
