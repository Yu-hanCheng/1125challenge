<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserReward extends Model
{
    protected $guarded = [];
    protected $hidden = [
        'id','hunter_id','created_at','updated_at',
    ];
}
