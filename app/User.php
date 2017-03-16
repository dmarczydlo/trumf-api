<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'surname', 'name', 'group_id', 'level'
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
        return $this->belongsToMany('App\Task', 'user_task')->withPivot('task_start', 'task_stop', 'status', 'schedule_day', 'status', 'accept', 'section');
    }

    public function group()
    {
        return $this->belongsTo('App\Group');
    }
}
