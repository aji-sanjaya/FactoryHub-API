<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class DpkPettycashRequestLine extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'adw_pettycash_requestline';
    protected $primaryKey = 'adw_pettycash_requestline_id';
    public $timestamps = false;

    protected $fillable = [
        'adw_pettycash_request_id',
        'ad_client_id',
        'ad_org_id',
        'description',
        'amount',
        'line',
        'value',
        'name',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'line' => 'integer',
        'created' => 'datetime',
        'updated' => 'datetime',
    ];

    /**
     * Relationship to header
     */
    public function request()
    {
        return $this->belongsTo(DpkPettycashRequest::class, 'adw_pettycash_request_id', 'adw_pettycash_request_id');
    }
}
