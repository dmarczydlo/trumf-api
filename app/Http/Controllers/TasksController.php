<?php

namespace App\Http\Controllers;

use App\TaskTime;
use App\UserTask;
use Illuminate\Http\Request;
use App\Task;
use App\User;

use App\Http\Requests;

class TasksController extends Controller
{

    //task status 1 - new
    //task status 2 - in progress
    //task status 3 - done
    //task status 4 - accepted
    //task status 5 - reclamation


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

        //TODO add logic if employee realize task to fast to scrool task - dodać nowe taski z dnia nastepnego
        $tasks = User::find($user_id)->tasks()
            ->where('schedule_day', $day)
            ->orderBy('order_int', 'ASC')
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
        $data = $request->only('user_id', 'task_id', 'schedule_day', 'order_num');


        $task_do = User::find($data['user_id'])->tasks()
            ->where('task_id', $data['task_id'])
            ->where('schedule_day', $data['schedule_day'])
            ->where('user_task.status', 1)
            ->count();


        if ($task_do <= 0) {
            $user = User::where('id', $data['user_id'])->first();
            if (!empty($user)) {

                //check that 6h limit not block

                $task = Task::find($data['task_id']);

                $limit = DB::table('task_time')
                    ->join('user_task', 'user_task.id', '=', 'task_time.user_task_id')
                    ->select('SUM(time)')
                    ->where('schedule_day', $data['schedule_day'])
                    ->where('user_id', $data['user_id'])
                    ->groupBy('user_task.task_id')
                    ->get();

                if ($limit + $task->time <= App::environment('BASIC_TIME') + App::environment('EXTRA_TIME')) {


                    $section = $user->group->name;
                    $user->tasks()->attach($data['task_id'], ['status' => 1, 'schedule_day' => $data['schedule_day'], 'section' => $section, 'order_num' => $data['order_num']]);

                    return response()->json([
                        'success' => true
                    ]);
                } else {
                    return response()->json(['error' => 'Ten pracownik nie ma wystarczająco czasu w tym dniu na to zadanie'], 401);
                }
            } else {
                return response()->json(['error' => 'Brak pracownika w bazie'], 401);
            }
        } else {
            return response()->json(['error' => 'To zadanie zostało już przydzielone do tego pracownika'], 401);
        }

    }

    public
    function startTask(Request $request)
    {
        $data = $request->only('date_start', 'task_id', 'section', 'user_task_id');
        $validator = Validator::make($data, [
            'date_start' => 'required|datetime',
            'task_id' => 'required|numeric',
            'user_task_id' => 'required|numeric',
            'section' => 'required'
        ]);

        if (!$validator->fails()) {

            $now = date('Y-m-d');
            $limit = DB::table('task_time')
                ->join('user_task', 'user_task.id', '=', 'task_time.user_task_id')
                ->select('SUM(time)')
                ->where('schedule_day', $now)
                ->where('user_id', $data['user_id'])
                ->groupBy('user_task.task_id')
                ->get();

            $task = Task::find($data['task_id']);

            if ($limit + $task->time <= App::environment('BASIC_TIME') + App::environment('EXTRA_TIME')) {


                $data = $request->only('date_start', 'task_id', 'section');
                $data['status'] = 2;
                $task_time = TaskTime::create($data);


                //update global task status
                $task = Task::find($data['task_id']);
                $task->status = 2;
                $task->save();
                return response()->json([
                    'time' => $task_time
                ]);
            } else {
                return response()->json(['error' => 'Przekroczyłeś swój dzienny limit. Nie można uruchomić tego zadania'], 401);

            }

        } else {
            return response()->json(['error' => 'Brak wymaganych danych'], 401);
        }
    }

    public
    function stopTask(Request $request)
    {

        $data = $request->only('id');
        $validator = Validator::make($data, [
            'id' => 'required|datetime',
        ]);

        if (!$validator->fails()) {

            $data_stop = date('Y-m-d H:i:s');
            $task = TaskTime::find($data['id']);
            $data_task = $data;
            $data_task['time'] = strtotime($data_stop) - strtotime($task->date_start);

            $task_time = User::updateOrCreate(['task_id' => $data['task_id'], 'user_task_id' => $data['user_task_id'], 'section' => $data['section'], 'date_start' => $data['date_start']], $data_task);
            $task_time->save();

            return response()->json([
                'time' => $task_time
            ]);
        } else {
            return response()->json(['error' => 'Brak wymaganych danych'], 401);
        }
    }

    public
    function acceptTask(Request $request)
    {
        //id - task_time_id
        $data = $request->only('id');
        $validator = Validator::make($data, [
            'id' => 'required|datetime',
        ]);

        if (!$validator->fails()) {
            $task_time = TaskTime::find($data['id']);
            $user_task = UserTask::find($task_time->user_task_id);

            $user_task->accept = 1;
            $user_task->status = 4;
            $user_task->save();

            $task = Task::find($user_task->task_id);
            $task->status = 4;
            $task->save();

            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json(['error' => 'Brak wymaganych danych'], 401);
        }
    }

    public
    function readAllTasks()
    {
        $tasks = Task::all();

        return response()->json([
            'tasks' => $tasks
        ]);
    }


}
