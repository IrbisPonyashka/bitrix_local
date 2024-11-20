<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Micros\Tasks\Internals\Task\AgreedTimeTable;
use Micros\Tasks\Reports\TasksReportTable;
use Bitrix\Main\Type\DateTime;

/**
 * @global CUser $USER
 */


global $USER, $DB, $APPLICATION;


$sendResponse = function($data, array $errors = array())
{
    if ($data instanceof Bitrix\Main\Result)
    {
        $errors = $data->getErrorMessages();
        $data = $data->getData();
    }

    $result = $data;
    $result['errors'] = $errors;
    $result['success'] = count($errors) === 0;

    header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

    echo \Bitrix\Main\Web\Json::encode($result);
    CMain::FinalActions();
    die();
};

$sendError = function($error) use ($sendResponse)
{
    $sendResponse(array(), (array)$error);
};

if (!CModule::IncludeModule('crm'))
{
    $sendError('Module CRM is not installed.');
}

$input_data_json = file_get_contents("php://input");
$input_data = json_decode($input_data_json,1);


if($_REQUEST["action"] && $_REQUEST["type"] && !empty($input_data))
{
    switch ($_REQUEST["type"]) {
        case 'reports':
            if($_REQUEST["action"] == 'add')
            {
                if( !$input_data['data'] && !empty($input_data['data'])){    
                    $sendError("Поле data пустое", ['success' => false]);
                    return;
                }
                // для начала нужно убедиться что по этой задаче данный пользователь не отправлял отчет
                
                $result = [];
                foreach ($input_data['data'] as $key => $arData)
                {
                    $getReportsByEmployeeAndTaskId = getReportListByTaskIdUserId($arData["TASK_ID"],$arData["EMPLOYEE_ID"]);
                    if(!empty($getReportsByEmployeeAndTaskId))
                    {
                        $report = $getReportsByEmployeeAndTaskId[0];
                        
                        /* если статус = ожидает/принят/принят с доработкой, запрещаем отправку */
                        if($report["STATUS"] == 0 || $report["STATUS"] == 1 || $report["STATUS"] == 3) {

                            $result["success"] = false;
                            $result["message"] = "Выбранная(-ые) задача(-и) уже отправлены на согласование";
                            $result["tasks_id"][] = $report["ID"];
                        
                        /* иначе обновляем статус этой задачи */
                        }else{
                            try {
                                $reportAddRes = TasksReportTable::update($report["ID"],["STATUS" => 0]);

                                if($reportAddRes->isSuccess()){
                                    $result["success"] = true;
                                }else{
                                    $result["success"] = false;
                                    $result["error"] = $reportAddRes->getErrorMessages();
                                    $result["tasks_id"][] = $arData["ID"];
                                }
                            } catch (\Exception $e) {
                                $errorMsg = $result["error_message"] = $e->getMessage();
                                if($errorMsg == "Mysql query error: (1062) Duplicate entry '20' for key 'idx_unique_task_id'"){
                                    $result["error_message"] = "Выбранная(-ые) задача(-и) отправлены на согласование";
                                }
                                $result["success"] = false;
                                $result["tasks_id"][] = $arData["ID"];
                            }

                        }

                    }else{
                        $arData["STATUS"] = $arData["STATUS"] ?? 0; 
                        $arData["START_DATE"] = new DateTime($arData["START_DATE"]);
                        $arData["END_DATE"] = new DateTime($arData["END_DATE"]);
                        
                        try {
                            $reportAddRes = TasksReportTable::add($arData);
                            if($reportAddRes->isSuccess()){
                                $result["success"] = true;
                            }else{
                                $result["success"] = false;
                                $result["error"] = $reportAddRes->getErrorMessages();
                                $result["tasks_id"][] = $arData["ID"];
                            }
                        } catch (\Exception $e) {
                            $errorMsg = $result["error_message"] = $e->getMessage();
                            if($errorMsg == "Mysql query error: (1062) Duplicate entry '20' for key 'idx_unique_task_id'"){
                                $result["error_message"] = "Выбранная(-ые) задача(-и) отправлены на согласование";
                            }
                            $result["success"] = false;
                            $result["tasks_id"][] = $arData["ID"];
                        }
                    }
                }
                // echo '<pre>'; print_r($result); echo '</pre>';
                header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                echo \Bitrix\Main\Web\Json::encode($result);

            }else if($_REQUEST["action"] == 'list')
            {
                if( !$input_data['data'] && !empty($input_data['data'])){    
                    $sendError("Поле data пустое", ['success' => false]);
                    return;
                }
                $paramsData = $input_data['data'];

                if($paramsData["filter"] && $paramsData["filter"]["START_DATE"]){
                    $paramsData["filter"]["START_DATE"] = new DateTime($paramsData["filter"]["START_DATE"]);
                }
                if($paramsData["filter"] && $paramsData["filter"]["END_DATE"]){
                    $paramsData["filter"]["END_DATE"] = new DateTime($paramsData["filter"]["END_DATE"]);
                }

                $reportList = TasksReportTable::getList($paramsData);
                if(isset($input_data["update"]) && $input_data["update"]!=0){
                    $result = [];
                    while ($report = $reportList->Fetch()) {
                        $updateResult = TasksReportTable::update($report["ID"], ["STATUS" => 1]);
                        if($updateResult->isSuccess()){
                            $result["success"] = true;
                        }else{
                            $result["success"] = false;
                            $result["report_id"] = $report["ID"];
                            // header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                            // echo \Bitrix\Main\Web\Json::encode(false."\n");
                        }
                    }
                    header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                    echo \Bitrix\Main\Web\Json::encode($result);

                }else{
                    header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                    echo \Bitrix\Main\Web\Json::encode($reportList->fetchAll());
                }
            }else if($_REQUEST["action"] == 'delete')
            {
                
                if( !$input_data['data'] && !empty($input_data['data'])){    
                    $sendError("Поле data пустое", ['success' => false]);
                    return;
                }

                $result = TasksReportTable::delete($input_data["id"]);
                if($result->isSuccess()){
                    header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                    echo \Bitrix\Main\Web\Json::encode(true);
                }else{
                    header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                    echo \Bitrix\Main\Web\Json::encode(false);
                }
            }else if($_REQUEST["action"] == 'update')
            {
                
                if( !$input_data['data'] && !empty($input_data['data'])){    
                    $sendError("Поле data пустое", ['success' => false]);
                    return;
                }

                $result = TasksReportTable::update($input_data["id"],$input_data['data']);
                // echo '<pre>'; print_r($result->getErrorMessages()); echo '</pre>';
                if($result->isSuccess()){
                    header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                    echo \Bitrix\Main\Web\Json::encode(true);
                }else{
                    header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                    echo \Bitrix\Main\Web\Json::encode($result->getErrorMessages()[0]);
                }
            }

        break;
        case 'agreed_time':
            if($_REQUEST["action"] == 'add')
            {   
                $responseArr = $input_data;
                $result = [];
                foreach ($responseArr as $resKey => $resValue) {
                    
                    $getAgrTime = getAgreedTimeByTaskIdUserId($resValue["TASK_ID"],$resValue["USER_ID"]);
                    
                    $time = getSecondsAndMinutes($resValue["AGREED_TIME"]);
                    if($getAgrTime){

                        $update = updateAgreedTimeById($getAgrTime[0]["ID"], [ "MINUTES" => $time["MINUTES"], "SECONDS" => $time["SECONDS"]] );
                        if($update->isSuccess() && $update->isSuccess()==1){
                            $result["success"] = true;
                        }else{
                            $result["success"] = false;
                            $result["TASKS_ID"][] = $resValue["TASK_ID"];
                        }
                    }else{
                        $currUserId = $USER->GetId();
                        $dateTime = new DateTime(); 
                        
                        $fields = [
                            "CREATED_DATE" => $dateTime,
                            "DATE_START" => $dateTime,
                            "DATE_STOP" => $dateTime,
                            "USER_ID" => $currUserId,
                            "TASK_ID" => $resValue["TASK_ID"],
                            "MINUTES" => $time["MINUTES"],
                            "SECONDS" => $time["SECONDS"],
                            "SOURCE" => 2,
                        ];

                        $addAgrTime = addAgreedTime($fields);
                        header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                        echo \Bitrix\Main\Web\Json::encode($addAgrTime);
                    }
                
                }
                header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
                echo \Bitrix\Main\Web\Json::encode($result);
            }

        break;
    }

}

