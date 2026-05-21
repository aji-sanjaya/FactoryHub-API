<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class DpkPettycashClosingLine extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'adw_pettycash_closingline';
    protected $primaryKey = 'adw_pettycash_closingline_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'adw_pettycash_closing_id',
        'adw_pettycash_request_id',
        'adw_pettycash_requestline_id',
        'name',
        'description',
        'amount',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
    ];

    protected $casts = [
        'created' => 'datetime',
        'updated' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationship to header
     */
    public function header()
    {
        return $this->belongsTo(DpkPettycashClosing::class, 'adw_pettycash_closing_id', 'adw_pettycash_closing_id');
    }

    /**
     * Relationship to Petty Cash Request Line
     */
    public function requestLine()
    {
        return $this->belongsTo(DpkPettycashRequestLine::class, 'adw_pettycash_requestline_id', 'adw_pettycash_requestline_id');
    }
}
