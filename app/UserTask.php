<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTask extends Model
{
    protected $appends = ['sum_time', 'time', 'check_time', 'running'];
    protected $table = 'user_task';
    public $timestamps = false;

    protected $fillable = ['user_id', 'task_id', 'status_internal', 'accept', 'schedule_day', 'section', 'order_num', 'updated_at', 'graphic_block', 'graver_block', 'date_start'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }


    public function task()
    {
        return $this->belongsTo('App\Task');
    }


    /**
     * props to stop unstoped tasks
     */
    public function stopTask()
    {
        if ($this->status_internal == 5) {
            $this->status_internal = 6;
            $this->work_time = strtotime(date('Y-m-d')) - strtotime($this->date_start);
            $this->save();
        }

        if ($this->status_internal == 2) {
            $this->status_internal = 3;
            $this->work_time = strtotime(date('Y-m-d')) - strtotime($this->date_start);
            $this->save();
        }

    }

    /**
     * props to set status task when task start
     */
    public function setStartStatus()
    {
        if ($this->user->group_id === 2)
            $this->status_internal = 2;
        else if ($this->user->group_id === 3)
            $this->status_internal = 5;
    }

    public function setNewWorkTime()
    {
        $this->work_time += strtotime(date('Y-m-d H:i:s')) - strtotime($this->date_start);
    }

    /**
     * props to set status task when task stop
     */
    public function setStopStatus()
    {
        if ($this->user->group_id === 2)
            $this->status_internal = 3;
        else if ($this->user->group_id === 3)
            $this->status_internal = 6;
    }

    /**
     * Props to set status task when task was accepted
     */
    public function setAcceptStatus()
    {
        if ($this->user->group_id === 2) {
            $this->status_internal = 4;
            $this->graphic_block = 0;
        } else if ($this->user->group_id === 3) {
            $this->status_internal = 7;
            $this->graver_block = 0;
        }
    }

    public function moveTask($new_position)
    {

        if ($new_position > $this->order_num) {
            //elements > new posiotion
            $tasks_up = UserTask::where('user_id', $this->user_id)
                ->where('schedule_day', $this->schedule_day)
                ->where('order_num', '>', $new_position)
                ->get();

            foreach ($tasks_up as $task) {
                $task->order_num++;
                $task->save();
            }

            $tasks_down = UserTask::where('user_id', $this->user_id)
                ->where('schedule_day', $this->schedule_day)
                ->where('order_num', '>', $this->order_num)
                ->where('order_num', '<=', $new_position)
                ->get();

            foreach ($tasks_down as $task) {
                $task->order_num--;
                $task->save();
            }


        }
        else {

            $tasks_down = UserTask::where('user_id', $this->user_id)
                ->where('schedule_day', $this->schedule_day)
                ->where('order_num', '>', $this->order_num)
                ->get();

            foreach ($tasks_down as $task) {
                $task->order_num--;
                $task->save();
            }


            $tasks_up = UserTask::where('user_id', $this->user_id)
                ->where('schedule_day', $this->schedule_day)
                ->where('order_num', '>=', $new_position)
                ->where('order_num', '<', $this->order_num)
                ->get();

            foreach ($tasks_up as $task) {
                $task->order_num++;
                $task->save();
            }
        }

        $this->order_num = $new_position;
    }

    /** Output serialize
     * @return array
     */
    public
    function serializeOneRow()
    {
        return [
            'min_lvl' => $this->task->min_lvl,
            'user_id' => $this->user_id,
            'order_number' => $this->task->order_number,
            'status' => $this->task->status,
            'prio' => $this->task->prio,
            'client' => $this->task->client,
            'done' => $this->task->done,
            'image_url' => $this->task->image_url,
            'type' => $this->task->type,
            'productID' => $this->task->productID,
            'time' => $this->time,
            'check_time' => $this->check_time,
            'user_task_id' => $this->id,
            'task_id' => $this->task_id,
            'schedule_day' => $this->schedule_day,
            'accept' => $this->accept,
            'section' => $this->section,
            'order_num' => $this->order_num,
            'sum_time' => $this->sum_time,
            'running' => $this->running,
            'status_internal' => $this->status_internal,
            'date' => $this->task->date_order,
            'reclamation' => $this->reclamation,
            'eq'=>$this->task->eq
        ];
    }

    function serializeDiplay($sumTime)
    {
        return [
            'time' => $this->time,
            'work_time' => $this->sum_time,
            'name' => $this->user->name,
            'surname' => $this->user->surname,
            'order_number' => $this->task->order_number,
            'productID' => $this->task->productID,
            'type' => $this->task->type,
            'status' => $this->status_internal,
            'maxTime' => (int)env('BASIC_TIME'),
            'sumTime' => $sumTime,
            'toDoTime' => (int)env('BASIC_TIME') - (int)$sumTime,
            'avatar' => $this->user->avatar,
            'image' => $this->task->image_url,
            'eq'=>$this->task->eq
        ];
    }

    function serializeAccept()
    {
        return [
            'work_time' => $this->sum_time,
            'user' => $this->user->name . ' ' . $this->user->surname,
            'order_number' => $this->task->order_number,
            'type' => $this->task->type,
            'image' => $this->task->image_url,
            'date' => $this->task->date_order,
            'group' => $this->user->group->name,
            'id' => $this->id,
            'eq'=>$this->task->eq
        ];
    }

    function serializeTaskDetail()
    {
        return [
            'type' => $this->task->type,
            'user' => $this->user->name . ' ' . $this->user->surname,
            'image' => $this->task->image_url,
            'date' => $this->task->date_order,
            'group' => $this->user->group->name,
            'work_time' => $this->sum_time,
            'id' => $this->id,
            'status' => $this->status_internal,
            'order_number' => $this->task->order_number,
            'client' => $this->task->client,
            'avatar' => $this->user->avatar,
            'eq'=>$this->task->eq
        ];
    }

    /**
     * ===================ACCESSORS===============
     */

    public
    function getSumTimeAttribute()
    {

        if ($this->status_internal == 2 || $this->status_internal == 5)
            return $this->work_time + strtotime(date('Y-m-d H:i:s')) - strtotime($this->date_start);
        else
            return $this->work_time;
    }

    public
    function getRunningAttribute()
    {
        return $this->status_internal == 2 || $this->status_internal == 5;
    }

    public
    function getTimeAttribute()
    {
        if ($this->user->group_id == 2)
            return $this->task->graphic_time;
        if ($this->user->group_id == 3)
            return $this->task->graver_time;
    }


    public
    function getCheckTimeAttribute()
    {
        if ($this->user->group_id == 3) {
            return $this->task->graphic_time;
        } else {
            return 0;
        }
    }


}
