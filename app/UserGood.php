<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGood extends Model
{
    protected $guarded = [];
    protected $hidden = [
        'id','created_at','updated_at',
    ];
}
