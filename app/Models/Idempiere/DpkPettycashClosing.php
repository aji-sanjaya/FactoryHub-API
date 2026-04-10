<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class DpkPettycashClosing extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'dpk_pettycash_closing';
    protected $primaryKey = 'dpk_pettycash_closing_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_client_id',
        'ad_org_id',
        'c_bpartner_id',
        'ad_user_id',
        'c_currency_id',
        'c_doctypetarget_id',
        'documentno',
        'datetrx',
        'dateacct',
        'description',
        'docaction',
        'docstatus',
        'processed',
        'processing',
        'posted',
        'totallines',
        'dpk_cost_center_id',
        'dpk_pettycash_request_id',
        'created',
        'createdby',
        'updated',
        'updatedby',
        'isactive',
    ];

    protected $casts = [
        'datetrx' => 'datetime',
        'dateacct' => 'datetime',
        'created' => 'datetime',
        'updated' => 'datetime',
        'totallines' => 'decimal:2',
    ];

    /**
     * Relationship to lines
     */
    public function lines()
    {
        return $this->hasMany(DpkPettycashClosingLine::class, 'dpk_pettycash_closing_id', 'dpk_pettycash_closing_id')
            ->where('isactive', 'Y')
            ->orderBy('dpk_pettycash_closingline_id');
    }

    /**
     * Relationship to Petty Cash Request
     */
    public function pettyCashRequest()
    {
        return $this->belongsTo(\App\Models\Idempiere\DpkPettycashRequest::class, 'dpk_pettycash_request_id', 'dpk_pettycash_request_id');
    }

    /**
     * Relationship to organization
     */
    public function organization()
    {
        return $this->belongsTo(\App\Models\Idempiere\ADOrg::class, 'ad_org_id', 'ad_org_id');
    }

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\Idempiere\ADUser::class, 'ad_user_id', 'ad_user_id');
    }

    /**
     * Relationship to currency
     */
    public function currency()
    {
        return $this->belongsTo(\App\Models\Idempiere\CCurrency::class, 'c_currency_id', 'c_currency_id');
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'DR' => 'Draft',
            'IP' => 'In Progress',
            'CO' => 'Completed',
            'CL' => 'Closed',
            'VO' => 'Voided',
            'RE' => 'Reversed',
        ];

        return $labels[$this->docstatus] ?? 'Unknown';
    }

    /**
     * Check if document is editable
     */
    public function isEditable()
    {
        return in_array($this->docstatus, ['DR', 'IP']);
    }

    /**
     * Check if document is draft
     */
    public function isDraft()
    {
        return $this->docstatus === 'DR';
    }
}
