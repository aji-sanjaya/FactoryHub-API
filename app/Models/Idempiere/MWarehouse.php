<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class MWarehouse extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'm_warehouse';
    protected $primaryKey = 'm_warehouse_id';
    public $timestamps = false; // iDempiere standard

    protected $fillable = [
        'm_warehouse_id',
        'ad_client_id',
        'ad_org_id',
        'value',
        'name',
        'description',
        'isactive',
    ];

    public function organization()
    {
        return $this->belongsTo(AdOrg::class, 'ad_org_id', 'ad_org_id');
    }
}
