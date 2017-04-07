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

        $select_graphic = ['tasks.id as task_id', 'order_number', 'user_task.status_internal as status', 'prio', 'client', 'graphic_time as time', 'image_url', 'type', 'productID', 'min_lvl'];
        $select_graver = ['tasks.id as task_id', 'order_number', 'user_task.status_internal as status', 'prio', 'client', 'graver_time as time', 'image_url', 'type', 'productID', 'min_lvl'];

        //tasks for graphic

//        $subQuery =


        $graphic_tasks = DB::table('tasks')
            ->leftJoin(DB::raw('( SELECT MAX(graphic_block) as graphic_block, status_internal, updated_at,  task_id  FROM `user_task` group by task_id  ORDER BY  updated_at DESC ) AS user_task'), function ($join) {
                $join->on('tasks.id', '=', 'user_task.task_id');
            })
            ->select($select_graphic)
            ->where('graphic_time', '>', 0)
            ->where(function ($query) {
                $query->where('user_task.graphic_block', '=', 0)->orWhere('user_task.graphic_block', '=', NULL);
            })
            ->where('done', 0)
            ->where(function ($query) {
                $query->where('user_task.status_internal', '<>', 4)->orWhere('user_task.status_internal', '=', NULL);
            })
            ->groupBy('tasks.id')
            ->orderBy('user_task.graphic_block', 'desc')
            ->orderBy('prio', 'desc')
            ->get();


        //tasks for graver
        $graver_tasks = DB::table('tasks')
            ->leftJoin(DB::raw('( SELECT MAX(graver_block) as graver_block, status_internal, updated_at,  task_id  FROM `user_task` group by task_id  ORDER BY  updated_at DESC ) AS user_task'), function ($join) {
                $join->on('tasks.id', '=', 'user_task.task_id');
            })
            ->select($select_graver)
            ->where('graver_time', '>', 0)
            ->where(function ($query) {
                $query->where('user_task.graver_block', '=', 0)->orWhere('user_task.graver_block', '=', NULL);
            })
            ->where('done', 0)
            ->where(function ($query) {
                $query->where('user_task.status_internal', '<>', 7)->orWhere('user_task.status_internal', '=', NULL);
            })
            ->groupBy('tasks.id')
            ->orderBy('prio', 'desc')
            ->get();


        $return = ['graphic' => $graphic_tasks, 'graver' => $graver_tasks];
        return response()->json([
            'tasks' => $return
        ]);

    }

    /**
     * @param $user_id
     * @param $day
     * @return \Illuminate\Http\JsonResponse
     */
    public function readTasksForUserAtDay($user_id, $day)
    {

        //TODO add logic if employee realize task to fast to scrool task - dodać nowe taski z dnia nastepnego

        $user = User::find($user_id);
        if ($user->group->name == env('GRAPHIC_NAME')) {
            $select = ['min_lvl', 'user_id', 'order_number', 'user_task.status_internal as status', 'prio', 'client', 'done', 'graphic_time as time', 'image_url', 'type', 'productID'];
        } else if ($user->group->name == env('GRAVER_NAME')) {
            $select = ['min_lvl', 'user_id', 'order_number', 'user_task.status_internal as status', 'prio', 'client', 'done', 'graver_time as time', 'image_url', 'type', 'productID'];
        }

        $user_task = [DB::raw('user_task.id AS user_task_id'), 'user_task.task_id', 'user_task.status_internal', 'user_task.schedule_day', 'user_task.accept', 'user_task.section', 'user_task.order_num', DB::raw('SUM(task_time.time) as sum_time')];
        $select = array_merge($select, $user_task);


        $tasks = DB::table('tasks')
            ->select($select)
            ->join('user_task', 'tasks.id', '=', 'user_task.task_id')
            ->leftJoin('task_time', 'tasks.id', '=', 'task_time.task_id')
            ->where('user_id', $user_id)
            ->where('user_task.schedule_day', $day)
            ->where('user_task.accept', 0)
            ->groupBy('user_task.task_id')
            ->get();


        return response()->json([
            'tasks' => $tasks
        ]);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setTaskToUser(Request $request)
    {

        //group_id  1 - admin
        //group_id 2 - graphic
        //group_id 3 - graver
        //TODO Validate

        //check if exist
        $data = $request->only('user_id', 'task_id', 'schedule_day');
        $user = User::find($data['user_id']);
        if ($user->group->name == env('GRAVER_NAME', 'grawernia')) {
            $task_block = UserTask::where('task_id', $data['task_id'])->where('graver_block', 1)->count();
            $data_block = 'graver_block';
            $data_select = 'graver_time';

        } else if ($user->group->name == env('GRAPHIC_NAME', 'grafika')) {
            $task_block = UserTask::where('task_id', $data['task_id'])->where('graphic_block', 1)->count();
            $data_block = 'graphic_block';
            $data_select = 'graphic_time';
        }

        if ($task_block > 0)
            return response()->json(['error' => 'To zadanie zostało już przydzielone do pracownika'], 402);

        $user = User::where('id', $data['user_id'])->first();

        if (empty($user))
            return response()->json(['error' => 'Brak pracownika w bazie'], 402);

        //check that 6h limit not block
        $task = Task::find($data['task_id']);
        //TODO Check 6h time for day NEXT
        //check that user can do this
        if ($task->min_lvl > $user->level)
            return response()->json(['error' => 'Wybrany pracownik ma zbyt niskie kompetencje'], 402);

        $limit = DB::table('user_task')
            ->leftJoin((DB::raw("(select SUM(time) as time, user_task_id from task_time group by user_task_id) as task_time")), 'user_task.id', '=', 'task_time.user_task_id')
            ->join('tasks', 'user_task.task_id', '=', 'tasks.id')
            ->select(DB::raw("SUM(CASE WHEN task_time.time >0 THEN task_time.time ELSE $data_select END ) as time"))
            ->where('schedule_day', $data['schedule_day'])
            ->where('user_id', $data['user_id'])
            ->first();

        $limit_value = isset($limit->time) ? $limit->time : 0;
        if ($limit_value + $task->time > env('BASIC_TIME') +env('EXTRA_TIME'))
            return response()->json(['error' => 'Ten pracownik nie ma wystarczająco czasu w tym dniu na to zadanie'], 402);

        $section = $user->group->name;
        //TODO add observe
        $user->tasks()->attach($data['task_id'],
            [
                $data_block => 1,
                'accept' => 0,
                'status_internal' => 1,
                'schedule_day' => $data['schedule_day'],
                'section' => $section,
                'order_num' => 10
            ]);
        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startTask(Request $request)
    {
        $data = $request->only('task_id', 'section', 'user_task_id');

        $validator = Validator::make($data, [
            'task_id' => 'required|numeric',
            'user_task_id' => 'required|numeric',
            'section' => 'required'
        ]);

        if ($validator->fails())
            return response()->json(['error' => 'Brak wymaganych danych'], 402);

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
        if (!$task)
            return response()->json(['error' => 'Zadanie nie istnieje'], 402);


        $limit_value = isset($limit->limit) ? $limit->limit : 0;
        if (($limit_value + $task->time) > env('BASIC_TIME') + env('EXTRA_TIME'))
            return response()->json(['error' => 'Przekroczyłeś swój dzienny limit. Nie można uruchomić tego zadania'], 402);


        $task_time = TaskTime::create($data);
        $userTask = UserTask::find($data['user_task_id']);

        if ($data['section'] === env('GRAPHIC_NAME', 'grafika'))
            $userTask->status_internal = 2;
        else if ($data['section'] === env('GRAVER_NAME', 'grawernia'))
            $userTask->status_internal = 5;

        $userTask->save();
        return response()->json([
            'time_id' => $task_time->id,
            'task_id' => $task_time->task_id
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopTask(Request $request)
    {

        $data = $request->only('id');
        $validator = Validator::make($data, [
            'id' => 'required|numeric',
        ]);

        if ($validator->fails())
            return response()->json(['error' => 'Brak wymaganych danych'], 402);

        $data_stop = date('Y-m-d H:i:s');
        $task = TaskTime::find($data['id']);

        if (!$task)
            return response()->json(['error' => 'Zadanie nie istnieje'], 402);

        $data_task = $data;
        $data_task['date_stop'] = $data_stop;
        $data_task['time'] = strtotime($data_stop) - strtotime($task->date_start);

        $id = $data['id'];
        $task_time = TaskTime::updateOrCreate(['id' => $id], $data_task);
        $task_time->save();

        $userTask = UserTask::find($task->user_task_id);

        if ($userTask->section === env('GRAPHIC_NAME', 'grafika'))
            $userTask->status_internal = 3;
        else if ($userTask->section === env('GRAVER_NAME', 'grawernia'))
            $userTask->status_internal = 6;

        $userTask->save();

        return response()->json([
            'time' => $task_time->time
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptTask(Request $request)
    {
        //id - task_time_id
        $data = $request->only('id');
        $validator = Validator::make($data, [
            'id' => 'required|numeric',
        ]);

        if ($validator->fails())
            return response()->json(['error' => 'Brak wymaganych danych'], 402);

        $user_task = UserTask::find($data['id']);
        $user_task->accept = 1;

        if ($user_task->section === env('GRAPHIC_NAME', 'grafika')) {
            $user_task->status_internal = 4;
            $user_task->graphic_block = 0;
        } else if ($user_task->section === env('GRAVER_NAME', 'grawernia')) {
            $user_task->status_internal = 7;
            $user_task->graver_block = 0;
        }
        $user_task->save();


        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param $user_task_id
     * @return \Illuminate\Http\JsonResponse
     */
    function removeTask($user_task_id)
    {
        $user_task = UserTask::find($user_task_id);
        if ($user_task->status_internal == 2 || $user_task->status_internal == 5)
            return response()->json(['error' => 'To zadanie trwa. Musi zostać zakończone'], 402);

        if ($user_task->section === env('GRAPHIC_NAME', 'grafika'))
            $user_task->status_internal = 4;
        else if ($user_task->section === env('GRAVER_NAME', 'grawernia'))
            $user_task->graver_block = 0;

        $user_task->user_id = -1;
        $user_task->save();
        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    function readAllTasks()
    {
        $tasks = Task::all();

        return response()->json([
            'tasks' => $tasks
        ]);
    }

    function moveTask($user_task_id, $order_num)
    {

    }


}
