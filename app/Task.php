<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = array();  // Important
    public $timestamps = false;

    public function user_task()
    {
        return $this->haveMany('App\UserTask');
    }

}
