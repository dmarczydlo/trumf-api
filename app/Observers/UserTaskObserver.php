<?php

/**
 * Created by PhpStorm.
 * User: marczak
 * Date: 2017-04-08
 * Time: 19:26
 */
namespace App\Observers;

use App\UserTask;
use Log;
use App\Hash;

class UserTaskObserver
{

    public function creating($userTask)
    {

        $orderMax = UserTask::where('user_id', $userTask->user_id)
            ->where('schedule_day', $userTask->schedule_day)
            ->where('status_internal', '<', 20)
            ->max('order_num');
        if ($orderMax > 0) {
            $orderMax++;
        } else $orderMax = 1;


        //block group
        if ($userTask->user->group_id == 2) {
            $userTask->graphic_block = 1;
        } else if ($userTask->user->group_id == 3) {
            $userTask->graver_block = 1;
        }

        //added section
        $userTask->section = $userTask->user->group->name;

        $userTask->order_num = $orderMax;
        return $userTask;
    }

    public function saving($userTask)
    {

        //remove this task
        if ($userTask->status_internal === 20) {
            $tasks = UserTask::where('user_id', $userTask->user_id)
                ->where('schedule_day', $userTask->schedule_day)
                ->where('order_num', '>', $userTask->order_num)
                ->get();

            $dispatcher = UserTask::getEventDispatcher();
            UserTask::unsetEventDispatcher();
            foreach ($tasks as $task) {

                $task->order_num--;
                $task->save();
            }
            UserTask::setEventDispatcher($dispatcher);

            $userTask->order_num = 0;
            return $userTask;
        }

        if ($userTask->accept == 1) {
            //accept
        }

    }

}