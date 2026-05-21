<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class CInvoice extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'c_invoice';
    protected $primaryKey = 'c_invoice_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
        'issotrx',
        'documentno',
        'docstatus',
        'docaction',
        'c_doctype_id',
        'c_doctypetarget_id',
        'dateinvoiced',
        'dateacct',
        'dateordered',
        'c_bpartner_id',
        'c_bpartner_location_id',
        'ad_user_id',
        'poreference',
        'description',
        'c_currency_id',
        'c_paymentterm_id',
        'paymentrule',
        'm_pricelist_id',
        'istaxincluded',
        'totallines',
        'grandtotal',
        'c_order_id',
        'c_project_id',
        'c_tax_id',
        'ispaid',
        'isapproved',
        'processed',
        'withholdingamount',
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
            'AP' => 'Approved',
            'WP' => 'Awaiting Payment',
        ];
        return $map[$this->docstatus] ?? $this->docstatus;
    }

    public function lines()
    {
        return $this->hasMany(CInvoiceLine::class, 'c_invoice_id', 'c_invoice_id');
    }
}