function getAgreedTimeByTaskIdUserId($taskId, $userId)
{
    return AgreedTimeTable::getList(
        Array( "filter" => [ "TASK_ID" => $taskId, "USER_ID" => $userId ])
    )->fetchAll();

}

function updateAgreedTimeById($taskId, $fields)
{
    return AgreedTimeTable::update( $taskId, $fields);
}

function addAgreedTime($fields)
{
    return AgreedTimeTable::add( $fields );
}


function getReportListByTaskIdUserId($taskId, $userId)
{
    return TasksReportTable::getList(
        Array( "filter" => [ "TASK_ID" => $taskId, "EMPLOYEE_ID" => $userId ])
    )->fetchAll();

}


/**
 * @var $time - hh::mm 
 * @return  Array("SECONDS", "MINUTES") 
*/
function getSecondsAndMinutes($time)
{
    list($hours, $minutes) = explode(":", $time);

    // Преобразуем строки в целые числа
    $hours = (int)$hours;
    $minutes = (int)$minutes;
    // return [$hours, $minutes];
    // Вычисляем общее количество минут
    $total_minutes = $hours * 60 + $minutes;
    
    // Вычисляем общее количество секунд
    $seconds = $total_minutes * 60;

    return ["MINUTES" => $total_minutes, "SECONDS" => $seconds];
}

function updateReportItem($reportList, $fields)
{
    while ($report = $reportList->Fetch()) {
        $updateResult = TasksReportTable::update($report["ID"], $fields);
        // if (!$updateResult->isSuccess()) {
        //     $allUpdated = false;
        // }
        return $updateResult;
    }

}
