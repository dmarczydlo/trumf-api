<?php

namespace App\Http\Controllers;

use App\Task;
use Illuminate\Http\Request;
use Excel;
use App\Http\Requests;

class ExportController extends Controller
{
    //
    public function exportData()
    {
        $tasks = Task::all();

        $tasks = $tasks->map(function ($item) {
            return $item->exportToReport();
        });

        //save to xlsx

        Excel::create('Raport', function ($excel) use ($tasks) {

            $excel->sheet('Raport', function ($sheet) use ($tasks) {

                $sheet->fromArray($tasks);

            });

        })->export('xlsx');


    }
}
