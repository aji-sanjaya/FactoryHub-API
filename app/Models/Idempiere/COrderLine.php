<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class COrderLine extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'c_orderline';
    protected $primaryKey = 'c_orderline_id';
    public $timestamps = false;

    // Fillable fields for PO Lines
    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
        'c_order_id',
        'line', // Line Number
        'm_product_id',
        'c_uom_id',
        'qtyordered', // Requisition has 'qty', PO has 'qtyordered'
        'qtyentered',
        'priceentered',
        'priceactual',
        'pricelist',
        'linenetamt',
        'description',
        // Withholding Tax (PPh23)
        'iswithholding',
        'withholdingtype_id',
        'withholdingrate',
        'withholdingbaseamt',
        'withholdingamt',
        'withholdingamount',
    ];

    protected $casts = [
        'iswithholding'       => 'boolean',
        'withholdingrate'     => 'float',
        'withholdingbaseamt'  => 'float',
        'withholdingamt'      => 'float',
        'withholdingamount'   => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(COrder::class, 'c_order_id', 'c_order_id');
    }
}
