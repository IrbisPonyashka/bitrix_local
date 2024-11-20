<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

use Bitrix\Main\Grid\Cell\Label\Color;
use Bitrix\Main\Grid\Cell\Label\RemoveButtonType;

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;

use Bitrix\Socialnetwork\WorkgroupTable;
//use Bitrix\Main\UserTable;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;

use Micros\Harvest\MicrosHarvestProjectsTable;

class project_management extends CBitrixComponent
{

    public $user;
    public $groups;
    public $clients;

    public function executeComponent()
    {
        $this->arResult = [];
        $this->gridName = "project_params_list";
        $this->filterName = "project_params_filter";

        $filter = [];
        $filterOption = new Bitrix\Main\UI\Filter\Options( $this->filterName );
        $filterData = $filterOption->getFilter();
        foreach ($filterData as $k => $v) {
            $filter[$k] = $v;
        };
        $this->arResult['FILTER'] = $filter;

        $this->grid = new GridOptions($this->gridName);
        $gridSortOptions = $this->grid->GetSorting();
        $nav_params = $this->grid->GetNavParams();
        $this->arResult["NAV"] = new PageNavigation($this->gridName);
        $this->arResult["NAV"]->allowAllRecords(true)
            ->setPageSize($nav_params['nPageSize'])
            ->initFromUri();

        $this->sortBy = array_keys($gridSortOptions["sort"])[0];
        $this->sortAs = strtoupper($gridSortOptions["sort"][$this->sortBy]);

        if (Loader::includeModule('micros.harvest.app')) {

            $this->projectsTableMap = MicrosHarvestProjectsTable::getMap();
            $fieldsParams = [
//                "filter" => [
//                    "ESTIMATED_TIME" => "08:00"
//                ]
            ];

//            if(!empty($this->arResult['FILTER'])){
//                $fieldsParams = $this::getAdaptedFilter($this->arResult['FILTER']);
//            }
//            $fieldsParams = [];
            $projectsTableList = MicrosHarvestProjectsTable::getList( $fieldsParams )->fetchAll();

            $this->arResult['MAP'] = $this->projectsTableMap;
            $this->arResult['FILTER_COLUMNS'] = $this->getFilter();
            $this->arResult["PROJECTS"] = !empty($projectsTableList) ? $projectsTableList : [] ;
            $this->arResult['COLUMNS'] = $this->getColumns();
            $this->arResult['ROWS'] = $this->getRows($projectsTableList);


        } else {

            ShowError("Модуль micros.harvest.app не установлен.");
        
        }

//        $this->arResult = CompanyTable::getList( [ "select" => ["ID", "TITLE"], "filter" => [ "ID" => "17044" ] ] )->Fetch();
//        $this->arResult = $this->getCustomer("17044", "COMPANY");

        $this->includeComponentTemplate();
        return $this->arResult;
    }


    private function getFilter()
    {
        $columns = [];
        foreach ( $this->projectsTableMap as $key => $item) {
            if($key=="ID"){
                $columns[] = array(
                    'id' => $key,
                    'name' => "ID настройки",
                    'type' => "number"
                );
            }else if( $key=="CUSTOMER_TYPE" ){
                $columns[] = array(
                    'id' => $key,
                    'name' => "Тип клиента",
                    'type' => "list",
                    'items' => ["contact" => "контакт", "company" => "компания"],
                );
            }else{
                $columns[] = array(
                    'id' => $key,
                    'name' => $item['title'],
                    'type' => $item['data_type'] == "enum" ? "list" : ($item['data_type'] == 'integer' ? 'number' : $item['data_type'])
                );
            }
        }

//        echo '<pre>'; print_r($columns); echo '</pre>';
        return $columns;
    }

    private function getColumns()
    {
        $columns = Array();

        $columns[] = array(
            'id' => "ID", 'name' => "ID настройки", 'sort' => true, 'default' => true
        );
        foreach ( $this->projectsTableMap as $fieldKey => $field )
        {
            if ( !empty($field["title"]) ){
                $columns[] = array(
                    'id' => $fieldKey, 'name' => $field["title"], 'sort' => true, 'default' => true
                );
            }
        }
        return $columns;
    }

