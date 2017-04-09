<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTask extends Model
{
    protected $table = 'user_task';
    public $timestamps = false;
    protected $fillable = ['user_id', 'task_id', 'status_internal', 'accept', 'schedule_day', 'section', 'order_num', 'updated_at', 'graphic_block', 'graver_block'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
