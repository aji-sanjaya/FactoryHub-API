<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class AdUser extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'ad_user';
    protected $primaryKey = 'ad_user_id';
    public $timestamps = false;

    protected $fillable = [
        'ad_user_id',
        'ad_client_id',
        'ad_org_id',
        'value',
        'name',
        'description',
        'password',
        'email',
        'phone',
        'logo',
        'ad_image_id',
        'isactive',
    ];

    public function client()
    {
        return $this->belongsTo(AdClient::class, 'ad_client_id', 'ad_client_id');
    }
}
