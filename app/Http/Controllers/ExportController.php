<?php

namespace App\Http\Controllers;

use App\Task;
use Illuminate\Http\Request;

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
    }
}
