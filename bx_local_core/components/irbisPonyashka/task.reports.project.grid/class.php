<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Service;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Router;

use Bitrix\Main\Loader;
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
        
        $this->arUserId = $this->arUser->getId();
        
        $this->arResult = [];

        $this->tableMap = TasksReportTable::getMap();
        $this->tableMap["EXPENSES"] = [
            'data_type' => 'string',
            'title' => 'Затраты'
        ];
        $this->snippet = new Snippet();

        $filter = [];
        $filterOption = new Bitrix\Main\UI\Filter\Options('reports_project_grid');
        $filterData = $filterOption->getFilter([]);
        
        foreach ($filterData as $k => $v)
        {
            $filter[$k] = $v;
        };
        
        $reportsFilter = array();
        if($filter)
        {
            $reportsFilter = $this->getFilterParams($filter); 
        }

        $this->gridOption = new GridOptions('reports_project_grid');

        $gridSortOptions = $this->gridOption->GetSorting();
        $nav_params = $this->gridOption->GetNavParams();

        $this->arResult["NAV"] = new Bitrix\Main\UI\PageNavigation('reports_project_grid');
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

        $this->arResult["GROUP_ACTIONS"] = [
            'GROUPS' => [ 
                [ 
                    'ITEMS' => [ 
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

        // echo '<pre>'; print_r($this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"]); echo '</pre>';

        $this->arResult["LIST"] = $arReports;
        $this->arParams["GRID_ID"] = "reports_project_grid";
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
            // ['id' => 'TASK', 'name' => 'Задача','type' => 'text', 'sort' => 'TASK' ],
            // ['id' => 'TAGS', 'name' => 'Теги', 'default' => true ],
            // ['id' => 'STATUS', 'name' => 'Статус', 'default' => true ],
            // ['id' => 'UF_BILLABLE', 'name' => 'Тип задачи', 'default' => true ],
            // ['id' => 'RATE', 'name' => 'Цена', 'default' => true ],
            ['id' => 'PROJECT_ID', 'name' => 'Проект', 'default' => true ],
            // ['id' => 'RESPONSIBLE_ID', 'name' => 'Ответственный', 'default' => true ],
            // ['id' => 'ACCOMPLICES', 'name' => 'Соисполнитель', 'default' => true ],
            // ['id' => 'AGREED_TIME', 'name' => 'Внесенное сотрудником время', 'default' => true, 'sort' => 'AGREED_TIME' ],
            ['id' => 'MAIN_AGREED_TIME', 'name' => 'Общее количество часов', 'default' => true, 'sort' => 'MAIN_AGREED_TIME',  'editable' => true ],
            // ['id' => 'CREATED_DATE', 'name' => 'Дата создания', 'default' => true ],
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
        $this->arUserId = $this->arUser->getId();
        
        if( count($reports) < 1 ){
            return [];
        }
        
        // Группировка массива 
        self::grouppingReportsByProject ( $reports );
        // Преобразование группированного массива в индексированный массив
        $reports = array_values($reports);

        $agreedtimeSum = 0;
        foreach ($reports as $key => $report)
        {

            $row = [];
            $data = [];

            $row["id"] = $report["ID"];

            foreach ($this->arResult["COLUMNS"] as $columnKey => $column)
            {
                $dataField;
                $rowField;

                switch ($column["id"]) {
                   
                    case 'PROJECT_ID':
                        if($report["GROUP_ID"]){
                            $project = $this->getProject( $report["GROUP_ID"] );
                            
                            $docField = $project["NAME"]; 
                            $dataField = $report["GROUP_ID"]; 
                            $rowField = $this->getEntityHtml ( $project, "project" );
                        }else{
                            $docField = $rowField = '';
                        }
                        
                        break;
                    case 'AGREED_TIME':
                        $rAgreedTimeList = AgreedTimeTable::getList([
                            "filter" => [
                                "TASK_ID" => $report["TASK_DATA"]["ID"],
                                "USER_ID" => $report["EMPLOYEE_ID"]
                            ]
                        ]);
                        $agreedTimeSum = 0; // sec
                        while ( $agreedTimeItem = $rAgreedTimeList->fetch()) {
                            echo '<pre>'; print_r($agreedTimeItem); echo '</pre>';
                            $agreedTimeSum += $agreedTimeItem["SECONDS"];
                        }
                        $dataField = $agreedTimeSum;
                        $docField = $rowField = self::formatTime((int)$agreedTimeSum);
                        break;
                    case 'MAIN_AGREED_TIME':
                        $agreedtimeSum += $report["MAIN_AGREED_TIME"];
                        $dataField = $report["MAIN_AGREED_TIME"] ?? 0;
                        $docField = $rowField = gmdate("H:i", $report["MAIN_AGREED_TIME"] ?? 0);
                        break;
                };

                $httpBuildQuery = http_build_query([
                    "GROUP_ID" => "SG".$report['GROUP_ID'],
                    "UF_CRM_5_PROJECT_LINK" => "SG".$report['GROUP_ID'],
                ]);
                // echo '<pre>'; print_r($report); echo '</pre>';

                $row['actions'] = array(
                    array(  
                        'text'    => 'Детальный просмотр',
                        'onclick' => "BX.SidePanel.Instance.open('detail/index.php?$httpBuildQuery', { requestMethod: 'post',  requestParams: { apply_filter: 'Y', filter_id: 'reports_general_grid' }, cacheable: false })"
                    )
                );
                
                $row["columns"][$column["id"]] = $rowField; 
                $row["data"][$column["id"]] = $dataField; 
                $row["docs"][$column["id"]] = $docField; 
                $row["data"]["EMPLOYEE_ID"] = $this->arUserId;
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

    
    private function filter()
    {
        $columns = [];

        foreach ( $this->arResult["COLUMNS"] as $key => $select) {
            switch ($select["id"]) {
                case "PROJECT_ID":
                    $columns[] = array(
                        'id' => "PROJECT_ID",
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
            }
        }

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

        if(isset($gridFilter["PROJECT_ID"]) ){
            preg_match('/\d+/', $gridFilter["PROJECT_ID"], $matches);
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
    
    public function grouppingReportsByProject ( &$reports )
    {

        $groupedReports = [];
        $agreedTimeSum = 0; // sec
        foreach ($reports as $report) {
            $cTaskItem = new CTaskItem($report["TASK_ID"], $this->arUserId );
            if(empty($cTaskItem["GROUP_ID"])) continue ;
            try {
                $report["TASK_DATA"] = $cTaskItem->getData();
                $report["GROUP_ID"] = $cTaskItem["GROUP_ID"];
            } catch (TasksException $e) {
                continue;
            }
            // echo '<pre>'; print_r($report); echo '</pre>';

            // Создаем ключ для группировки
            $key = $report["GROUP_ID"];

            // Добавляем элемент в группированный массив
            if (!isset($groupedReports[$key])) {
                $groupedReports[$key] = $report;
            };

            // Добавляем задачу в текущую группу
            $groupedReports[$key]["MAIN_AGREED_TIME"] += $report['AGREED_TIME'];
            // $groupedReports[$key][""] = $report['AGREED_TIME'];
            // $rAgreedTimeList = AgreedTimeTable::getList([
            //     "filter" => [
            //         "TASK_ID" => $report["TASK_DATA"]["ID"],
            //         "USER_ID" => $report["EMPLOYEE_ID"]
            //     ]
            // ]);
            // while ( $agreedTimeItem = $rAgreedTimeList->fetch())
            // {
            //     $agreedTimeSum += $agreedTimeItem["SECONDS"];
            // }
            // $groupedReports[$key]["AGREED_TIME"] = $agreedTimeSum;
            // $dataField = $agreedTimeSum;
            // $docField = $rowField = self::formatTime((int)$agreedTimeSum);

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
    
    public function getVisibleColumns ()
    {
        return $this->gridOption->GetVisibleColumns();
    }


}
