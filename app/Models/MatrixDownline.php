<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatrixDownline extends Model
{
    //

    protected $table = 'matrix_downlines';

    protected $fillable = [
        'upline_id', 'downline_id', 'level'
    ];


    public function downlines()
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }
}
