<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Type\DateTime;
// use Bitrix\Main\UI\Filter\Options; 


if(isset($_REQUEST["IFRAME"]) || (isset($_REQUEST["IFRAME"]) && isset($_REQUEST["EMPLOYEE_ID"])) )
{
    $dateTimeStart =   new DateTime($_REQUEST["START"]);
    $dateTimeEnd =   new DateTime($_REQUEST["END"]);
    
    $dateTimeStart = $dateTimeStart->setTime(0, 0, 0); 
    $dateTimeEnd = $dateTimeEnd->setTime(0, 0, 0); 

    $expensesSumUzs = 0;
    $expensesSumUsd = 0;
    foreach ($arResult["grid"]["ROWS"] as $key => $arRow)
    {
        $smartItemCreatedDate = new DateTime($arRow["data"]["CREATED_TIME"]); 
        $smartItemCreatedDate = $smartItemCreatedDate->setTime(0, 0, 0); 

        $mustDelete = $smartItemCreatedDate > $dateTimeStart && $smartItemCreatedDate < $dateTimeEnd ? false : true;

        if($_REQUEST["EMPLOYEE_ID"]){
            if($mustDelete || $arRow["data"]["CREATED_BY"] != $_REQUEST["EMPLOYEE_ID"] ){
                unset($arResult["grid"]["ROWS"][$key]);
            }
        }
            unset($arResult["grid"]["ROWS"][$key]["actions"]);

        $summa = explode("|",$arResult["grid"]["ROWS"][$key]["data"]["UF_CRM_5_SUM"]);

        $expensesSumUzs += $summa[1] == "UZS" ? $summa[0] : 0 ;
        $expensesSumUsd += $summa[1] == "USD" ? $summa[0] : 0 ;
    }

    $arResult["EXPENSES_SUM_UZS"] = $expensesSumUzs;
    $arResult["EXPENSES_SUM_USD"] = $expensesSumUsd;

    $arResult["grid"]["SHOW_ACTION_PANEL"] = false;
    $arResult["grid"]["SHOW_ROW_CHECKBOXES"] = false;
    
}else if( isset($_REQUEST["IFRAME"]) )
{
}

foreach ($arResult["toolbar_parameters"]["filter"]["FILTER"] as $key => $filterValue) {
    if($filterValue["id"] == "UF_CRM_5_PROJECT_LINK")
    {
        $arResult["toolbar_parameters"]["filter"]["FILTER"][$key] = Array(
            "id" => "UF_CRM_5_PROJECT_LINK",
            "name" => "Проект",
            'type' => "dest_selector",
            'params' => [
                "context" => "CRM_ENTITIES",
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
            ]
        );

    }
}

unset($arResult["toolbar_parameters"]["filter"]["FILTER"][1]);

