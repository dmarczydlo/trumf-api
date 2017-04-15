<?php

namespace App\Http\Controllers;

use App\Task;
use App\UserTask;
use DB;
use File;
use App\Hash;
use App\TMP;


class IntegratorController extends Controller
{

    /**
     * function to translate tryumf col name to my col name
     * @param $name_from_sql
     * @return mixed
     */
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
            'LinId' => 'line_id',
            'SloImage' => 'image_url',
            'Ilosc' =>'eq'
        ];

        return $table_tanslations[$name_from_sql];
    }

    function rstr4($length)
    {
        // uses md5 & mt_rand. Not as "random" as it could be, but it works, and its fastest from my tests
        return str_shuffle(substr(str_repeat(md5(mt_rand()), 2 + $length / 32), 0, $length));
    }

    function saveImage($collection, $path_img)
    {
        //check is exist
        $ex = Task::where('order_number', $collection->Nagid)
            ->where('line_id', $collection->LinId)->first();
        if (!$ex) {

            $image_string = hex2bin($collection->SloImage);
            $image_name = date('ymdhis') . '_' . $this->rstr4(5) . '.jpg';
            $handle = fopen($_SERVER['DOCUMENT_ROOT'] . $path_img . $image_name, "a+");
            fwrite($handle, $image_string);
            return $path_img . $image_name;
        }

        return $ex->url_image;
    }


    /**Function to update row or create
     * @param $rows
     * @return bool
     */
    function addOrUpdateRows($rows)
    {

        if (empty($rows)) {
            return false;
        }


        foreach ($rows as $row) {
            $renameRow = $this->renameHelper($row);
            $conditions = ['order_number' => $renameRow['order_number'], 'line_id' => $renameRow['line_id']];
            Task::updateOrCreate($conditions, $renameRow);
            echo 'update/create ' . $renameRow['order_number'] . ' ' . $renameRow['line_id'] . ' <br/>';
        }
        return true;
    }

    /**Generator Hash
     * @param $data
     */
    function checkAndGenerateHASH($data)
    {
        $new_hash = md5(json_encode($data));
        $last_hash = DB::table('hash')->orderBy('id', 'desc')->first();

        if (empty($last_hash) || $last_hash->hash != $new_hash) {

            $whatIsNew = $this->jsonCreateAndMerge($data);

            $this->addOrUpdateRows($whatIsNew);

            $json_new = json_encode($data);
            file_put_contents(env('JSON_PATH'), $json_new);
            Hash::create(['hash' => $new_hash]);
            echo 'done';
        } else {
            echo 'nothing to change';
        }

    }

    /**
     * DIFF to ARRAY
     * @param $new
     * @param $old
     * @return array
     */
    public function arrayDiff($new, $old)
    {
        return array_map('json_decode', array_diff(array_map('json_encode', $new), array_map('json_encode', $old)));

    }

    /**Function to create and Merge JSON
     * @param $data
     * @return array
     */
    function jsonCreateAndMerge($data)
    {
        $json_new = json_encode($data);

        if (!File::exists(env('JSON_PATH'))) {
            return $data;
        }
        $last_json = file_get_contents(env('JSON_PATH'));
        $diff = $this->arrayDiff(json_decode($json_new, true), json_decode($last_json, true));
        return $diff;
    }

    /**
     * CRON FUNCTION
     */
    function newDataChecker()
    {
        $tasks = DB::connection('sqlsrv')
            ->table('dbo.w_fnGetOrders4Isoft()')
            ->select("Nagid", "LinId", "Data", "Rd", "DataSprz", "Logo", "LogoH", "Priorytet", "Status", "GotowyProjekt", "GrafikaCzasPierwotny", "GrafikaCzasWtorny", "GrawerniaCzas", "SymKar", "StTrudnosci", 'SloImage','Ilosc')
            ->where('Nagid', '>=', env('TASK_START_ID', 1))
            ->get();

        foreach ($tasks as $kev => $val) {
            $tasks[$kev]->SloImage = $this->saveImage($val, '/images/');
        }

        //TESTED DATA
//        $tasks = DB::table('tmp_tryumf')
//            ->select("Nagid", "Linid", "Data", "Rd", "DataSprz", "Logo", "LogoH", "Priorytet", "Status", "GotowyProjekt", "GrafikaCzasPierwotny", "GrafikaCzasWtorny", "GrawerniaCzas", "SymKar", "StTrudnosci")
//            ->get();


        if (empty($tasks)) {
            echo 'empty data';
            exit();
        }
        $this->checkAndGenerateHASH($tasks);
    }

    /**
     * Function helper to rename column for import data
     * @param $task
     * @return array
     */
    function renameHelper($task)
    {
        if (empty($task))
            return $task;

        $renamedRow = [];
        foreach ($task as $k => $v) {
            if ($k == 'GrafikaCzasPierwotny') {
                if ($v > 0) {
                    $renamedRow[$this->translateData($k)] = $v * 60;
                } else {
                    if ($task->GrafikaCzasWtorny > 0) {
                        $renamedRow[$this->translateData($k)] = $task->GrafikaCzasWtorny * 60;
                    } else {
                        $renamedRow[$this->translateData($k)] = 0;
                    }
                }
            } else if ($k == 'GrawerniaCzas') {
                if ($task->GrawerniaCzas === '' || $task->GrawerniaCzas <= 0) {
                    $renamedRow[$this->translateData($k)] = 0;
                } else {
                    $renamedRow[$this->translateData($k)] = $v * 60;
                }
            } else if ($k == 'StTrudnosci') {
                if ($v > 0) {
                    $renamedRow[$this->translateData($k)] = $v;
                } else {
                    $renamedRow[$this->translateData($k)] = 0;
                }
            } else if ($k !== 'GrafikaCzasWtorny') {
                $renamedRow[$this->translateData($k)] = $v;
            }


        }
        return $renamedRow;

    }


    /**
     * TEST FOR COPY ALL TABLE
     */
    function dataCopyLocal()
    {

        //truncate
        $tmp = TMP::all();
        $tmp->truncate();
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
