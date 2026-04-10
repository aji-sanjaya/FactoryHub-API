<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class CInOutLine extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'm_inoutline';
    protected $primaryKey = 'm_inoutline_id';
    public $timestamps = false; // iDempiere standard

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
        'm_inout_id',
        'line',
        'm_product_id',
        'movementqty',
        'qtyentered',
        'c_uom_id',
        'm_locator_id',
        'c_orderline_id',
        'description',
        'c_charge_id',
        'isDescription',
        'm_attributesetinstance_id',
        'c_project_id',
        'c_projectphase_id',
        'c_projecttask_id',
        'ref_inoutline_id',
        'isdescription',
    ];

    // Relationships
    public function inout()
    {
        return $this->belongsTo(CInOut::class, 'm_inout_id', 'm_inout_id');
    }
}
