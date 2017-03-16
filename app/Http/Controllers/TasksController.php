<?php

namespace App\Http\Controllers;

use App\TaskTime;
use App\UserTask;
use Illuminate\Http\Request;
use App\Task;
use App\User;
use Validator;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App;

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
                    ->select(DB::raw('SUM(time) as time'))
                    ->where('schedule_day', $data['schedule_day'])
                    ->where('user_id', $data['user_id'])
                    ->groupBy('user_task.task_id')
                    ->get();

                if ($limit['time'] + $task->time <= App::environment('BASIC_TIME') + App::environment('EXTRA_TIME')) {


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
        $data = $request->only('task_id', 'section', 'user_task_id');

        $validator = Validator::make($data, [
            'task_id' => 'required|numeric',
            'user_task_id' => 'required|numeric',
            'section' => 'required'
        ]);

        if (!$validator->fails()) {


            $data['date_start'] = date('Y-m-d H:i:s');
            $user_task = DB::table('user_task')
                ->select('user_id')
                ->where('id', $data['user_task_id'])
                ->first();

            $now = date('Y-m-d');
            $limit = DB::table('task_time')
                ->join('user_task', 'user_task.id', '=', 'task_time.user_task_id')
                ->select(DB::raw('SUM(time) as time'))
                ->where('schedule_day', $now)
                ->where('user_id', $user_task->user_id)
                ->groupBy('user_task.task_id')
                ->first();


            $task = Task::find($data['task_id']);
            if ($task) {


                if (($limit['time'] + $task->time) <= App::environment('BASIC_TIME') + App::environment('EXTRA_TIME')) {

                    $task_time = TaskTime::create($data);

//                    $task_time->save();

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
                return response()->json(['error' => 'Zadanie nie istnieje'], 401);

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
            'id' => 'required|numeric',
        ]);

        if (!$validator->fails()) {

            $data_stop = date('Y-m-d H:i:s');
            $task = TaskTime::find($data['id']);
            if ($task) {
                $data_task = $data;
                $data_task['date_stop'] = $data_stop;
                $data_task['time'] = strtotime($data_stop) - strtotime($task->date_start);

                $id = $data['id'];
                $task_time = TaskTime::updateOrCreate(['id' => $id], $data_task);
                $task_time->save();

                return response()->json([
                    'time' => $task_time
                ]);
            } else {
                return response()->json(['error' => 'Zadanie nie istnieje'], 401);
            }
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
            'id' => 'required|numeric',
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
