<?php

namespace App\Http\Controllers;

use App\Task;
use Illuminate\Http\Request;

use App\Http\Requests;
use DB;
use Illuminate\Support\Facades\Storage;

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
            'Rd' => 'type',

            'Priorytet' => 'prio',
            'Status' => 'status',
            'Opis' => 'name',
            'Poziom' => 'min_level',

            'GotowyProjekt' => 'done',
            'GrafikaCzasPierwotny' => 'graphic_time',
            'GrawerniaCzas' => 'graver_time',
            'LinId' => 'tryumf_tmp_id'


        ];

        return $table_tanslations[$name_from_sql];
    }

    function saveImage($image_string, $path_img, $id = null)
    {

       // $image_string = $image_string;



        $handle = fopen($_SERVER['DOCUMENT_ROOT'] . $path_img . date('ymdhis') . '.jpg',"a+");
        if(fwrite($handle,$image_string)===FALSE){
        }

        echo 'done';
        exit();


//        $data = base64_decode($image_string); // decode an image
        $im = imagecreatefromstring($image_string); // php function to create image from string
        if ($im !== false) {
            imagejpeg($im, $_SERVER['DOCUMENT_ROOT'] . $path_img . date('ymdhis') . '.jpg');
            imagedestroy($im);
        } else
            echo 'An error occurred.';
    }


    function run_cron()
    {
        echo 'run';

        $tasks = DB::connection('sqlsrv')->table('dbo.w_fnGetOrders4Isoft()')->limit(1)->get();
//        echo "<pre>";
//        print_r($tasks);

        $this->saveImage($tasks[0]->SloImage, '/images/', 0);
        exit();


        //get max last softlab_id
        $softlab_max_id = DB::table('tasks')->max('order_number');


        $tasks = DB::connection('sqlsrv')
            ->table('dbo.w_fnGetOrders4Isoft()')
            ->select("Nagid", "LinId", "Data", "Rd", "DataSprz", "Logo", "LogoH", "Priorytet", "Status", "GotowyProjekt", "GrafikaCzasPierwotny", "GrafikaCzasWtorny", "GrawerniaCzas")
            ->where('Nagid', '>=', env('TASK_START_ID', 1))
            ->where('Nagid', '>=', $softlab_max_id)
            ->get();

        $count_added = 0;
        if (!empty($tasks)) {
//            echo "<pre>";
//            print_r($tasks); exit();
            foreach ($tasks as $task) {

//                print_R($task); exit();
                $count = DB::table('tasks')->where('tryumf_tmp_id', $task->LinId)->where('order_number', $task->Nagid)->count();
                if ($count == 0) {
                    //add new task
                    $data_my = [];
                    foreach ($task as $k => $v) {
                        if ($k == 'GrafikaCzasPierwotny') {
                            if ($v > 0) {
                                $data_my[$this->translateData($k)] = $v * 60;
                            } else {
                                if ($task->GrafikaCzasWtorny > 0) {
                                    $data_my[$this->translateData($k)] = $task->GrafikaCzasWtorny * 60;
                                } else {
                                    $data_my[$this->translateData($k)] = 0;
                                }
                            }
                        } else if ($k == 'GrawerniaCzas') {
                            if ($task->GrawerniaCzas === '' || $task->GrawerniaCzas <= 0) {
                                $data_my[$this->translateData($k)] = 0;
                            } else {
                                $data_my[$this->translateData($k)] = $v * 60;
                            }
                        } else if ($k !== 'GrafikaCzasWtorny') {
                            $data_my[$this->translateData($k)] = $v;
                        }


                    }

                    if (!empty($data_my)) {
                        $count_added = count($data_my);
                        Task::create($data_my);
                    } else {
                        $count_added = 0;
                    }

                }

            }
        }
        echo ' DONE Added ' . $count_added;

    }
}
