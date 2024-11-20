<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm\Model\Dynamic\Type;

use Bitrix\Main\Grid\Panel\Snippet;

use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;

use Bitrix\Main\Grid\Cell\Label\Color;
use Bitrix\Socialnetwork\WorkgroupTable;
use Micros\Tasks\Reports\TasksReportTable;
use Bitrix\Main\Grid\Options as GridOptions;
use Micros\Tasks\Internals\Task\AgreedTimeTable;
use Bitrix\Main\Grid\Cell\Label\RemoveButtonType;



/**
 * @var Container
 * 
 * @var Service\Factory\Dynamic
 */

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}
if (!CModule::IncludeModule("crm"))
{
	ShowError(GetMessage("CRM_MODULE_NOT_FOUND"));
	return;
}

class tasksReportsGrid extends CBitrixComponent
{

    public function executeComponent()
    {
        global $USER;
        
        $this->arUser = $USER;
        
        $this->arResult = [];

        $this->tableMap = TasksReportTable::getMap();
        $this->tableMap["EXPENSES"] = [
            'data_type' => 'string',
            'title' => 'Затраты'
        ];
        $this->snippet = new Snippet();

        $filter = [];
        $filterOption = new Bitrix\Main\UI\Filter\Options('filter_reports_grid');
        $filterData = $filterOption->getFilter([]);
        
        foreach ($filterData as $k => $v)
        {
            $filter[$k] = $v;
        };
        
        $reportsFilter = array();

        if(isset($_REQUEST["STATUS"])){
            $filter["STATUS"] = $_REQUEST["STATUS"]; 
        }
        if(isset($_REQUEST["EMPLOYEE_ID"])){
            $filter["EMPLOYEE_ID"] = $_REQUEST["EMPLOYEE_ID"]; 
        }
        if(isset($_REQUEST["START"]) && isset($_REQUEST["END"])){
            $filter["START_DATE"] = $_REQUEST["START"];
            $filter["END_DATE"] = $_REQUEST["END"];
        }
        
        if($filter)
        {
            $reportsFilter = $this->getFilterParams($filter); 
        }

        $gridOption = new GridOptions('reports_detail_grid');
        $gridSortOptions = $gridOption->GetSorting();
        $nav_params = $gridOption->GetNavParams();

        $this->arResult["NAV"] = new Bitrix\Main\UI\PageNavigation('reports_detail_grid');
        $this->arResult["NAV"]->allowAllRecords(true)
            ->setPageSize($nav_params['nPageSize'])
            ->initFromUri();

        $sortBy = array_keys($gridSortOptions["sort"])[0];
        $sortAs = strtoupper($gridSortOptions["sort"][$sortBy]);

        $tasksOrder = [];
        if($sortBy && $sortAs){
            $tasksOrder = $this->getOrderParams($sortBy, $sortAs); 
        }

        $container = Service\Container::getInstance();
        /**
         * 190 - expenses
        */
        $this->expensesSmartItemsFactory = $container->getFactory( 190 );

        $arReports = array();
        $reports = TasksReportTable::GetList([
            "filter" => $reportsFilter,
            "order" => $tasksOrder,
            "limit" => $nav_params['nPageSize'], // Количество элементов на странице
            "offset" => $this->arResult["NAV"]->getOffset(), // Смещение для пагинации
        ]);

        $arReports = $reports->fetchAll();
        
		$this->arResult['NAV_PARAM_NAME'] = $this->arResult["NAV"]->getId();
		$this->arResult['CURRENT_PAGE'] = $this->arResult["NAV"]->getCurrentPage();

        $this->arResult["FILTER"] = self::filter();
        $this->arResult["COLUMNS"] = self::columns();
        $this->arResult["ROWS"] = self::rows($arReports);
        
        // $totalCount = count($this->arResult["ROWS"]);
        $totalCount = TasksReportTable::getCount([
            $reportsFilter,
        ]);

        $this->arResult["NAV"]->setRecordCount($totalCount);
        $this->arResult["TOTAL_ROWS_COUNT"] = $totalCount;
        
        $removeButton = $this->snippet->getRemoveButton();
        $removeButton["TEXT"] = "Исключить";
        $removeButton["TITLE"] = "Исключить отмеченные элементы";
        $removeButton["ONCHANGE"][0]["CONFIRM_MESSAGE"] = "Вы уверены, что хотите исключить";
        $removeButton["ONCHANGE"][0]["CONFIRM_APPLY_BUTTON"] = "Исключить";
        $removeButton["ONCHANGE"][0]["DATA"][0]["JS"] = "removeSelected()";

        $editButton = $this->snippet->getEditButton();

        if( $_REQUEST["STATUS"] == 0 )
        {
            if( $this->arUser->isAdmin() ){
                
                $this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"][] = array(
                    'ID' => 'send_to_approval', 
                    'TYPE'  => 'BUTTON', 
                    'TEXT' => 'Согласовать',
                    'CLASS' => 'main-grid-action-button', 
                    'ONCHANGE' => [
                        [
                            'ACTION' => 'CALLBACK',
                            'CONFIRM_MESSAGE' => 'Вы уверены, что хотите согласовать',
                            'CONFIRM_CANCEL_BUTTON' => 'Отменить',
                            'CONFIRM' => 1,
                            'DATA' => [
                                ['JS' => "updateSelected()"]
                            ]
                        ]
                    ]
                );

                $this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"][] = $editButton;
                $this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"][] = $removeButton;
            }
        }
        $this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"][] = Array( 
            'ID'    => 'actallrows_', 
            'TYPE'  => 'CHECKBOX', 
            'CLASS' => 'main-grid-for-all-checkbox',
            'VALUE' => 'Y',
            'NAME' => 'action_all_rows_',
            'LABEL' => '',
            'ONCHANGE' => [
                [
                    'ACTION' => 'CALLBACK',
                    'DATA' => [
                        [ 'JS' => "Grid.confirmForAll()"]
                    ]
                ]
            ],
        );

        // echo '<pre>'; print_r($this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"]); echo '</pre>';

        $this->arResult["LIST"] = $arReports;
        $this->arParams["GRID_ID"] = "reports_detail_grid";
        $this->arResult["USER_ID"] = $this->arUser->GetId();

        $this->includeComponentTemplate();
        return $this->arResult;
    }

    public function columns()
    {
        $columns = [
            ['id' => 'TASK', 'name' => 'Задача','type' => 'text', 'sort' => 'TASK' ],
            ['id' => 'TAGS', 'name' => 'Теги', 'default' => true ],
            ['id' => 'UF_BILLABLE', 'name' => 'Тип задачи', 'default' => true ],
            ['id' => 'GROUP_ID', 'name' => 'Проект', 'default' => true ],
            ['id' => 'RESPONSIBLE_ID', 'name' => 'Ответственный', 'default' => true ],
            ['id' => 'ACCOMPLICES', 'name' => 'Соисполнитель', 'default' => true ],
            ['id' => 'ELAPSED_TIME', 'name' => 'Затраченное время', 'default' => true, 'sort' => 'ELAPSED_TIME' ],
            ['id' => 'AGREED_TIME', 'name' => 'Внесенное сотрудником время', 'default' => true, 'sort' => 'AGREED_TIME' ],
            ['id' => 'MAIN_AGREED_TIME', 'name' => 'Согласованное руководителем время', 'default' => true, 'sort' => 'MAIN_AGREED_TIME',  'editable' => true ],
            ['id' => 'CREATED_DATE', 'name' => 'Дата создания', 'default' => true ],
        ];
        
        return $columns;
    }

    /**
     * @var Array $reports
     * @return Array
    */
    public function rows($reports)
    {
        $rows = [];
        $currentUserId = $this->arUser->getId();
        
        if( count($reports) < 1 ){
            return [];
        }

        $allEmployeTime = 0;
        $mainAgreedtimeSum = 0;
        foreach ($reports as $key => $report)
        {
            $cTaskItem = new CTaskItem($report["TASK_ID"],$currentUserId );
            
            try {
                $taskData = $cTaskItem->getData();
            } catch (TasksException $e) {
                continue;
            }
            $row = [];
            $data = [];

            $row["id"] = $report["ID"];
            foreach ($this->arResult["COLUMNS"] as $columnKey => $column)
            {
                $dataField;
                $rowField;
                switch ($column["id"]) {
                    case 'TASK':
                        $dataField = $cTaskItem["TASK"]; 
                        $rowField = $this->getEntityHtml ( $cTaskItem, "task" );
                        
                        break;
                    case 'TAGS':
                        $arTags = $cTaskItem->getTags();

                        $dataField = [];
                        $dataField["items"] = [];

                        foreach ($arTags as $key => $arTag) {
                            $dataField["items"][] = [
                                "text" => $arTag,
                                "active" => "",
                                "events" => [
                                    "click" => "BX.Tasks.GridActions.toggleFilter.bind(BX.Tasks.GridActions, {\"TAG\":[\"$arTag\"],\"TAG_label\":[\"$arTag\"]}, 0, true)" 
                                ],
                            ];
                        }
                        
                        $dataField["addButton"]["events"]["click"] = "BX.Tasks.GridActions.onTagUpdateClick.bind(BX.Tasks.GridActions, ".$arTask["ID"].", 1)";
                        $rowField = self::getEntityHtml ( $arTags, "tags" );
                        break;
                    case 'UF_BILLABLE':
                        $dataField = $cTaskItem["UF_BILLABLE"]; 
                        $rowField = $cTaskItem["UF_BILLABLE"] == "36" ? "Подлежит к оплате" : "Не подлежит к оплате" ;

                        break;
                    case 'GROUP_ID':
                        if($cTaskItem["GROUP_ID"]){
                            $project = $this->getProject( $cTaskItem["GROUP_ID"] );
                            
                            $dataField = $cTaskItem["GROUP_ID"]; 
                            $rowField = $this->getEntityHtml ( $project, "project" );
                        }else{
                            $rowField = '';
                        }
                        
                        break;
                    case 'RESPONSIBLE_ID':
                        if($cTaskItem["RESPONSIBLE_ID"]){
                            $responsible = $this->getResponsible( $cTaskItem["RESPONSIBLE_ID"] );
                            
                            $dataField = $cTaskItem["RESPONSIBLE_ID"]; 
                            $rowField = $this->getEntityHtml ( $responsible, "user" );
                        }else{
                            $rowField = '';
                        }
                        
                        break;
                    case 'ACCOMPLICES':
                        if($cTaskItem["ACCOMPLICES"]){
                            $arAccomplcs = [];
                            foreach ($cTaskItem["ACCOMPLICES"] as $key => $accomplcs) {
                                $arAccomplcs[] = $this->getResponsible( $accomplcs );
                            }
                            $dataField = $cTaskItem["ACCOMPLICES"]; 
                            $rowField = $this->getEntityHtml ( $arAccomplcs, "accomplcs" );
                        }else{
                            $rowField = '';
                        }
                        break;
                    case 'ELAPSED_TIME':
                        $dataField = $report["ELAPSED_TIME"] ?? 0;
                        $rowField = gmdate("H:i", $report["ELAPSED_TIME"] ?? 0);
                        break;
                    case 'AGREED_TIME':
                        $rAgreedTimeList = AgreedTimeTable::getList([
                            "filter" => [
                                "TASK_ID" => $cTaskItem["ID"],
                                "USER_ID" => $report["EMPLOYEE_ID"]
                            ]
                        ]);
                        $agreedTimeSum = 0; // sec
                        while ( $agreedTimeItem = $rAgreedTimeList->fetch()) {
                            $agreedTimeSum += $agreedTimeItem["SECONDS"];
                        }
                        $allEmployeTime += $agreedTimeSum;
                        $dataField = $agreedTimeSum;
                        $rowField = self::formatTime((int)$agreedTimeSum);
                        break;
                    case 'MAIN_AGREED_TIME':
                        $mainAgreedtimeSum += $report["AGREED_TIME"];
                        $dataField = $report["AGREED_TIME"] ?? 0;
                        $rowField = gmdate("H:i", $report["AGREED_TIME"] ?? 0);
                        break;
                    case 'CREATED_DATE':

                        $date = new DateTime($cTaskItem["CREATED_DATE"], 'd.m.Y H:i:s');
                        setlocale(LC_TIME, 'ru_RU.UTF-8');
                        $formattedDate = strftime('%a %d %B', $date->getTimestamp());
                        
                        $dataField = $cTaskItem["CREATED_DATE"];
                        $rowField = ucfirst($formattedDate);
                        break;
                    default:
                        $rowField = $report[$column["id"]];
                    break;
                };
                
                $row["columns"][$column["id"]] = $rowField; 
                $row["data"][$column["id"]] = $dataField; 
                $row["data"]["EMPLOYEE_ID"] = $report["EMPLOYEE_ID"];
                $row["data"]["STATUS"] = 0;

            }
            if($row["data"]["AGREED_TIME"] != $row["data"]["MAIN_AGREED_TIME"]){
                $row["attrs"]["data-row-color"] = "main-grid-row-color-red";
                $html = "<span style='color:red'>".$row["columns"]["AGREED_TIME"]."</span>";
                $row["columns"]["AGREED_TIME"] = $html; 

            }
            $rows[] = $row;
        }

        $this->arResult["EMPLOYEY_TIME_SUM"] = [
            "SECUNDS" => $allEmployeTime,
            "CONVERTED" => gmdate("H:i", $allEmployeTime)
        ];
        $this->arResult["AGREED_TIME_SUM"] = [
            "SECUNDS" => $mainAgreedtimeSum,
            "CONVERTED" => gmdate("H:i", $mainAgreedtimeSum)
        ];
        
        return $rows;
    }

    
    private function filter()
    {
        $columns = [];        
        return $columns;
    }

    
    public function getOrderParams( $sortBy, $sortAs )
    {
        switch($sortBy){
            case 'TASK':
                return [ "TASK_ID" => $sortAs ];
            break;
            default:
                return [ $sortBy => $sortAs ];
            break;
        }

    }

    public function getFilterParams( $gridFilter )
    {
        $filter = [];
        foreach ($gridFilter as $key => $value) {
            switch ($key) { 
                case 'START_DATE':
                    $filter["<=START_DATE"] = new DateTime($value); 
                    break;
                case 'END_DATE':
                    $date = new DateTime($value);
                    $filter["<=END_DATE"] = $date->toUserTime(); 
                    break;
                default:
                    $filter[$key] = $value;
                    break;
            }
        }
        // echo '<pre>'; print_r($filter); echo '</pre>';
        return $filter;
    }

    public function getExpensesItems ($filter = [], $sort = [], $order = [])
    {
        return $this->expensesSmartItemsFactory->getItems(
            array(
                "filter" => $filter
            )
        );

    }

    public function getResponsible ( $id )
    {
        $rsUser = CUser::GetByID($id);
        $arUser = $rsUser->Fetch();
        
        if($arUser && $arUser["PERSONAL_PHOTO"]){
            $rsFile = CFile::GetByID( $arUser["PERSONAL_PHOTO"] );
			$arFile = $rsFile->Fetch();
            $arUser["AVATAR"] = $arFile; 
        }
        

        return $arUser;
    }

    public function getProject ( $id )
    {
        $project = [];
        $project = WorkgroupTable::getList( [ "select" => ["ID", "ACTIVE", "NAME", "DESCRIPTION", "AVATAR_TYPE", "IMAGE_ID"], "filter" => [ "ID" => $id ]  ] )->Fetch();

        if($project && $project["IMAGE_ID"]){
            $rsFile = CFile::GetByID( $project["IMAGE_ID"] );
			$arFile = $rsFile->Fetch();
            $project["AVATAR"] = $arFile; 
        }

        return $project;
    }
    
    public function grouppingReportsByEmployeeAndDate ( &$reports )
    {
        $groupedReports = [];
        foreach ($reports as $report) {
            // Создаем ключ для группировки
            $key = $report['EMPLOYEE_ID'] . '-' . $report['START_DATE']->format('Y-m-d H');

            // Добавляем элемент в группированный массив
            if (!isset($groupedReports[$key])) {
                $groupedReports[$key] = [
                    'EMPLOYEE_ID' => $report['EMPLOYEE_ID'],
                    'START_DATE' => $report['START_DATE'],
                    'END_DATE' => $report['END_DATE'],
                ];
            };

            // Добавляем задачу в текущую группу
            $groupedReports[$key]["AGREED_TIME"] += $report['AGREED_TIME'];
            $groupedReports[$key]["ELAPSED_TIME"] += $report['ELAPSED_TIME'];

        }
        
        $reports = $groupedReports;

        return $reports;
    }

    protected function formatTime($seconds)
    {
        // Вычисляем количество минут и часов
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        // Форматируем строки
        $formatted_hours = str_pad($hours, 2, "0", STR_PAD_LEFT);
        $formatted_minutes = str_pad($minutes, 2, "0", STR_PAD_LEFT);

        return "$formatted_hours:$formatted_minutes";
    }

    public function getEntityHtml ( $arData, $entity )
    {
        switch ($entity) {
            case "task":
                $currUser = $this->arUser;
                $currUserId = $currUser->GetId();
                
                $html = "<a data-value=\"$arData[ID]\" href=\"/company/personal/user/$currUserId/tasks/task/view/$arData[ID]/?ta_sec=tasks&amp;ta_sub=list&amp;ta_el=title_click\" class=\"task-title task-status-text-color-in-progress\">";
                $html .= $arData['TITLE']."<span class=\"task-title-indicators\"></span>";
                $html .= "</a>";
    
                return $html;
            break;
            case "tags":
                $currUser = $this->arUser;
                $currUserId = $currUser->GetId();
                $html = "<div class=\"main-grid-tags\">";
                    foreach ($arData as $key => $tag) {
                        $html .= "<span class=\"main-grid-tag\"><span class=\"main-grid-tag-inner\">$tag</span></span>\n";        
                    }
                $html .= "</div>";
                return $html;
            case "accomplcs":
                $html = "<span style=\"display: flex;\">";
                foreach ($arData as $key => $value) {
                    $html .= "<a class=\"tasks-grid-group\" onclick=\"BX.PreventDefault()\" href=\"javascript:void(0)\">";
                    $html .= $value["AVATAR"] ?
                    "<span class=\"tasks-grid-avatar ui-icon ui-icon-common-user-group\">
                        <i style=\"background-image: url('".$value["AVATAR"]["SRC"]."')\"></i>
                    </span>" : 
                    "<span class=\"tasks-grid-avatar ui-icon ui-icon-common-user tasks-grid-avatar-empty\">
                        <i></i>
                    </span>" ;
                    $html .= "<span class=\"tasks-grid-group-inner\">".$value["NAME"]." ".$value["LAST_NAME"]."</span><span class=\"tasks-grid-filter-remove\"></span>";
                    $html .= "</a>";
                }
                $html .= "</span>";
                return $html;
            break;
            case "accomplcs":
                $html = "<a class=\"tasks-grid-group\" onclick=\"BX.PreventDefault()\" href=\"javascript:void(0)\">";
                    foreach ($arData as $key => $value) {
                        $html .= $value["AVATAR"] ?
                        "<span class=\"tasks-grid-avatar ui-icon ui-icon-common-user-group\">
                            <i style=\"background-image: url('".$value["AVATAR"]["SRC"]."')\"></i>
                        </span>" : 
                        "<span class=\"tasks-grid-avatar ui-icon ui-icon-common-user tasks-grid-avatar-empty\">
                            <i></i>
                        </span>" ;
                        $html .= "<span class=\"tasks-grid-group-inner\">".$value["NAME"]." ".$value["LAST_NAME"]."</span><span class=\"tasks-grid-filter-remove\"></span>";
                    }
                $html .= "</a>";
                return $html;
            break;
            default:
                $html = "<a class=\"tasks-grid-group\" onclick=\"BX.PreventDefault()\" href=\"javascript:void(0)\">";
                
                if($entity == "user" ){
                    $html .= $arData["AVATAR"] ?
                        "<span class=\"tasks-grid-avatar ui-icon ui-icon-common-user-group\">
                            <i style=\"background-image: url('".$arData["AVATAR"]["SRC"]."')\"></i>
                        </span>" : 
                        "<span class=\"tasks-grid-avatar ui-icon ui-icon-common-user tasks-grid-avatar-empty\">
                            <i></i>
                        </span>" ;
                    $html .= "<span class=\"tasks-grid-group-inner\">".$arData["NAME"]." ".$arData["LAST_NAME"]."</span><span class=\"tasks-grid-filter-remove\"></span>";
                }else if($entity == "project"){;
                    $html .= $arData["AVATAR"] ?
                        "<span class=\"tasks-grid-avatar ui-icon ui-icon-common-user-group\">
                            <i style=\"background-image: url('".$arData["AVATAR"]["SRC"]."')\"></i>
                        </span>" : 
                        "<span class=\"tasks-grid-avatar sonet-common-workgroup-avatar --".$arData["AVATAR_TYPE"]."\">
                            <i></i>
                        </span>" ;
                    $html .= "<span class=\"tasks-grid-group-inner\">".$arData["NAME"]."</span><span class=\"tasks-grid-filter-remove\"></span>";
                }
                
                $html .= "</a>";
                
                return $html;
            break;
        }

    }

}
