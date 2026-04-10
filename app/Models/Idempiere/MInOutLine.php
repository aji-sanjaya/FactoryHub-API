<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class MInOutLine extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'm_inoutline';
    protected $primaryKey = 'm_inoutline_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'isactive',
        'm_inout_id',
        'line',
        'm_product_id',
        'movementqty',
        'c_uom_id',
        'c_orderline_id',
        'description',
        'qtyentered',
        'c_uom_to_id',
        'm_locator_id',
        'm_locatorto_id',
    ];

    public function inout()
    {
        return $this->belongsTo(MInOut::class, 'm_inout_id', 'm_inout_id');
    }
}
