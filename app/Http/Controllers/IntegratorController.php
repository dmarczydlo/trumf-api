<?php

namespace App\Http\Controllers;

use App\Task;
use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use File;
use Hash;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use App\TMP;
use PDO;

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
            'SymKar' => 'productID',
            'Priorytet' => 'prio',
            'Status' => 'status',
            'Opis' => 'name',
            'StTrudnosci' => 'min_lvl',
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


        $handle = fopen($_SERVER['DOCUMENT_ROOT'] . $path_img . date('ymdhis') . '.jpg', "a+");
        if (fwrite($handle, $image_string) === FALSE) {
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

    function checkAndGenerateHASH($data)
    {
        $new_hash = md5(json_encode($data));
        $last_hash = DB::table('hash')->orderBy('id', 'desc')->first();

        if (empty($last_hash)) {
            $whatIsNew = $this->jsonCreateAndMerge($data);
            dd($whatIsNew);
        } else if ($last_hash->hash != $new_hash) {
            $whatIsNew = $this->jsonCreateAndMerge($data);
            dd($whatIsNew);
            //save new hash
            Hash::create($new_hash);
        }
    }

    public static function arrayDiff($new, $old)
    {
        return array_map('json_decode', array_diff(array_map('json_encode', $new), array_map('json_encode', $old)));

    }

    function jsonCreateAndMerge($data)
    {
        $json_new = json_encode($data);
        $last_json_path = './db/last_db.json';

        if (!File::exists($last_json_path)) {
            //create json
            file_put_contents($last_json_path, $json_new);
            // all is new
            return $data;
        }
        $last_json = file_get_contents($last_json_path);
        $diff = $this->arrayDiff(json_decode($json_new, true), json_decode($last_json, true));
        //save new
        file_put_contents($last_json_path, $json_new);
        return $diff;
    }

    function newDataChecker()
    {

//        $tasks = DB::connection('sqlsrv')
//            ->table('dbo.w_fnGetOrders4Isoft()')
//            ->select("Nagid", "LinId", "Data", "Rd", "DataSprz", "Logo", "LogoH", "Priorytet", "Status", "GotowyProjekt", "GrafikaCzasPierwotny", "GrafikaCzasWtorny", "GrawerniaCzas", "SymKar")
//            ->where('Nagid', '>=', env('TASK_START_ID', 1))
//            //->where('Status', '>=', 2)
//            ->get();

        $tasks = DB::table('tmp_tryumf')->get();

        if (empty($tasks)) {
            echo 'empty data';
            exit();
        }
        $this->checkAndGenerateHASH($tasks);
    }

    function run_cron()
    {
        echo 'run';

        //get max last softlab_id
        $softlab_max_id = DB::table('tasks')->max('order_number');
        if (!$softlab_max_id > 0) $softlab_max_id = 1;

        //status >=2 task is authorized
        $tasks = DB::connection('sqlsrv')
            ->table('dbo.w_fnGetOrders4Isoft()')
            ->select("Nagid", "LinId", "Data", "Rd", "DataSprz", "Logo", "LogoH", "Priorytet", "Status", "GotowyProjekt", "GrafikaCzasPierwotny", "GrafikaCzasWtorny", "GrawerniaCzas", "SymKar")
            ->where('Nagid', '>=', env('TASK_START_ID', 1))
            ->where('Nagid', '>=', $softlab_max_id)
            ->where('Status', '>=', 2)
            ->get();

        $count_added = 0;
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
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

    /**
     * TEST FOR COPY ALL TABLE
     */
    function test_image()
    {
        echo 'start';
        $tasks = DB::connection('sqlsrv')
            ->table('dbo.w_fnGetOrders4Isoft()')
            ->get();

        foreach ($tasks as $task) {
            TMP::create((array)$task);
        }
        echo 'done';
        exit();
    }

}
