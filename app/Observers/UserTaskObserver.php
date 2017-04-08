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
            ->max('order_num');
        if ($orderMax > 0) {
            $orderMax++;
        } else $orderMax = 1;

        $userTask->order_num = $orderMax;
        return $userTask;
    }

    public function saving($userTask)
    {

        //remove this task
        if ($userTask->user_id === 0) {
            $tasks = UserTask::where('user_id', $userTask->user_id)
                ->where('schedule_day', $userTask->schedule_day)
                ->where('order_num', '>', $userTask->order_num)
                ->get();


            foreach ($tasks as $task) {

//                $task->order_num--;
//                $task->save();
            }


        }

        if ($userTask->accept == 1) {
            //accept
        }

    }

}