<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class AdRole extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'ad_role';
    protected $primaryKey = 'ad_role_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_role_id',
        'ad_client_id',
        'ad_org_id',
        'name',
        'description',
        'isactive',
    ];

    public function users()
    {
        return $this->belongsToMany(
            AdUser::class,
            'ad_user_roles',
            'ad_role_id',
            'ad_user_id'
        );
    }
}
