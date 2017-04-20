<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false;


    protected $fillable = [
        'name', 'email', 'password', 'surname', 'name', 'group_id', 'level','avatar'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    public function tasks()
    {
        return $this->belongsToMany('App\Task', 'user_task')->withPivot('id','task_id','status_internal', 'schedule_day', 'accept', 'section','order_num');
    }

    public function group()
    {
        return $this->belongsTo('App\Group');
    }
}
