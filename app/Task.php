<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = array();  // Important
    public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany('App\User');
    }
}
