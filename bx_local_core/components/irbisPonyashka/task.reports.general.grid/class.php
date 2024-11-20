<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Service;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Router;


use Bitrix\Main\Loader;
use Bitrix\Main\Grid\Editor\Types;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Main\UI\Filter\DateType;

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

        // $types = Types::getList();
        // echo '<pre>'; print_r($types); echo '</pre>';

        $this->arResult = [];

        $this->tableMap = TasksReportTable::getMap();
        $this->tableMap["EXPENSES"] = [
            'data_type' => 'string',
            'title' => 'Затраты'
        ];
        $this->snippet = new Snippet();

        $filter = [];
        $filterOption = new Bitrix\Main\UI\Filter\Options('reports_general_grid');
        $filterData = $filterOption->getFilter([]);

		$request = $this->request;
        $request_filter = $request->toArray();
        
        if(!empty($request_filter["GROUP_ID"])){
            $filterData["GROUP_ID"] = $request_filter["GROUP_ID"];
        }
        
        foreach ($filterData as $k => $v)
        {
            $filter[$k] = $v;
        };
        
        $reportsFilter = array();
        if($filter)
        {
            $reportsFilter = $this->getFilterParams($filter); 
        }

        $this->gridOption = new GridOptions('reports_general_grid');

        $gridSortOptions = $this->gridOption->GetSorting();
        $nav_params = $this->gridOption->GetNavParams();

        $this->arResult["NAV"] = new Bitrix\Main\UI\PageNavigation('reports_general_grid');
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
         * 184 - rates
        */
        $this->expensesSmartItemsFactory = $container->getFactory( 190 );
        $this->ratesSmartItemsFactory = $container->getFactory( 184 );

        // $reportsFilter["STATUS"] = 1;
        $arReports = array();
        
        if( !$reportsFilter["HIDE_ALL"] )
        {
            $reports = TasksReportTable::GetList([
                "filter" => $reportsFilter,
                "order" => $tasksOrder,
                "limit" => $nav_params['nPageSize'], // Количество элементов на странице
                "offset" => $this->arResult["NAV"]->getOffset(), // Смещение для пагинации
            ]);
            
            $arReports = $reports->fetchAll();

            $totalCount = TasksReportTable::getCount([
                $reportsFilter,
            ]);
    
            $this->arResult["NAV"]->setRecordCount($totalCount);
            $this->arResult["TOTAL_ROWS_COUNT"] = $totalCount;
        }

		$this->arResult['NAV_PARAM_NAME'] = $this->arResult["NAV"]->getId();
		$this->arResult['CURRENT_PAGE'] = $this->arResult["NAV"]->getCurrentPage();

        $this->arResult["COLUMNS"] = self::columns();
        $this->arResult["ROWS"] = self::rows($arReports);
        $this->arResult["FILTER"] = self::filter();

        $removeButton = $this->snippet->getRemoveButton();
        $removeButton["TEXT"] = "Исключить";
        $removeButton["TITLE"] = "Исключить отмеченные элементы";
        $removeButton["ONCHANGE"][0]["CONFIRM_APPLY_BUTTON"] = "Исключить";
        $removeButton["ONCHANGE"][0]["DATA"][0]["JS"] = "removeSelected()";
        
        // $editButton = $this->snippet->getEditButton();
        // echo '<pre>'; print_r($editButton); echo '</pre>';

        $this->arResult["GROUP_ACTIONS"] = [
            'GROUPS' => [ 
                [ 
                    'ITEMS' => [ 
                        $this->snippet->getEditButton(),
                        [ 
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
                        ]
                    ], 
                ]
            ]
        ];

        $this->arResult["LIST"] = $arReports;
        $this->arParams["GRID_ID"] = "reports_general_grid";
        $this->arResult["USER_ID"] = $this->arUser->GetId();
        
		if(empty($this->arParams["GET_RESULT"]))
		{
			$this->includeComponentTemplate();
		}
		
		return $this->arResult;
    }

    public function columns()
    {
        $columns = [
            ['id' => 'TASK', 'name' => 'Задача','type' => 'text', 'sort' => 'TASK'],
            ['id' => 'TAGS', 'name' => 'Теги', 'default' => true ],
            // ['id' => 'STATUS', 'name' => 'Статус', 'default' => true ],
            [   'id' => 'INVOICE_STATUS',
                'name' => 'Статус инвойсирования',
                'default' => true ,
                'type' => 'list' ,
                'editable' => [
                    'items' => self::getEnumUserFieldList("TASKS_TASK", "UF_INVOICE_STATUS"),
                ],
            ],
            ['id' => 'UF_BILLABLE', 'name' => 'Тип задачи', 'default' => true ],
            ['id' => 'RATE', 'name' => 'Цена', 'default' => true ],
            ['id' => 'RESPONSIBLE_RATE', 'name' => 'Ставка специалиста', 'default' => true ],
            ['id' => 'GROUP_ID', 'name' => 'Проект', 'default' => true ],
            ['id' => 'RESPONSIBLE_ID', 'name' => 'Ответственный', 'default' => true ],
            ['id' => 'ACCOMPLICES', 'name' => 'Соисполнитель', 'default' => true ],
            ['id' => 'AGREED_TIME', 'name' => 'Внесенное сотрудником время', 'default' => true, 'sort' => 'ELAPSED_TIME' ],
            ['id' => 'MAIN_AGREED_TIME', 'name' => 'Согласованное руководителем время', 'default' => true, 'sort' => 'AGREED_TIME'],
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

        $agreedtimeSum = 0;
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
                /* ставка спецаилиста */
                $thisRate = $this->getRatesItems(["UF_CRM_6_PROJECT_LINK" => $cTaskItem["GROUP_ID"], "UF_CRM_6_SPECIALIST" => $cTaskItem["RESPONSIBLE_ID"]]);
                if(empty($thisRate)){
                    $thisRate = $this->getRatesItems(["UF_CRM_6_SPECIALIST" => $cTaskItem["RESPONSIBLE_ID"]]);
                }

                $dataField;
                $rowField;
                switch ($column["id"]) {
                    case 'TASK':
                        $dataField = $cTaskItem["ID"]; 
                        $docField = $cTaskItem["TITLE"]; 
                        $rowField = $this->getEntityHtml ( $cTaskItem, "task" );
                        
                        break;
                    case 'INVOICE_STATUS':
                        
                        if(!empty($taskData["UF_INVOICE_STATUS"]))
                        {
                            $dataField = $docField = $rowField = self::getEnumUserFieldValueById("TASKS_TASK", "UF_INVOICE_STATUS", $taskData["UF_INVOICE_STATUS"]);
                            // $dataField = $docField = $rowField = $taskData["UF_INVOICE_STATUS"];
                        }else{
                            $dataField = $docField = $rowField = "Не выбран";
                        }
                        
                        break;
                    case 'TAGS':
                        if($cTaskItem["ID"]){
                        $arTags = $cTaskItem->getTags();
                            $dataField = [];
                            $dataField["items"] = [];
                            $docField = "";
                            foreach ($arTags as $key => $arTag) {
                                $docField .= "$arTag ";
                                $dataField["items"][] = [
                                    "text" => $arTag,
                                    "active" => "",
                                    "events" => [
                                        "click" => "BX.Tasks.GridActions.toggleFilter.bind(BX.Tasks.GridActions, {\"TAG\":[\"$arTag\"],\"TAG_label\":[\"$arTag\"]}, 0, true)" 
                                    ],
                                ];
                            }
                        }
                        
                        $dataField["addButton"]["events"]["click"] = "BX.Tasks.GridActions.onTagUpdateClick.bind(BX.Tasks.GridActions, ".$arTask["ID"].", 1)";
                        $rowField = self::getEntityHtml ( $arTags, "tags" );
                        break;
                    case 'UF_BILLABLE':
                        $dataField = $cTaskItem["UF_BILLABLE"]; 
                        $docField = $rowField = $cTaskItem["UF_BILLABLE"] == "36" ? "Подлежит к оплате" : "Не подлежит к оплате" ;

                        break;
                    case 'GROUP_ID':
                        if($cTaskItem["GROUP_ID"]){
                            $project = $this->getProject( $cTaskItem["GROUP_ID"] );
                            
                            $docField = $project["NAME"]; 
                            $dataField = $cTaskItem["GROUP_ID"]; 
                            $rowField = $this->getEntityHtml ( $project, "project" );
                        }else{
                            $docField = $rowField = '';
                        }
                        
                        break;
                    case 'RESPONSIBLE_ID':
                        if($cTaskItem["RESPONSIBLE_ID"]){
                            $responsible = $this->getResponsible( $cTaskItem["RESPONSIBLE_ID"] );
                            $docField = [
                                "name" => $responsible["NAME"]." ".$responsible["LAST_NAME"],
                                "title" => $responsible["WORK_POSITION"]
                            ]; 
                            $dataField = $cTaskItem["RESPONSIBLE_ID"]; 
                            $rowField = $this->getEntityHtml ( $responsible, "user" );
                        }else{
                            $docField = $rowField = '';
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
                            $docField = $rowField = '';
                        }
                        break;
                    case 'STATUS':
                        switch ($report[$column["id"]]){
                            case 0:
                                $rowField = "Ожидает подтверждения";
                                break;
                            case 1:
                                $rowField = "Подтвержден";
                                break;
                            case 2:
                                $rowField = "Не принят";
                                break;
                            case 3:
                                $rowField = "Принят с исправлениями";
                                break;
                            default:
                        }
                        $docField = $rowField;
                        break;
                    case 'ELAPSED_TIME':
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
                        $dataField = $agreedTimeSum;
                        $docField = $rowField = self::formatTime((int)$agreedTimeSum);
                        break;
                    case 'MAIN_AGREED_TIME':
                        $agreedtimeSum += $report["AGREED_TIME"];
                        $dataField = $report["AGREED_TIME"] ?? 0;
                        $docField = $rowField = gmdate("H:i", $report["AGREED_TIME"] ?? 0);
                        break;
                    case 'CREATED_DATE':

                        $date = new DateTime($cTaskItem["CREATED_DATE"], 'd.m.Y H:i:s');
                        setlocale(LC_TIME, 'ru_RU.UTF-8');
                        $formattedDate = strftime('%a %d %B', $date->getTimestamp());
                        
                        $docField = $dataField = $cTaskItem["CREATED_DATE"];
                        $rowField = ucfirst($formattedDate);
                        break;
                    case 'RATE':
                        
                        if($thisRate){
                            $timeInHours = $report["AGREED_TIME"] / 3600;
                            $rate = explode("|",$thisRate[0]["UF_CRM_6_RATE"]);
                            $totalCost = round($timeInHours * $rate[0], 2);
                            // echo '<pre>'; print_r($report["AGREED_TIME"]); echo '</pre>';
                            
                            $rate[1] = $rate[1] == "USD" ? "$" : ( $rate[1] == "EUR" ? "€" : $rate[1]);
                            $docField = $rate; 
                            $dataField = $totalCost; 
                            $rowField = $totalCost . "" . $rate[1];
                        }else{
                            $dataField = $docField = $rowField = '';                            
                        }
                        break;
                    case 'RESPONSIBLE_RATE':
                        if($thisRate){
                            $rate = explode("|",$thisRate[0]["UF_CRM_6_RATE"]);
                            // echo '<pre>'; print_r($rate); echo '</pre>';
                            $rate[1] = $rate[1] == "USD" ? "$" : ( $rate[1] == "EUR" ? "€" : $rate[1]);
                            
                            $docField = $rate; 
                            $dataField = $rate; 
                            $rowField = $rate[0] . "" . $rate[1];                         
                        }else{
                            $dataField = $docField = $rowField = null;                            
                        }
                        break;
                    default:
                        $rowField = $report[$column["id"]];
                    break;
                };
                
                $row["columns"][$column["id"]] = $rowField; 
                $row["data"][$column["id"]] = $dataField; 
                $row["docs"][$column["id"]] = $docField; 
                $row["data"]["EMPLOYEE_ID"] = $currentUserId;
                $row["data"]["STATUS"] = 0;

            }

            $rows[] = $row;
        }

        $this->arResult["AGREED_TIME_SUM"] = [
            "SECUNDS" => $agreedtimeSum,
            "CONVERTED" => gmdate("H:i", $agreedtimeSum)
        ];
        return $rows;
    }

    public function getRowDataById( $id )
    {
        return array_filter($this->arResult["ROWS"], function($element) use ($idToFind) {
            return isset($element['id']) && $element['id'] == $idToFind;
        });
    }
    
    private function filter()
    {
        $columns = [];

        foreach ( $this->arResult["COLUMNS"] as $key => $select) {
            switch ($select["id"]) {
                case "TAGS":
                case "CREATED_DATE":
                case "ELAPSED_TIME":
                case "AGREED_TIME":
                case "CREATED_DATE":
                case "ACCOMPLICES":
                break;
                case 'STATUS':
                    $columns[] = array(
                        'id' => "STATUS",
                        'name' => "Статус",
                        'type' => "list",
                        'items' => [
                            "" => "Не указан",
                            "0" => "Ожидает подтверждения",
                            "1" => "Подтвержден",
                            "2" => "Не подлежит подтверждению",
                        ]
                    );
                break;
                case 'UF_BILLABLE':
                    $enumfields = self::getEnumUserFieldValue("TASKS_TASK", "UF_BILLABLE");
                    $columns[] = array(
                        'id' => "UF_BILLABLE",
                        'name' => "Тип задачи",
                        'items' => [
                            $enumfields[0]["ID"] => $enumfields[0]["VALUE"],
                            $enumfields[1]["ID"] => $enumfields[1]["VALUE"],
                        ],
                        'type' => "list"
                    );
                    break;
                break;
                case "GROUP_ID":
                    $columns[] = array(
                        'id' => "GROUP_ID",
                        'name' => "Проект",
                        'type' => "dest_selector",
                        'params' => [
                            'context' => 'TASKS_PROJECTS',
                            'multiple' => 'N',
                            'enableUsers' => 'N',
                            'enableSonetgroups' => 'Y',
                            'departmentSelectDisable' => 'Y',
                            'enableAll' => 'N',
                            'enableDepartments' => 'N',
                            'enableCrm' => 'N',
                        ],
                        'items' => [
                            'groups' => []
                        ],
                        'paramsGroup' => [
                            'context' => 'TASKS_PROJECTS',
                            'multiple' => 'N',
                            'enableUsers' => 'N',
                            'enableSonetgroups' => 'Y',
                            'departmentSelectDisable' => 'Y',
                            'enableAll' => 'N',
                            'enableDepartments' => 'N',
                            'enableCrm' => 'N',
                        ]
                    );
                break;
                case "RESPONSIBLE_ID":
                    $columns[] = array(
                        'id' => $select["id"],
                        'name' => "Ответственный",
                        'type' => "dest_selector",
                        'params' => [
                            'context' => $select["id"],
                            'multiple' => 'N',
                            'enableUsers' => 'y',
                            'enableSonetgroups' => 'N',
                            'departmentSelectDisable' => 'N',
                            'enableAll' => 'N',
                            'enableDepartments' => 'N',
                            'enableCrm' => 'N',
                        ],
                        'items' => [
                            'groups' => []
                        ],
                        'paramsGroup' => [
                            'context' => $select["id"],
                            'multiple' => 'N',
                            'enableUsers' => 'Y',
                            'enableSonetgroups' => 'N',
                            'departmentSelectDisable' => 'N',
                            'enableAll' => 'N',
                            'enableDepartments' => 'N',
                            'enableCrm' => 'N',
                        ]
                    );
                break;
                /* case "ACCOMPLICES":
                    $columns[] = array(
                        'id' => $select["id"],
                        'name' => "Соисполнитель",
                        'type' => "dest_selector",
                        'params' => [
                            'context' => $select["id"],
                            'multiple' => 'Y',
                            'enableUsers' => 'y',
                            'enableSonetgroups' => 'N',
                            'departmentSelectDisable' => 'N',
                            'enableAll' => 'N',
                            'enableDepartments' => 'N',
                            'enableCrm' => 'N',
                        ],
                        'items' => [
                            'groups' => []
                        ],
                        'paramsGroup' => [
                            'context' => $select["id"],
                            'multiple' => 'Y',
                            'enableUsers' => 'Y',
                            'enableSonetgroups' => 'N',
                            'departmentSelectDisable' => 'N',
                            'enableAll' => 'N',
                            'enableDepartments' => 'N',
                            'enableCrm' => 'N',
                        ]
                    );
                break; */
                default:
            }
        }

        $columns[] = array(
            "id" => "SELECTED_DATE",
            "name" => "Дата создания отчета",
            "type" => "date",
            "exclude" => array(
                DateType::TOMORROW,
                DateType::YESTERDAY,
                DateType::CURRENT_DAY,
                DateType::CURRENT_QUARTER,
                DateType::LAST_7_DAYS,
                DateType::LAST_30_DAYS,
                DateType::LAST_60_DAYS,
                DateType::LAST_90_DAYS,
                DateType::PREV_DAYS,
                DateType::NEXT_DAYS,
                DateType::MONTH,
                DateType::QUARTER,
                DateType::YEAR,
                DateType::EXACT,
                DateType::NEXT_WEEK,
                DateType::NEXT_MONTH,
            )
        );


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
        $tasksFilter = [];

        if(isset($gridFilter["SELECTED_DATE_from"]) ){
            $date = new DateTime($gridFilter["SELECTED_DATE_from"]);
            $filter[">=START_DATE"] = $date->setTime(0, 0, 0);
        }
        if(isset($gridFilter["SELECTED_DATE_to"]) ){
            $date = new DateTime($gridFilter["SELECTED_DATE_to"]);
            $filter["<=END_DATE"] = $date->setTime(0, 0, 0);
        }
        if(isset($gridFilter["STATUS"]) ){
            $filter["STATUS"] = $gridFilter["STATUS"];
        }
        
        if(isset($gridFilter["RESPONSIBLE_ID"]) ){
            preg_match('/\d+/', $gridFilter["RESPONSIBLE_ID"], $matches);
            $number = isset($matches[0]) ? (int)$matches[0] : null;

            $tasksFilter["EMPLOYEE_ID"] = $number;
            $filter["EMPLOYEE_ID"] = $number;
        }

        if(isset($gridFilter["GROUP_ID"]) ){
            preg_match('/\d+/', $gridFilter["GROUP_ID"], $matches);
            $number = isset($matches[0]) ? (int)$matches[0] : null;

            $tasksFilter["GROUP_ID"] = $number;
        }

        if(isset($gridFilter["UF_BILLABLE"]) ){
            $tasksFilter["UF_BILLABLE"] = $gridFilter["UF_BILLABLE"];
        }

        if(!empty($tasksFilter))
        {
            /* Так как я гений не создал ячейку PROJECT_ID в таблице b_tasks_reports, придется костылить */
            /* А может это и к лучшему */
            $res = CTasks::GetList(
                Array(),
                $tasksFilter,
                Array("ID", "GROUP_ID", "RESPONSIBLE_ID")
            );
            if($res->SelectedRowsCount()>0){
                while ($arTask = $res->fetch()) {
                    $filter["TASK_ID"][] = $arTask["ID"];
                }
            }else{
                /* в том случае, если по параметру нет задач, то нужно просто не выводить репопрты */
                $filter["HIDE_ALL"] = true;
            }
        
        }

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

    public function getRatesItems ($filter = [], $sort = [], $order = [])
    {
        return $this->ratesSmartItemsFactory->getItems(
            array(
                "filter" => $filter,
                "order" => $order,
                "limit" => $limit,
                "offset" => $offset,
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

    /**
    * получить значение выбранного пользовательского поля типа список по коду поля и сущности
    * @param $entityId
    * @param $fieldCode
    *
    * @return string
    */
    public static function getEnumUserFieldValue($entityId, $fieldCode)
    {

        $arFieldRes = \CUserTypeEntity::GetList(
            ['ID' => 'ASC'],
            ['ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldCode, 'USER_TYPE_ID' => 'enumeration']
        );
        
        if ($arField = $arFieldRes->Fetch()) {
            
            $enum = new \CUserFieldEnum();
            $enumRes = $enum->GetList(array(), array('USER_FIELD_ID' => $arField['ID']));
            $enumFields = [];
            
            while ($enumEl = $enumRes->Fetch()) 
                $enumFields[] = $enumEl;
            return $enumFields;
        }
    }

    /**
    * получить значение выбранного пользовательского поля типа список по коду поля и сущности
    * @param $entityId
    * @param $fieldCode
    *
    * @return string
    */
    public static function getEnumUserFieldValueById($entityId, $fieldCode, $id)
    {
        // Получаем информацию о пользовательском поле
        $userField = \CUserTypeEntity::GetList([], ["ENTITY_ID" => $entityId, "FIELD_NAME" => $fieldCode])->Fetch();
    
        if (!$userField) {
            return null; // Пользовательское поле не найдено
        }
    
        // Получаем значения перечисляемого поля
        $enumList = \CUserFieldEnum::GetList(["SORT" => "ASC"], ["USER_FIELD_ID" => $userField['ID']]);
    
        // Ищем значение по ID
        while ($enum = $enumList->Fetch()) {
            if ($enum['ID'] == $id) {
                return $enum['VALUE']; // Возвращаем значение, если ID совпадает
            }
        }
    
        return null;
    }

    public static function getEnumUserFieldList($entityId, $fieldCode)
    {
        // Получаем информацию о пользовательском поле
        $userField = \CUserTypeEntity::GetList([], ["ENTITY_ID" => $entityId, "FIELD_NAME" => $fieldCode])->Fetch();

        if (!$userField) {
            return []; // Пользовательское поле не найдено
        }

        // Получаем значения перечисляемого поля
        $enumList = \CUserFieldEnum::GetList(["SORT" => "ASC"], ["USER_FIELD_ID" => $userField['ID']]);

        $result = [];
        while ($enum = $enumList->Fetch()) {
            $result[$enum['ID']] = $enum['VALUE']; // Добавляем значение в результат
            // $result[] = [
            //     'ID' => $enum['ID'],
            //     'VALUE' => 
            // ]; // Добавляем значение в результат
        }
        return $result; // Возвращаем массив значений
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
                
                $html = "<a task-id=\"$arData[ID]\" href=\"/company/personal/user/$currUserId/tasks/task/view/$arData[ID]/?ta_sec=tasks&amp;ta_sub=list&amp;ta_el=title_click\" class=\"task-title task-status-text-color-in-progress\">";
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
