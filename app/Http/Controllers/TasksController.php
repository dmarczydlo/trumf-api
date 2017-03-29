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
    //task status 2 - in progress - graphic
    //task status 3 - done - graphic
    //task status 4 - accepted - graphic
    //task status 5 - in progress - graver
    //task status 6 - done - graver
    //task status 7 - accepted - graver
    //task status 10 - reclamation


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

        $user = User::find($user_id);
        if ($user->group_id == 2) {
            $select = ['order_number', 'status', 'prio', 'client', 'done', 'graphic_time as time', 'image_url', 'type'];
        } else if ($user->group_id == 3) {
            $select = ['order_number', 'status', 'prio', 'client', 'done', 'graver_time as time', 'image_url', 'type'];
        }
//
        $user_task = [DB::raw('user_task.id AS user_task_id'), 'user_task.task_id', 'user_task.status_internal', 'user_task.schedule_day', 'user_task.accept', 'user_task.section', 'user_task.order_num', DB::raw('SUM(task_time.time) as sum_time')];

        $select = array_merge($select, $user_task);


        $tasks = DB::table('tasks')
            ->select($select)
            ->join('user_task', 'tasks.id', '=', 'user_task.task_id')
            ->leftJoin('task_time', 'tasks.id', '=', 'task_time.task_id')
            ->where('user_id', $user_id)
            ->where('user_task.schedule_day', $day)
            ->where('user_task.accept',0)
            ->groupBy('user_task.task_id')
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

                //TODO Check 6h time for day NEXT

                //check that user can do this
                if ($task->min_lvl <= $user->level) {

                    $limit = DB::table('task_time')
                        ->join('user_task', 'user_task.id', '=', 'task_time.user_task_id')
                        ->select(DB::raw('SUM(time) as time'))
                        ->where('schedule_day', $data['schedule_day'])
                        ->where('user_id', $data['user_id'])
                        ->groupBy('user_task.task_id')
                        ->get();

                    $limit_value = isset($limit->limit) ? $limit->limit : 0;
                    if ($limit_value + $task->time <= App::environment('BASIC_TIME') + App::environment('EXTRA_TIME')) {


                        $section = $user->group->name;
                        $user->tasks()->attach($data['task_id'], ['accept'=>0,'status' => 1, 'schedule_day' => $data['schedule_day'], 'section' => $section, 'order_num' => $data['order_num']]);

                        return response()->json([
                            'success' => true
                        ]);
                    } else {
                        return response()->json(['error' => 'Ten pracownik nie ma wystarczająco czasu w tym dniu na to zadanie'], 401);
                    }
                } else {
                    return response()->json(['error' => 'Ten pracownik nie ma takich kompetencji'], 401);

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

                $limit_value = isset($limit->limit) ? $limit->limit : 0;
                if (($limit_value + $task->time) <= App::environment('BASIC_TIME') + App::environment('EXTRA_TIME')) {

                    $task_time = TaskTime::create($data);

//                    $task_time->save();

                    //update global task status


                    $userTask = UserTask::find($data['user_task_id']);


                    if ($data['section'] === env('GRAPHIC_NAME', 'grafika')) {
                        $userTask->status_internal = 2;
                    } else if ($data['section'] === env('GRAVER_NAME', 'grawernia')) {
                        $userTask->status_internal = 5;
                    }
                    $userTask->save();
                    return response()->json([
                        'time_id' => $task_time->id,
                        'task_id' => $task_time->task_id
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

                $userTask = UserTask::find($task->user_task_id);

                if ($userTask->section === env('GRAPHIC_NAME', 'grafika')) {
                    $userTask->status_internal = 3;
                } else if ($userTask->section === env('GRAVER_NAME', 'grawernia')) {
                    $userTask->status_internal = 6;
                }
                $userTask->save();

                return response()->json([
                    'time' => $task_time->time
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
            $user_task = UserTask::find($data['id']);
            $user_task->accept = 1;

            if ($user_task->section === env('GRAPHIC_NAME', 'grafika')) {
                $user_task->status_internal = 4;
            } else if ($user_task->section === env('GRAVER_NAME', 'grawernia')) {
                $user_task->status_internal = 7;
            }
            $user_task->save();


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
