<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenStaked extends Model
{
    
    /*
     * Table Name Specified
     */
    protected $table = 'token_staked';
    public $timestamps = false;
     protected $fillable = ['tnx_id','trnx_id', 'user_id', 'staking_tenure','token_staked','date_staked','    date_staking_updated_at ','status'
    ];
}
