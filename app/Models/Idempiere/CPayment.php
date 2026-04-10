<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class CPayment extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'c_payment';
    protected $primaryKey = 'c_payment_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
        'isreceipt',
        'documentno',
        'docstatus',
        'docaction',
        'c_doctype_id',
        'datetrx',
        'dateacct',
        'c_bpartner_id',
        'c_bpartner_location_id',
        'ad_user_id',
        'description',
        'c_currency_id',
        'paymentrule',
        'tendertype',
        'c_bankaccount_id',
        'payamt',
        'discountamt',
        'writeoffamt',
        'isallocated',
        'isprepayment',
        'processed',
        'posted',
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
        ];
        return $map[$this->docstatus] ?? $this->docstatus;
    }
}
