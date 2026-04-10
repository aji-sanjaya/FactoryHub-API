<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class CPaymentAllocate extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'c_paymentallocate';
    protected $primaryKey = 'c_paymentallocate_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
        'c_payment_id',
        'c_invoice_id',
        'amount',
        'discountamt',
        'writeoffamt',
        'overunderamt',
    ];

    public function payment()
    {
        return $this->belongsTo(CPayment::class, 'c_payment_id', 'c_payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(CInvoice::class, 'c_invoice_id', 'c_invoice_id');
    }
}
