<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use DB;

class IntegratorController extends Controller
{

    function translateData($name_from_sql)
    {

        $table_tanslations = [
            'Nagid' => 'order_number',
            'Data' => 'date_add',
            'DataSprz' => 'date_order',
            'Logo' => 'client',
            'LogoH' => 'employee',

            'Priorytet' => 'prio',
            'Status' => 'status',
            'Opis' => 'name',
            'Poziom' => 'min_level',

            'GotowyProjekt' => 'done',
            'GrafikaCzasPierwotny' => 'graphic_time',
            'GrawerniaCzas' => 'graver_time',


        ];
        return $table_tanslations[$name_from_sql];
    }

    function run_cron()
    {
        echo 'run';
        $users = DB::connection('sqlsrv')->select('*')->from('dbo.w_fnGetOrders4Isoft');


    }
}
