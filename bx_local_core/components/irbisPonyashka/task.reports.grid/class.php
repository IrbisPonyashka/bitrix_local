<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm\Model\Dynamic\Type;

use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UI\Filter\DateType;
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

        $filter = [];
        $filterOption = new Bitrix\Main\UI\Filter\Options('reports_grid');
        $filterData = $filterOption->getFilter([]);
        foreach ($filterData as $k => $v) {
            $filter[$k] = $v;
        };
        
        $reportFilter = array();
        if($filter){
            $reportFilter = $this->getFilterParams($filter); 
        }

        $gridOption = new GridOptions('reports_grid');
        $gridSortOptions = $gridOption->GetSorting();
        $nav_params = $gridOption->GetNavParams();

        $this->arResult["NAV"] = new Bitrix\Main\UI\PageNavigation('reports_grid');
        
		$this->arResult['NAV_PARAM_NAME'] = $this->arResult["NAV"]->getId();
		$this->arResult['CURRENT_PAGE'] = $this->arResult["NAV"]->getCurrentPage();

        $this->arResult["NAV"]->allowAllRecords(true)
            ->allowAllRecords(true)
            ->setPageSize($nav_params['nPageSize'])
            ->initFromUri();

        $sortBy = array_keys($gridSortOptions["sort"])[0];
        $sortAs = strtoupper($gridSortOptions["sort"][$sortBy]);

        $tasksOrder = [];
        if($sortBy && $sortAs){
            $tasksOrder = $this->getOrderParams($sortBy, $sortAs); 
        }

        $container = Service\Container::getInstance();
        /* 190 - expenses */
        $this->expensesSmartItemsFactory = $container->getFactory( 190 );

        $arReports = array();
        $reports = TasksReportTable::GetList([
            "order" => $tasksOrder, // arOrder
            "filter" => $reportFilter, // arFilter
        ]);
        while ($arReport = $reports->fetch()){ $arReports[] = $arReport ;}

        $this->arResult["COLUMNS"] = self::columns();
        $this->arResult["FILTER"] = self::filter();

        $allRows = self::rows($arReports);

        $this->arResult["ROWS"] = $allRows;
        
        $totalCount = count($this->arResult["ROWS"]); // Или используйте другой способ подсчета
        
        $this->arResult["NAV"]->setRecordCount($totalCount);
        $this->arResult["TOTAL_ROWS_COUNT"] = $totalCount;

        // Определение начального и конечного индекса для текущей страницы
        $currentPage = $this->arResult["NAV"]->getCurrentPage();
        $pageSize = $this->arResult["NAV"]->getPageSize();
        $startIndex = ($currentPage - 1) * $pageSize;
        $endIndex = $startIndex + $pageSize;

        // Сегментация данных для текущей страницы
        $pagedRows = array_slice($allRows, $startIndex, $pageSize);

        // Установка данных для отображения
        $this->arResult["ROWS"] = $pagedRows;
        
        
        $this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"] = []; 
        
        if( $this->arUser->isAdmin() ){
            
            $this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"][] = [ 
                'ID' => 'send_to_approval', 
                'TYPE'  => 'BUTTON', 
                'TEXT' => 'Согласовать',
                'CLASS' => 'main-grid-action-button', 
                'ONCHANGE' => [
                    [
                        'ACTION' => 'CALLBACK',
                        'DATA' => [
                            ['JS' => "sendToApproval(".json_encode($this->arResult["ROWS"]).")"]
                        ]
                    ]
                ]
            ];
            
        }
        $this->arResult["GROUP_ACTIONS"]["GROUPS"][0]["ITEMS"][] = [ 
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
        ];


        $this->includeComponentTemplate();
        return $this->arResult;
    }

    public function columns()
    {

        $columns = [];
        foreach ($this->tableMap as $fieldKey => $field)
        {
            switch ($fieldKey) {
                case 'ID':
                case 'TASK_ID':
                    break;
                case 'EXPENSES':
                    $columns[] = [
                        'id' => "EXPENSES",
                        'name' => $field['title'],
                        'default' => true
                    ];
                    break;
                case 'START_DATE':
                case 'END_DATE':
                    $columns[] = [
                        'id' => $fieldKey,
                        'name' => $field['title'],
                        'default' => true
                    ];
                    break;
                case 'EMPLOYEE_ID':
                case 'STATUS':
                    $columns[] = [
                        'id' => $fieldKey,
                        'name' => $field['title'],
                        'sort' => $fieldKey,
                        'default' => true
                    ];
                    break;
                default:
                    $columns[] = [
                        'id' => $fieldKey,
                        'name' => $field['title'],
                        'sort' => $fieldKey,
                        'default' => true
                    ];
                    break;
            }
        }
        
        return $columns;
    }

    /**
     * @var Array $reports
     * @return Array
    */
    public function rows($reports)
    {
        $rows = [];
        
        if( count($reports) < 1 ){
            return [];
        }

        /**
         * Нужно собрать все данные в единое:
         * по дате
         * и по сотруднику 
        */

        // Группировка массива 
        self::grouppingReportsByEmployeeAndDate ( $reports );
        // Преобразование группированного массива в индексированный массив
        $reports = array_values($reports);

        foreach ($reports as $key => $report)
        {
            
            $row = [];
            $data = [];

            $row["id"] = $key;
            foreach ($this->arResult["COLUMNS"] as $columnKey => $column)
            {
                $rowField;
                $dataField;
                switch ($column["id"]) {
                    case "ELAPSED_TIME":
                        $dataField = $report[$column["id"]]; 
                        $rowField = self::formatTime((int)$report[$column["id"]]); 
                        break;
                    case "AGREED_TIME":
                        $dataField = $report[$column["id"]]; 
                        $rowField = self::formatTime((int)$report[$column["id"]]); 
                        break;
                    case "EMPLOYEE_ID":
                        if($report["EMPLOYEE_ID"]){
                            $responsible = $this->getResponsible( $report["EMPLOYEE_ID"] );
                            
                            $dataField = $report["EMPLOYEE_ID"]; 
                            $rowField = $this->getEntityHtml ( $responsible, "user" );
                        }else{
                            $rowField = '';
                        }

                        break;
                    case "START_DATE":
                    case "END_DATE":
                        $dataField = $rowField = $report[$column["id"]]->toUserTime()->format("d.m.Y");
                        break;
                    case "STATUS":
                        $dataField = $report[$column["id"]];
                        switch ($report["STATUS"]){
                            case 0:
                                $rowField = "Ожидает подтверждения";
                                break;
                            case 1:
                                $rowField = "Подтвержден";
                                break;
                            case 2:
                                $rowField = "Не подлежит согласованию";
                                break;
                            case 3:
                                $rowField = "Принят с исправлениями";
                                break;
                            default:
                        }
                        break;
                    case "EXPENSES":
                        $filter = [
                            "ASSIGNED_BY_ID" => $report["EMPLOYEE_ID"]
                        ];
                        $expenses = 0;
                        $expensesCurrency = 0;
                        $expensesItems = self::getExpensesItems($filter);
                        foreach ($expensesItems as $key => $expensesItem)
                        {
                            $expensesItemData = $expensesItem->getData();    
                            $expensesAr = $expensesItemData["UF_CRM_5_SUM"] ? explode('|', $expensesItemData["UF_CRM_5_SUM"]) : 0 ;
                            $expenses += $expensesAr[0];
                        }
                        $rowField = $expenses.' '.$expensesAr[1];
                        break;
                    default:
                        $rowField = $report[$column["id"]];
                    break;
                }
                $httpBuildQuery = http_build_query([
                    "STATUS" => $report['STATUS'],
                    "EMPLOYEE_ID" => $report['EMPLOYEE_ID'],
                    "START" => $report['START_DATE']->toString(),
                    "END" => $report['END_DATE']->toString(),
                ]);

                $row["columns"][$column["id"]] = $rowField; 
                $row["data"][$column["id"]] = $dataField; 

                $row['actions'] = array(
                    array(  
                        'text'    => 'Детальный просмотр',
                        'onclick' => "BX.SidePanel.Instance.open('detail/index.php?".$httpBuildQuery."', {
                        })"
                    )
                );
                
            }

            $rows[] = $row;
        }
    
        
        return $rows;
    }

    
    private function filter()
    {
        $columns = [];

        foreach ( $this->arResult["COLUMNS"] as $key => $select) {
            switch ($select["id"]) {
                case "START_DATE":
                case "END_DATE":
                case "AGREED_TIME":
                case "ELAPSED_TIME":
                case "EXPENSES":
                    break;
                case "EMPLOYEE_ID":
                    $columns[] = array(
                        'id' => "EMPLOYEE_ID",
                        'name' => "Сотрудник",
                        'type' => "dest_selector",
                        'params' => [
                            'context' => 'EMPLOYEE',
                            'multiple' => 'N',
                            'enableUsers' => 'Y',
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
                            'context' => 'EMPLOYEE',
                            'multiple' => 'N',
                            'enableUsers' => 'Y',
                            'enableSonetgroups' => 'N',
                            'departmentSelectDisable' => 'N',
                            'enableAll' => 'N',
                            'enableDepartments' => 'N',
                            'enableCrm' => 'N',
                        ]);
                    break;
               
                case "STATUS":
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
                default:
                    $columns[] = array(
                        'id' => $select["id"],
                        'name' => $select["name"],
                    );
                break;
            }
        }
        // DateType::type, То что в комментариях это и выводится
        $columns[] = array(
            "id" => "SELECTED_DATE",
            "name" => "Дата создания отчета",
            "type" => "date",
            "exclude" => array(
                DateType::TOMORROW,
                DateType::YESTERDAY,
                DateType::CURRENT_DAY,
                // DateType::CURRENT_WEEK,
                // DateType::CURRENT_MONTH,
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
                // DateType::LAST_WEEK,
                // DateType::LAST_MONTH,
                // DateType::RANGE,
                DateType::NEXT_WEEK,
                DateType::NEXT_MONTH,
            )
        );
        return $columns;
    }

    
    public function getOrderParams( $sortBy, $sortAs )
    {
        switch($sortBy){
            case 'TASK_LINK':
                return [ "TITLE" => $sortAs ];
            break;
            case 'TASK_GROUP_ID':
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
        // echo '<pre>'; print_r($gridFilter); echo '</pre>';
        foreach ($gridFilter as $filterKey => $filterValue) {
            switch ($filterKey) {
                case "EMPLOYEE_ID":
                    preg_match('/\d+/', $gridFilter[$filterKey], $matches);
                    $number = isset($matches[0]) ? (int)$matches[0] : null;
                    
                    $filter["EMPLOYEE_ID"] = $number;
                    break;
                case "SELECTED_DATE_from":
                    $date = new DateTime($filterValue);
                    $filter[">=START_DATE"] = $date->setTime(0, 0, 0);
                    break;
                case "SELECTED_DATE_to":
                    $date = new DateTime($filterValue);
                    $filter["<=END_DATE"] = $date->setTime(0, 0, 0);
                    break;
                case "STATUS":
                    $filter["STATUS"] = $gridFilter[$filterKey];
                    break;
                default:
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
            $key = $report['STATUS'] . '-' . $report['EMPLOYEE_ID'] . '-' . $report['START_DATE']->format('Y-m-d H');
            // Добавляем элемент в группированный массив
            if (!isset($groupedReports[$key])) {
                $groupedReports[$key] = $report;
            }else{
                $groupedReports[$key]["AGREED_TIME"] += $report['AGREED_TIME'];
                $groupedReports[$key]["ELAPSED_TIME"] += $report['ELAPSED_TIME'];
            }

            // echo '<pre>'; print_r($groupedReports[$key]); echo '</pre>';
            // echo '<pre>'; print_r($key); echo '</pre>';



        }
        
        $reports = $groupedReports;

        return $reports;
    }

    protected function formatTime($seconds)
    {
        // Вычисляем количество минут и часов
        $total_minutes  = floor($seconds / 60);
        $hours = floor($total_minutes  / 60);
        $minutes = $total_minutes % 60;
        // echo '<pre>'; print_r([$seconds,$minutesб,$total_minutes ,$hours]); echo '</pre>';
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
                
                $html = "<a href=\"/company/personal/user/$currUserId/tasks/task/view/$arData[ID]/?ta_sec=tasks&amp;ta_sub=list&amp;ta_el=title_click\" class=\"task-title task-status-text-color-in-progress\">";
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
