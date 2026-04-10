<?php

namespace App\Models\Idempiere;

use Illuminate\Database\Eloquent\Model;

class AdClient extends Model
{
    protected $connection = 'idempiere';
    protected $table = 'ad_client';
    protected $primaryKey = 'ad_client_id';
    public $timestamps = false; // Usually iDempiere tables don't have Laravel style timestamps

    protected $fillable = [
        'ad_client_id',
        'value',
        'name',
        'description',
        'isactive',
    ];

    public function users()
    {
        return $this->hasMany(AdUser::class, 'ad_client_id', 'ad_client_id');
    }
}