    public function getRows ($projects)
    {
        $rows = [];
        $projectsTableMap = $this->projectsTableMap;

        if( count($projects)>0 ){
            foreach ( $projects as $projectKey => $project ) {
                $column['actions'] = [
                    [
                        'text'    => 'Редакиторвать',
                        'onclick' => "BX.SidePanel.Instance.open(\"/bitrix/components/micros/projectparams.management/templates/.default/elements/sidepanel.php?action=edit\",
                            { 
                                width: 800,
                                Title: \"Редактировать\",
                                allowChangeHistory: false,
                                requestMethod: \"post\",
                                requestParams: { // post-параметры
                                    MAP: ". json_encode($this->projectsTableMap) .",
                                    DATA: ".json_encode($project).",
                                }
                            })"
                    ]
                ];
                foreach ($projectsTableMap as $fieldKey => $field) {
                    if( $fieldKey == "ID") {
                        $project[$fieldKey] = $project[$fieldKey];
                    }else if( $fieldKey == "CUSTOMER_ID") {
                        $getCustomer = $this->getCustomer( $project[$fieldKey], $project["CUSTOMER_TYPE"]);
                        $customerHref = $project["CUSTOMER_TYPE"] === "CONTACT" ? "/crm/contact/details/$getCustomer[ID]/" : "/crm/company/details/$getCustomer[ID]/";
                        $customerName = $project["CUSTOMER_TYPE"] === "CONTACT" ? $getCustomer["NAME"]." ".$getCustomer["LAST_NAME"] : $getCustomer["TITLE"];

                        $project[$fieldKey] = "<a href='$customerHref'> $customerName </a>";
                    }else if ($fieldKey == "PROJECT_ID"){
                        $getProject = $this->getProject( $project[$fieldKey] );

                        $project[$fieldKey] = "<a href='/workgroups/group/$getProject[ID]/tasks/'> $getProject[NAME] </a>";
                    }
                    $column['data'][$fieldKey] = $project[$fieldKey];
                }

                $rows[] = $column;
            }
        }

        return $rows;
    }

    public function getCustomer ( $customerId, $customerType )
    {
        $customer = [];
         if($customerType=="CONTACT"){
             $customerList = ContactTable::getList( [ "select" => ["ID", "NAME", "LAST_NAME"], "filter" => [ "ID" => $customerId ] ] )->Fetch();
             $customer = $customerList ?? [];
         }else{
             $customerList = CompanyTable::getList( [ "select" => ["ID", "TITLE"], "filter" => [ "ID" => $customerId ] ] )->Fetch();
             $customer = $customerList ?? [];
         }

         return $customer;
    }

    public function getProject ( $id )
    {
        $project = [];
        $projectList = WorkgroupTable::getList( [ "select" => ["ID", "ACTIVE", "NAME", "DESCRIPTION", "AVATAR_TYPE", "IMAGE_ID"], "filter" => [ "ID" => $id ]  ] )->Fetch();
        $project = $projectList ?? [];
        return $project;
    }


    private function getAdaptedFilter ( $filter )
    {
//        echo '<pre>'; print_r($filter); echo '</pre>';
//        $newFilter = [];
//        $mapKeys = array_keys($this->projectsTableMap);
//        $i = 0;
//        while( $i < count($mapKeys) ){
//
//            if( array_key_exists( "$mapKeys[$i]_numsel", $filter) ){
//                array_push( $newFilter, $mapKeys[$i] );
//            }else if( array_key_exists( "$mapKeys[$i]", $filter) ) {
//                array_push( $newFilter, $mapKeys[$i] );
//            }
//
//            $i++;
//        }
//        echo '<pre>'; print_r($newFilter); echo '</pre>';

    }

}
