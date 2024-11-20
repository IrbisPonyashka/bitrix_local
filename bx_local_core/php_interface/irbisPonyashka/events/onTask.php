<?php

namespace IrbisPonyashka\Events\OnTasks;

use IrbisPonyashka\Tasks\Internals\Task\AgreedTimeTable;
use Bitrix\Tasks\Internals\Task\ElapsedTimeTable;
use \IrbisPonyashka\Tasks\Reports;


if (!\CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

class taskHandler
{
    public function __construct()
    {

    }

    
    /**
     * Сносим запись, связанную с этой задачей в таблице b_tasks_agreed_time  
    */
    public static function onTaskDelete(&$arFields)
    {

        $rAgreedTimeListByTaskId = AgreedTimeTable::getList([
            "filter" => [ "TASK_ID" => $arFields ],
            "select" => ["ID"]
        ]);
        while($agreedTime = $rAgreedTimeListByTaskId->fetch()){
            AgreedTimeTable::delete($agreedTime["ID"]);
        }
    }

    public static function onBeforeTaskUpdate(&$id, &$data, &$arTaskCopy)
    {
        // self::LogB([$id, $data, $arTaskCopy], "TASK___");
        // Запрещаем редактирование с выводом сообщения
        // self::displayTaskBanMessage($arFields);
        
    }

    public static function onBeforeTaskDelete(&$arFields)
    {
        // Запрещаем удаление с выводом сообщения
        self::displayTaskBanMessage($arFields);
    }

    /**
     * Метод вызывается после добавления в лог действий над задачей записи о затраченном времени.  
    */
    public static function onTaskElapsedTimeAdd(&$arFields)
    {
        if(is_int($arFields))
        {
            $thisElpsTime = ElapsedTimeTable::getById($arFields)->fetch();
            
            if($thisElpsTime){
                $cTask = new \CTaskItem($thisElpsTime["TASK_ID"],$thisElpsTime["USER_ID"] );
                $agreedTimeListByUserAndTask = AgreedTimeTable::getList([
                    "filter" => [
                        "TASK_ID" => $thisElpsTime["TASK_ID"],
                        "USER_ID" => $thisElpsTime["USER_ID"]
                    ]
                ])->fetchAll();
                if(!empty($agreedTimeListByUserAndTask)){
                    $agreedTime = $agreedTimeListByUserAndTask[0];

                    $upd = AgreedTimeTable::update($agreedTime["ID"], [
                        "SECONDS" => $cTask["TIME_SPENT_IN_LOGS"]
                    ]);
                }else{

                    $arFields = [
                        "CREATED_DATE" =>   $thisElpsTime["CREATED_DATE"] ?? "",
                        "DATE_START" =>     $thisElpsTime["DATE_START"] ?? "",
                        "DATE_STOP" =>      $thisElpsTime["DATE_STOP"] ?? "",
                        "USER_ID" =>        $thisElpsTime["USER_ID"] ?? "",
                        "TASK_ID" =>        $thisElpsTime["TASK_ID"] ?? "",
                        "MINUTES" =>        $thisElpsTime["MINUTES"] ?? "",
                        "SECONDS" =>        $cTask["TIME_SPENT_IN_LOGS"] ?? "",
                        "SOURCE" =>         $thisElpsTime["SOURCE"] ?? "",
                        "COMMENT_TEXT" =>   $thisElpsTime["COMMENT_TEXT"] ?? "",
                    ];
                    AgreedTimeTable::add($arFields);
                }
            }
        }
    }

    /**
     * Метод вызывается после добавления в лог действий над задачей записи о затраченном времени.  
    */
    public static function onTaskElapsedTimeUpdate(&$arFields)
    {
    }

    /**
     * Метод вызывается после добавления в лог действий над задачей записи о затраченном времени.  
    */
    public static function onTaskElapsedTimeDelete(&$arFields)
    {
    }

    protected static function displayTaskBanMessage($taskId){

        $filter = [
            "STATUS" => "1",
            "TASK_ID" => $taskId
        ];

        $query = Reports\TasksReportTable::GetList([
            "filter" => $filter,
        ]);

        $count = $query->getSelectedRowsCount();

        if($count > 0){
            $message = "Данная задача используется в $count";
            $message .= $count > 1 ? " отчетах" : " отчете" ;

            throw new \Bitrix\Tasks\Control\Exception\TaskUpdateException($message);
            // throw new \Bitrix\Tasks\ActionFailedException($message);
        }
    }

    /**
     * метод для объединения сущностей  
     * @var $seedID сущность-источник 
     * @var $targID сущность-приемник 
     * @var $entityTypeId ID-сущности (deal/company/contact)
    */
    protected static function entityMerge($seedID, $targID, $entityTypeId)
    {    
        $merger = \Bitrix\Crm\Merger\EntityMerger::create($entityTypeId, 8262, false);
        $data = $merger->mergeBatch($seedID, $targID);
        return true;
    }     
        
    protected static function LogB($result, $comment) // метод для просмотра логов
    {
        $html = '\-------' . $comment . "---------\n";
        $html .= print_r($result, true);
        $html .= "\n" . date("d.m.Y H:i:s") . "\n--------------------\n\n\n";
        $file = $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/irbisPonyashka/logs/onTasksLogs.txt";
        $old_data = file_get_contents($file);
        file_put_contents($file, $html . $old_data);
    }
    
}