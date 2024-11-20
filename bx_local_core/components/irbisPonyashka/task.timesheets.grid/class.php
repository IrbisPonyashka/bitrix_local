<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Service;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Localization\Loc;

// use Bitrix\Main\Grid\Column\Type;

use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UI\Filter\Options;

use Bitrix\Main\Grid\Panel\Snippet;

use Bitrix\Main\Grid\Cell\Label\Color;
use Bitrix\Socialnetwork\WorkgroupTable;
use Micros\Tasks\Reports\TasksReportTable;

use Bitrix\Tasks\Internals\Task\MemberTable;

use Bitrix\Main\Grid\Options as GridOptions;

use Micros\Tasks\Internals\Task\AgreedTimeTable;
use Bitrix\Main\Grid\Cell\Label\RemoveButtonType;



if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

class tasksReportsGrid extends CBitrixComponent
{

    public function executeComponent()
    {
        global $USER;
        
        $this->arUser = $USER;
        
        $this->arResult = [];

        $this->gridTasksSelect = array(
            "TITLE",
            "STATUS",
            "TAG",
            "GROUP_ID",
            "UF_BILLABLE",
            "RESPONSIBLE_ID",
            "ACCOMPLICE",
            "ELAPSED_TIME",
            "AGREED_TIME",
            "CREATED_DATE",
        );
        
        $this->snippet = new Snippet();

        /* Get filter by gridOptions */
        $filter = [];
        $filterOption = new Bitrix\Main\UI\Filter\Options('timesheets_grid');
        $filterData = $filterOption->getFilter([]);
        foreach ($filterData as $k => $v) {
            $filter[$k] = $v;
        };
        /* Prepare filter to Tasks::Gelist */
        $tasksFilter = array();
        if($filter){
            $tasksFilter = $this->getFilterParams($filter); 
        }

        /* Grid navigation and sort params */
        $gridOption = new GridOptions('timesheets_grid');
        $gridSortOptions = $gridOption->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
        $nav_params = $gridOption->GetNavParams();

        $this->arResult["NAV"] = new Bitrix\Main\UI\PageNavigation('timesheets_grid');

        $this->arResult["NAV"]
            ->allowAllRecords(true)
            ->setPageSize($nav_params['nPageSize'])
            ->initFromUri();

        $sortBy = array_keys($gridSortOptions["sort"])[0];
        $sortAs = strtoupper($gridSortOptions["sort"][$sortBy]);

        /* Prepare sort params to Tasks::Gelist */
        $tasksOrder = [];
        if(isset($sortBy) && isset($sortAs)){
            $tasksOrder = $this->getOrderParams($sortBy, $sortAs); 
        }

        if( !$this->arUser->isAdmin() )
        {

            $observerTasks = [];
            $observerRes = MemberTable::getList([
                'select' => ['TASK_ID'],
                'filter' => [
                    'USER_ID' => $this->arUser->getId(),
                    'TYPE' => 'U' // U означает наблюдатель
                ]
            ]);
            
            while ($observerTask = $observerRes->fetch()) {
                $tasksFilter["!ID"][] = $observerTask['TASK_ID'];
            }
        }

        
		$this->arResult['NAV_PARAM_NAME'] = $this->arResult["NAV"]->getId();
		$this->arResult['CURRENT_PAGE'] = $this->arResult["NAV"]->getCurrentPage();
        
        $offset = $this->arResult["NAV"]->getOffset();
        $limit = $this->arResult["NAV"]->getLimit();

        $this->arTasks = array();
        $taskRes = CTasks::GetList(
            $tasksOrder, // arOrder
            $tasksFilter, // arFilter
            array("*", "UF_*"), // arSelect
            array(
                "NAV_PARAMS" => [
                    "iNumPage" => $this->arResult["NAV"]->getCurrentPage(), // Текущая страница
                    "nPageSize" => $this->arResult["NAV"]->getPageSize(), // Количество элементов на странице
                ]
            ),
        );
        $taskRes->NavStart(30); 
        while ($arTask = $taskRes->GetNext()){
            $this->arTasks[] = $arTask;
        }
        /* Костыль */
        $this->arTasksFetch = array();
        $taskAllRes = CTasks::GetList([],$tasksFilter,["ID"]);
        while ($arTask = $taskAllRes->GetNext()){
            $this->arTasksFetch[] = $arTask;
        }
        /* Костыль end */
        $totalCount = count($this->arTasksFetch);

        $this->arResult["NAV"]->setRecordCount($totalCount);
        $this->arResult["TOTAL_ROWS_COUNT"] = $totalCount;

        $this->arResult["FILTER"] = self::filter();

        $this->arResult["COLUMNS"] = self::columns();
        
        $this->arResult["ROWS"] = self::rows($this->arTasks);
        
        $this->arResult["GROUP_ACTIONS"] = [
            'GROUPS' => [ 
                [ 
                    'ITEMS' => [ 
                        $this->snippet->getEditButton(),
                        [ 
                            'ID' => 'send_to_approval', 
                            'TYPE'  => 'BUTTON', 
                            'TEXT' => 'Отправить на согласование',
                            'CLASS' => 'main-grid-action-button', 
                            'ONCHANGE' => [
                                [
                                    'ACTION' => 'CALLBACK',
                                    'DATA' => [
                                        ['JS' => "sendToApproval(".json_encode($this->arResult["ROWS"]).")"]
                                    ]
                                ]
                            ]
                        ],
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

        $this->arResult["LIST"] = $this->arTasks;
        $this->arParams["GRID_ID"] = "timesheets_grid";
        $this->arResult["USER_ID"] = $this->arUser->GetId();

        $this->includeComponentTemplate();
        return $this->arResult;
    }

    public static function params()
    {

        $params = [
            "GRID_ID" => "timesheets_grid",
            "USER_ID" => $this->arUser->GetId()
        ];
        
        return $columns;
    }

    public static function columns()
    {

        $columns = [
            ['id' => 'TASK', 'name' => 'Задача','type' => 'text', 'sort' => 'TASK' ],
            ['id' => 'TAGS', 'name' => 'Теги', 'default' => true, 'type' => 'tags' ],
            ['id' => 'STATUS', 'name' => 'Статус', 'default' => true ],
            ['id' => 'UF_BILLABLE', 'name' => 'Тип задачи','sort' => 'UF_BILLABLE', 'default' => true ],
            ['id' => 'GROUP_ID', 'name' => 'Проект', 'sort' => 'GROUP_ID', 'default' => true ],
            ['id' => 'RESPONSIBLE_ID', 'name' => 'Ответственный', 'default' => true ],
            ['id' => 'ACCOMPLICES', 'name' => 'Соисполнитель', 'default' => true ],
            ['id' => 'ELAPSED_TIME', 'name' => 'Затраченное время', 'default' => true ],
            ['id' => 'AGREED_TIME', 'name' => 'Внесенное сотрудником время', 'default' => true,  'editable' => true ],
            ['id' => 'MAIN_AGREED_TIME', 'name' => 'Согласованное руководителем время', 'default' => true ],
            ['id' => 'CREATED_DATE', 'name' => 'Дата создания', 'sort' => 'CREATED_DATE', 'default' => true ],
        ];
        
        return $columns;
    }

    /**
     * @var Array $tasks
     * @return Array
    */
    public function rows($tasks)
    {
        $rows = [];
        $currentUserId = $this->arUser->getId();

        if( count($tasks) < 1 ){
            return [];
        }

        foreach ($tasks as $taskKey => $arTask)
        {
            $row = [];
            $data = [];
            $row["id"] = $arTask["ID"];
            $cTask = new CTaskItem($arTask["ID"],$currentUserId );
            foreach ($this->arResult["COLUMNS"] as $columnKey => $column)
            {
                $dataField;
                $rowField;

                switch ($column["id"]) {
                    case 'TASK':
                        $dataField = $arTask["ID"]; 
                        $rowField = $this->getEntityHtml ( $arTask, "task" );
                        
                        break;
                    case 'UF_BILLABLE':
                        $dataField = $cTask["UF_BILLABLE"]; 
                        $rowField = $cTask["UF_BILLABLE"] == "36" ? "Подлежит к оплате" : "Не подлежит к оплате" ;

                        break;
                    case 'TAGS':
                        $arTags = $cTask->getTags();

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
                        $rowField = $dataField;
                        break;
                    case 'GROUP_ID':
                        if($arTask["GROUP_ID"]){
                            
                            $dataField = $arTask["GROUP_ID"]; 
                            $project = $this->getProject( $arTask["GROUP_ID"] );
                            $rowField = $this->getEntityHtml ( $project, "project" );
                        }else{
                            $rowField = '';
                        }
                        
                        break;
                    case 'RESPONSIBLE_ID':
                        if($arTask["RESPONSIBLE_ID"]){

                            $dataField = $arTask["RESPONSIBLE_ID"]; 
                            $responsible = $this->getResponsible( $arTask["RESPONSIBLE_ID"] );
                            $rowField = $this->getEntityHtml ( $responsible, "user" );
                        }else{
                            $rowField = '';
                        }
                        
                        break;
                    case 'ACCOMPLICES':
                        if($cTask["ACCOMPLICES"]){
                            $arAccomplcs = [];
                            foreach ($cTask["ACCOMPLICES"] as $key => $accomplcs) {
                                $arAccomplcs[] = $this->getResponsible( $accomplcs );
                            }
                            $dataField = $arTask["ACCOMPLICES"]; 
                            $rowField = $this->getEntityHtml ( $arAccomplcs, "accomplcs" );
                        }else{
                            $rowField = '';
                        }
                        break;
                    case 'ELAPSED_TIME':
                        $dataField = $arTask["TIME_SPENT_IN_LOGS"] ?? 0;
                        $rowField = gmdate("H:i", $arTask["TIME_SPENT_IN_LOGS"] ?? 0);
                        break;
                    case 'AGREED_TIME':
                        $rAgreedTimeList = AgreedTimeTable::getList([
                            "filter" => [
                                "TASK_ID" => $arTask["ID"],
                                "USER_ID" => $this->arUser->GetId()
                            ]
                        ]);
                        $agreedTimeSum = 0; // sec
                        while ( $agreedTimeItem = $rAgreedTimeList->fetch()) {
                            $agreedTimeSum += $agreedTimeItem["SECONDS"];
                        }
                        $dataField = $agreedTimeSum;
                        $rowField = self::formatTime((int)$agreedTimeSum);
                        break;
                    case 'MAIN_AGREED_TIME':
                        $rReportList = TasksReportTable::getList([
                            "filter" => [
                                "TASK_ID" => $arTask["ID"],
                                "EMPLOYEE_ID" => $currentUserId
                            ]
                        ]);
                        // echo '<pre>'; print_r($rReportList->fetchAll()); echo '</pre>';
                        if(!empty($reportsFetch = $rReportList->fetchAll()))
                        {
                            $reportsFetch = $reportsFetch[0];
                            $dataField = $reportsFetch["AGREED_TIME"];
                            $rowField = self::formatTime((int)$reportsFetch["AGREED_TIME"]);
                        }else{
                            $dataField = 0;
                            $rowField = self::formatTime((int)0);
                        }
                        break;
                    case 'STATUS':
                        $rReportList = TasksReportTable::getList([
                            "filter" => [
                                "TASK_ID" => $arTask["ID"],
                                "EMPLOYEE_ID" => $currentUserId
                            ]
                        ]);
                        // echo '<pre>'; print_r($rReportList); echo '</pre>';
                        if(!empty($reportsFetch = $rReportList->fetchAll()))
                        {
                            $reportsFetch = $reportsFetch[0];
                            $dataField = $reportsFetch["STATUS"];
                            switch ($reportsFetch["STATUS"]){
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
                            // $reportsFetch["STATUS"] == 0 ? "Ожидает подтверждения" : ( $reportsFetch["STATUS"] == 1 ? "Подтвержден" : "Не принят") ;
                        }else{
                            $dataField = null;
                            $rowField = "Не отправлен" ;
                        }
                        break;
                    case 'CREATED_DATE':
                        $date = DateTime::createFromFormat('d.m.Y H:i:s', $arTask[$column["id"]]);
                        setlocale(LC_TIME, 'ru_RU.UTF-8');
                        $formattedDate = strftime('%a %d %B', $date->getTimestamp());
                        
                        $dataField = $arTask[$column["id"]];
                        $rowField = ucfirst($formattedDate);
                        
                        break;
                    default:
                        $dataField = $rowField = $arTask[$column["id"]];
                    break;
                };

                $row["columns"][$column["id"]] = $rowField; 
                $row["data"][$column["id"]] = $dataField; 
                $row["data"]["EMPLOYEE_ID"] = $currentUserId;
                $row["data"]["STATUS"] = 0;

            }
            echo '<pre style="display:none; color: white;">'; print_r($row); echo '</pre>';
            
            $rows[] = $row;

        }
        
        return $rows;
    }

    
    private function filter()
    {
        $columns = [];

        foreach ( $this->gridTasksSelect as $key => $select) {
            switch ($select) {
                /* case "TITLE":
                    $columns[] = array(
                        'id' => "LINK",
                        'name' => "Название задачи"
                    );
                    break; */
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
                case "ACCOMPLICE":
                    $name = $select == "RESPONSIBLE_ID" ? "Ответственный" : "Соисполнитель";
                    $columns[] = array(
                        'id' => $select,
                        'name' => $name,
                        'type' => "dest_selector",
                        'params' => [
                            'context' => $select,
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
                            'context' => $select,
                            'multiple' => 'Y',
                            'enableUsers' => 'Y',
                            'enableSonetgroups' => 'N',
                            'departmentSelectDisable' => 'N',
                            'enableAll' => 'N',
                            'enableDepartments' => 'N',
                            'enableCrm' => 'N',
                        ]
                    );
                    break;
                case "TAG":
                    global $DB;
                    $results = $DB->Query("SELECT * FROM b_tasks_label ORDER BY ID ASC");
                    $items = array();
                    while ($row = $results->Fetch()){
                        $items[$row["NAME"]] = $row["NAME"]; 
                    }
                    $columns[] = array(
                        'id' => "TAG",
                        'name' => "Теги",
                        'type' => "list",
                        'items' => $items,
                        'params' => ['multiple' => 'Y']
                    );
                    break;
                case "CREATED_DATE":
                    $columns[] = array(
                        'id' => "CREATED_DATE",
                        'name' => "Дата создания",
                        'type' => "date"
                    );
                    break;
                case "UF_BILLABLE":
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
                case "STATUS":
                    $columns[] = array(
                        'id' => "STATUS",
                        'name' => "Статус",
                        'type' => "list",
                        'items' => [
                            "not_send" => "Не отправлен",
                            "0" => "Ожидает подтверждения",
                            "1" => "Согласован",
                        ]
                    );
                    break;
                default:
            }
        }
        return $columns;
    }

    
    public function getOrderParams( $sortBy, $sortAs )
    {
        switch($sortBy){
            case 'TASK':
                return [ "TITLE" => $sortAs ];
            break;
            case 'GROUP_ID':
                return [ "GROUP_ID" => $sortAs ];
            break;
            default:
                return [ $sortBy => $sortAs ];
            break;
        }

    }

    public function getFilterParams( $gridFilter )
    {
        $filter = [];

        if($gridFilter["GROUP_ID"]){

            preg_match('/\d+/', $gridFilter["GROUP_ID"], $matches);
            $number = isset($matches[0]) ? (int)$matches[0] : null;
            
            $filter["GROUP_ID"] = $number;
        }
        if($gridFilter["RESPONSIBLE_ID"]){
            // echo '<pre style="disaply:none">'; print_r($gridFilter["RESPONSIBLE_ID"]); echo '</pre>';
            // preg_match('/\d+/', $gridFilter["RESPONSIBLE_ID"][0], $matches);
            // $number = isset($matches[0]) ? (int)$matches[0] : null;

            // $filter["RESPONSIBLE_ID"] = $number;
            foreach ($gridFilter["RESPONSIBLE_ID"] as $key => $value) {
                preg_match('/\d+/', $value, $matches);
                $number = isset($matches[0]) ? (int)$matches[0] : null;
                $filter["RESPONSIBLE_ID"][] = $number;
            }
        }
        if($gridFilter["ACCOMPLICE"]){
            foreach ($gridFilter["ACCOMPLICE"] as $key => $value) {
                preg_match('/\d+/', $value, $matches);
                $number = isset($matches[0]) ? (int)$matches[0] : null;
                $filter["ACCOMPLICE"][] = $number;
            }
        }
        if($gridFilter["TAG"]){
            $tags = [];
            foreach ($gridFilter["TAG"] as $key => $tagsId) {
                $tags[] = $tagsId;
            }
            $filter["TAG"] = $tags;
        }
        if($gridFilter["CREATED_DATE_datesel"]){

            $filter[">=CREATED_DATE"] = $gridFilter["CREATED_DATE_from"];
            $filter["<=CREATED_DATE"] = $gridFilter["CREATED_DATE_to"];
        }
        if($gridFilter["UF_BILLABLE"]){
            $filter["UF_BILLABLE"] = $gridFilter["UF_BILLABLE"];
        }
        if( isset($gridFilter["STATUS"]) || (isset($gridFilter["STATUS"]) && $gridFilter["STATUS"] == 0)){
            $taskReportFilter = [];
            $gridFilter["STATUS"] != "not_send" ? $taskReportFilter["STATUS"] = $gridFilter["STATUS"] : null; 
            
            /* tasksReportsList */
            $taskReportListRes = TasksReportTable::getList([
                "filter" => $taskReportFilter,
                "select" => [ "ID", "TASK_ID", "STATUS"]
            ]);
            while ($taskReport = $taskReportListRes->Fetch())
            {
                $gridFilter["STATUS"] == "not_send" ? $filter["!ID"][] = $taskReport["TASK_ID"] : $filter["=ID"][] = $taskReport["TASK_ID"];
            }

            if($gridFilter["STATUS"]!="not_send" && empty($filter))
            {
                $filter["=ID"] = 0;
            }
        }
        

        return $filter;
    }

    public function getElapsedTime ( $taskId )
    {
        // $result = CTaskElapsedTime::GetList(
        //     Array(), 
        //     Array("ID" => $taskId)
        // );
        // $elapsedTime = 0;
        // while ($arElapsed = $result->Fetch())
        // {
        // }

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

    public function getBXAjaxJsHml ( $data )
    {
        ?>
        <script>
            <?ob_start();?>
                // import {Type} from 'main.core';
                BX.ajax({
                    url: "/local/components/micros/task.timesheets.grid/ajax.php?action=add&type=reports", 
                    data: '<?=json_encode($data)?>', 
                    method: "POST",
                    dataType: "json", 
                    processData: false,
                    preparePost: false, 
                    onsuccess: function(data) {
                        // console.log(data);
                        const grid = BX.Main.grid = BX.Main.gridManager.getById('timesheets_grid')?.instance;
                        const gridRealtime = BX.Grid.Realtime = grid.getRealtime();
                        grid.editSelectedSave();
                        // gridRealtime.showStub({
                        //     content: {
                        //         title: 'Загрузка',
                        //     }
                        // });
                        // debugger;
                    },
                    onfailure: function(data) { 
                        const grid = BX.Main.grid = BX.Main.gridManager.getById('timesheets_grid')?.instance;
                        const gridRealtime = BX.Grid.Realtime = grid.getRealtime();
                        grid.editSelectedSave();
                    }
                });
            <? $buffer = ob_get_clean() ?>
        </script>
        <?
            return $buffer;

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
                    $html .= "<span class=\"main-grid-tag-add\"></span>\n";        
                $html .= "</div>";
                return $html;
            break;
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
                }else if($entity == "project"){
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
