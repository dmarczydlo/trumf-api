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
    //task status 20 - removed

    /**
     * @param $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function read($user_id)
    {

        $task = Task::find($user_id);

        return response()->json([
            'task' => $task
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAllNewTask()
    {

        $select_graphic = ['tasks.id as task_id', 'order_number', 'user_task.status_internal as status', 'prio', 'client', 'graphic_time as time', 'image_url', 'type', 'productID', 'min_lvl'];
        $select_graver = ['tasks.id as task_id', 'order_number', 'user_task.status_internal as status', 'prio', 'client', 'graver_time as time', 'image_url', 'type', 'productID', 'min_lvl'];


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

        $tasks = UserTask::with('task')->where('user_id', $user_id)
            ->where('schedule_day', $day)
            ->where('accept', 0)
            ->where('status_internal','<',20)
            ->orderBy('order_num')
            ->get();

        $tasks = $tasks->map(function ($item) {
            return $item->serializeOneRow();
        });

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

        //check if exist
        $data = $request->only('user_id', 'task_id', 'schedule_day');


        $validator = Validator::make($data, [
            'task_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'schedule_day' => 'required|date'
        ]);

        if ($validator->fails())
            return response()->json(['error' => 'Brak wymaganych danych'], 402);

        $user = User::find($data['user_id']);

        if (empty($user))
            return response()->json(['error' => 'Brak pracownika w bazie'], 402);


        if ($user->group->name == env('GRAVER_NAME', 'grawernia')) {
            $task_block = UserTask::where('task_id', $data['task_id'])
                ->where('graver_block', 1)
                ->count();
        } else if ($user->group->name == env('GRAPHIC_NAME', 'grafika')) {
            $task_block = UserTask::where('task_id', $data['task_id'])
                ->where('graphic_block', 1)
                ->count();
        }

        if ($task_block > 0)
            return response()->json(['error' => 'To zadanie zostało już przydzielone do pracownika'], 402);

        $task = Task::find($data['task_id']);

        //check that user can do this
        if ($task->min_lvl > $user->level)
            return response()->json(['error' => 'Wybrany pracownik ma zbyt niskie kompetencje'], 402);

        $limit = UserTask::where('schedule_day', $data['schedule_day'])
            ->where('user_id', $data['user_id'])->get()
            ->sum('time');


        $limit = $limit > 0 ? $limit : 0;
        if ($limit + $task->time > env('BASIC_TIME') + env('EXTRA_TIME'))
            return response()->json(['error' => 'Ten pracownik nie ma wystarczająco czasu w tym dniu na to zadanie'], 402);

        UserTask::create(
            [
                'accept' => 0,
                'task_id' => $data['task_id'],
                'user_id' => $data['user_id'],
                'status_internal' => 1,
                'schedule_day' => $data['schedule_day']
            ]
        );


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
        $data = $request->only('user_task_id');

        $validator = Validator::make($data, [
            'user_task_id' => 'required|numeric'
        ]);

        if ($validator->fails())
            return response()->json(['error' => 'Brak wymaganych danych'], 402);

        $user_task = UserTask::find($data['user_task_id']);

        //close unclosed task
        $unclose = UserTask::where('user_id', $user_task->user_id)
            ->where('schedule_day', date('Y-m-d'))
            ->whereIn('status_internal', [2, 5])->get();
        if ($unclose)
            foreach ($unclose as $one) {
                $one->stopTask();
            }

        //check that user can work
        $sumWorkTime = UserTask::where('user_id', $user_task->user_id)
            ->where('schedule_day', date('Y-m-d'))
            ->get()
            ->sum('work_time');


        $limit_value = $sumWorkTime ? $sumWorkTime : 0;
        if (($limit_value + $user_task->time) > env('BASIC_TIME') + env('EXTRA_TIME'))
            return response()->json(['error' => 'Przekroczyłeś swój dzienny limit. Nie można uruchomić tego zadania'], 402);

        $user_task->setStartStatus();
        $user_task->date_start = date('Y-m-d H:i:s');
        $user_task->save();

        return response()->json([
            'success' => true
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

        $user_task = UserTask::find($data['id']);
        $user_task->setNewWorkTime();
        $user_task->setStopStatus();
        $user_task->save();

        return response()->json(['time' => $user_task->work_time]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public
    function acceptTask(Request $request)
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
        $user_task->setAcceptStatus();
        $user_task->save();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function removeTask(Request $request)
    {
        $data = $request->only('user_task_id');
        $validator = Validator::make($data, [
            'user_task_id' => 'required|numeric'
        ]);

        if ($validator->fails())
            return response()->json(['error' => 'Brak wymaganych danych'], 402);

        $user_task = UserTask::find($data['user_task_id']);
        if ($user_task->status_internal == 2 || $user_task->status_internal == 5)
            return response()->json(['error' => 'To zadanie trwa. Musi zostać zakończone'], 402);


        $user_task->status_internal = 20;
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

    /** Move task - change order_num
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function moveTask(Request $request)
    {
        $data = $request->only('user_task_id', 'order_num');

        $validator = Validator::make($data, [
            'user_task_id' => 'required|numeric'
        ]);

        //TODO write this method

        if ($validator->fails())
            return response()->json(['error' => 'Brak wymaganych danych'], 402);
    }

    function getEmployeeTasksStatus($group_id)
    {

        if (!$group_id > 0)
            return response()->json(['error' => 'Brak wymaganych danych'], 402);

        $nowWorking = UserTask::where('schedule_day', date('Y-m-d'))
            ->orderBy('date_start', 'desc')
            ->get()
            ->unique('user_id');

        $tasks = $nowWorking->map(function ($item) {

            $sumTime = UserTask::where('user_id', $item->user_id)
                ->where('schedule_day', $item->schedule_day)
                ->get()
                ->sum('sum_time');

            return $item->serializeDiplay($sumTime);
        });

        return response()->json(
            ['data' => $tasks]
        );
    }
}
