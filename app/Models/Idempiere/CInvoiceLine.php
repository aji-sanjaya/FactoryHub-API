<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class CInvoiceLine extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'c_invoiceline';
    protected $primaryKey = 'c_invoiceline_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
        'c_invoice_id',
        'line',
        'm_product_id',
        'c_uom_id',
        'qtyinvoiced',
        'qtyentered',
        'priceactual',
        'priceentered',
        'pricelist',
        'pricelimit',
        'linenetamt',
        'linetotalamt',
        'taxamt',
        'c_tax_id',
        'c_orderline_id',
        'm_inoutline_id',
        'description',
        'c_project_id',
        'isdescription',
        'processed',
    ];

    public function invoice()
    {
        return $this->belongsTo(CInvoice::class, 'c_invoice_id', 'c_invoice_id');
    }
}
