<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class AdOrg extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'ad_org';
    protected $primaryKey = 'ad_org_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_org_id',
        'ad_client_id',
        'value',
        'name',
        'description',
        'isactive',
    ];
}
