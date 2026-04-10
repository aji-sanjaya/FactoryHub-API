<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class MRequisition extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'm_requisition';
    protected $primaryKey = 'm_requisition_id';
    public $timestamps = false;

    protected $fillable = [
        'm_requisition_id',
        'ad_client_id',
        'ad_org_id',
        'documentno',
        'description',
        'datedoc',
        'daterequired',
        'docstatus',
        'totalines',
        'created',
        'createdby',
        'updated',
        'updatedby'
    ];

    // Status Constants
    const STATUS_DRAFTED = 'DR';
    const STATUS_IN_PROGRESS = 'IP';
    const STATUS_COMPLETED = 'CO';
    const STATUS_CLOSED = 'CL';
    const STATUS_APPROVED = 'AP';
    const STATUS_INVALID = 'IN';
    const STATUS_VOIDED = 'VO';
    const STATUS_REVERSED = 'RE';

    public function getStatusLabelAttribute()
    {
        return match ($this->docstatus) {
            self::STATUS_DRAFTED => 'Drafted',
            self::STATUS_INVALID => 'Drafted', // Treat Invalid as Draft-like
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_APPROVED => 'Approved', // Could be considered In Progress or separate
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_VOIDED => 'Voided',
            self::STATUS_REVERSED => 'Reversed',
            default => 'Unknown',
        };
    }

    public function org()
    {
        return $this->belongsTo(AdOrg::class, 'ad_org_id', 'ad_org_id');
    }

    public function client()
    {
        return $this->belongsTo(AdClient::class, 'ad_client_id', 'ad_client_id');
    }
}
