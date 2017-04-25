<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = array();  // Important
    public $timestamps = false;

    public function user_task()
    {
        return $this->hasMany('App\UserTask');
    }

    public function exportToReport()
    {

        $ret = [
            'Nagid' => $this->order_number,
            'Linid' => $this->line_id,
            'Data' => $this->date_add,
            'DataSprz' => $this->date_order,
            'Logo' => $this->client,
            'LogoH' => $this->employee,
            'Priorytet' => $this->prio,
            'Status' => $this->status,
            'SymKar' => $this->productID,
            'Ilosc' => $this->eq,
            'StTrudnosci' => $this->min_lvl,
            'Rd' => $this->type,
        ];

        $times = ['GrawerniaCzas' => 0, 'GrafikaCzas' => 0];

        $user_tasks = $this->user_task;
        if (!empty($user_tasks)) {
            foreach ($user_tasks as $task) {
                if ($task->section == 'grafika')
                    $times['GrafikaCzas'] += $task->work_time;
                else if ($task->section == 'grawernia')
                    $times['GrawerniaCzas'] += $task->work_time;
            }

            if ($times['GrafikaCzas'] == 0)
                $times['GrafikaCzas'] = $this->graphic_time;
            if ($times['GrawerniaCzas'] == 0)
                $times['GrawerniaCzas'] = $this->graver_time;
        }

        $times['GrafikaCzas'] /= 60;
        $times['GrawerniaCzas'] /= 60;

        $times['GrafikaCzas'] = (round($times['GrafikaCzas']));
        $times['GrawerniaCzas'] = (round($times['GrawerniaCzas']));

        $ret = array_merge($ret, $times);
        return $ret;
    }

}
