<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaskTime extends Model
{

    protected $fillable = [
        'user_task_id', 'date_start', 'date_stop', 'task_id', 'section', 'time', 'id'
    ];

    public $timestamps = false;

    protected $table = 'task_time';

    public function UserTask()
    {
        return $this->belongsTo('App\UserTask');
    }

}
