<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Task;
use App\User;

use App\Http\Requests;

class TasksController extends Controller
{

    //task status 1 - new
    //task status 2 - in progress
    //task status 3 - done
    //task status 4 - reclamation


    public function read($user_id)
    {

        $task = Task::find($user_id);

        return response()->json([
            'task' => $task
        ]);
    }

    public function readAllNewTask()
    {

        $tasks = Task::where('status', '=', 1)->get();

        return response()->json([
            'tasks' => $tasks
        ]);

    }


    public function readTasksForUserAtDay($user_id, $day)
    {


        $tasks = User::find($user_id)->tasks()
            ->where('schedule_day', $day)
            ->get();

        return response()->json([
            'tasks' => $tasks
        ]);

    }

    public function setTaskToUser(Request $request)
    {

        //group_id  1 - admin
        //group_id 2 - graphic
        //group_id 3 - graver
        //TODO Validate

        //check if exist
        $data = $request->only('user_id', 'task_id', 'schedule_day');

        $task_do = User::find($data['user_id'])->tasks()->where('task_id', $data['task_id'])->where('schedule_day', $data['schedule_day'])->get();

        if (empty($task_do)) {
            $user = User::where('id', $data['user_id'])->first();
            $section = $user->group->name;
            $user->tasks()->attach($data['task_id'], ['status' => 1, 'schedule_day' => $data['schedule_day'], 'section' => $section]);

            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json(['error' => 'This task is added to this user'], 401);
        }

    }

    public function startTask()
    {

    }

    public function stopTask()
    {

    }

    public function acceptTask()
    {

    }

    public function readAllTasks()
    {
        $tasks = Task::all();

        return response()->json([
            'tasks' => $tasks
        ]);
    }

}
