<?php

ini_set('display_errors', 1);
error_reporting(-1);

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
require $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/micros/vendor/autoload.php';

use Bitrix\Socialnetwork\WorkgroupTable;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Dompdf\Dompdf;
use Dompdf\Options;


$expensesComponentResult = $APPLICATION->IncludeComponent(
    "micros:crm.item.list",
    'sidepanel',
    array(
		"id" => "5",
		"entityTypeId" => "190",
        "GET_RESULT" => "Y"
    )
);

if($expensesComponentResult && !empty($expensesComponentResult["grid"]))
{
    $expensesComponentResultRows = $expensesComponentResult["grid"]["ROWS"];
    $expensesComponentResultColumns = $expensesComponentResult["grid"]["COLUMNS"];
}
$expensesComponentResult = $APPLICATION->IncludeComponent(
    "micros:crm.item.list",
    'sidepanel',
    array(
		"id" => "5",
		"entityTypeId" => "190",
        "GET_RESULT" => "Y"
    )
);



$generalGridComponentResult = $APPLICATION->IncludeComponent(
    "micros:task.reports.general.grid",
    '',
    array(
        "GET_RESULT" => "Y"
    )
);
if($generalGridComponentResult && !empty($generalGridComponentResult["ROWS"]) && !empty($generalGridComponentResult["COLUMNS"]) )
{
    $data["rows"] = $generalGridComponentResult["ROWS"];
    $data["columns"] = $generalGridComponentResult["COLUMNS"];
}

// echo json_encode([$expensesComponentResult,$generalGridComponentResult]);
// die;

function getProject ( $id ) {
    $project = [];
    $project = WorkgroupTable::getList( [ "select" => ["ID", "ACTIVE", "NAME", "DESCRIPTION", "AVATAR_TYPE", "IMAGE_ID"], "filter" => [ "ID" => $id ]  ] )->Fetch();

    if($project && $project["IMAGE_ID"]){
        $rsFile = CFile::GetByID( $project["IMAGE_ID"] );
        $arFile = $rsFile->Fetch();
        $project["AVATAR"] = $arFile; 
    }

    return $project;
}

function getInitials($name) {
    // Разбиваем строку на массив слов
    $words = explode(' ', $name);
    $initials = '';
    
    // Проходим по каждому слову и добавляем первую букву к инициалам
    foreach ($words as $word) {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1)); // mb_substr для получения первого символа, mb_strtoupper для преобразования в верхний регистр
    }
    // echo '<pre>'; print_r($initials); echo '</pre>';
    return $initials;
}

if(!isset($data["rows"]) && !isset($data["columns"])) {  
    echo "Ошибка: Не удалось получить HTML содержимое.";
    exit;
}

$templatePath = '/home/bitrix/www/local/components/micros/task.reports.general.grid/templates/.default/template.docx';

$phpWord = IOFactory::load($templatePath);

$htmlWriter = IOFactory::createWriter($phpWord, 'HTML');

ob_start(); // Захватываем вывод HTML в буфер
$htmlWriter->save('php://output'); // Сохраняем HTML в выходной поток
$docxToHtml = ob_get_clean(); // Получаем HTML из буфера

// Загружаем HTML в DOMDocument
$dom = new DOMDocument();
libxml_use_internal_errors(true); // Игнорируем ошибки HTML
$dom->loadHTML($docxToHtml);
libxml_clear_errors();

//________________ #FIRST_TABLE ______________________________________________________________________________


unset($data["columns"][1], $data["columns"][2], $data["columns"][3], $data["columns"][7], $data["columns"][8] );
$data["columns"] = array_values($data["columns"]);

$doc_names = [
    'CREATED_DATE' => 'Date',
    'RESPONSIBLE_ID' => 'Initials',
    'TASK' => 'Task',
    'GROUP_ID' => 'Project',
    'MAIN_AGREED_TIME' => 'Hours',
    'RATE' => 'Amount(CURRENCY)',
];

// Определяем порядок сортировки
$order = [
    'CREATED_DATE',
    'RESPONSIBLE_ID',
    'TASK',
    'GROUP_ID',
    'MAIN_AGREED_TIME',
    'RATE'
];

// Сортировка массива
usort($data["columns"], function($a, $b) use ($order) {
    $posA = array_search($a['id'], $order);
    $posB = array_search($b['id'], $order);
    return $posA - $posB;
});

// Добавляем свойство doc_name каждому элементу массива
foreach ($data["columns"] as &$item) {
    if (isset($doc_names[$item['id']])) {
        $item['doc_name'] = $doc_names[$item['id']];
    }
}


$summary_services_rows = [];

$columns_of_services_table = [
    [
        "name" => "Initials",
        "id" => "INITIALS",
    ], 
    [
        "name" => "Name",
        "id" => "NAME",
    ], 
    [
        "name" => "Title",
        "id" => "TITLE",
    ], 
    [
        "name" => "Hours",
        "id" => "HOURS",
    ], 
    [
        "name" => "Hourly rate",
        "id" => "RATE",
    ], 
    [
        "name" => "Total price (CURRENCY)",
        "id" => "TOTAL_PRICE",
    ], 
];

ob_start();

?>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Document</title>
        <style>
            @page {
                /* margin: 100px 50px; */
            }
            header {
                position: fixed;
                top: 0px;
                left: 0px;
                right: 0px;
                /* height: 50px; */
                text-align: center;
                line-height: 35px;
                border-bottom: 2px solid #F4AAAC
            }
            .header-private {
                background-color: #F4AAAC;
                color: white;
                position: absolute;
                right: 0;
                top: 0;
                text-align: center;
                line-height: 30px;
                font-weight: bold;
            }
            footer {
                position: fixed;
                bottom: 0px;
                left: 0px;
                right: 0px;
                height: 50px;
                text-align: right;
                line-height: 35px;
                border-top: 2px solid #F4AAAC;
            }
            span.page-number {
                font-size: 24px;
                color: white;
                background-color: #F4AAAC;
                display: inline-block;
                padding: 0 1rem;
            }
            footer .page-number:after {
                background-color: #F4AAAC;
                color: white;
                content: counter(page);
                display: block;
            }
            footer div {
                display: flex;
                justify-content: end;
            }
            body {font-family: 'Arial'; font-size: 11pt;}
            * {font-family: 'Arial'; font-size: 11pt;}
            a.NoteRef {text-decoration: none;}
            hr {height: 1px; padding: 0; margin: 1em 0; border: 0; border-top: 1px solid #CCC;}
            table {border: 1px solid black; border-spacing: 0px; width : 100%;}
            td {border: 1px solid black;}
            p, .Normal {margin-bottom: 8pt;}
            .Normal Table {table-layout: auto;}
            .Table Grid {table-layout: auto; border-top-style: solid; border-top-color: auto; border-top-width: 0.2pt; border-left-style: solid; border-left-color: auto; border-left-width: 0.2pt; border-bottom-style: solid; border-bottom-color: auto; border-bottom-width: 0.2pt; border-right-style: solid; border-right-color: auto; border-right-width: 0.2pt;}
            .header {margin-bottom: 0pt;}
            .footer {margin-bottom: 0pt;}
            body > div + div {page-break-before: always;}
            div > *:first-child {page-break-before: auto;}
            @page page1 {size: A4 portrait; margin-right: 0.19722222222222in; margin-left: 1.18125in; margin-top: 0.19722222222222in; margin-bottom: 0.39375in; }
        </style>
        <style type="text/css">
            table#table { border:none }
            table#table tr td:nth-of-type(1) { padding: 0 0 0 12px; }
            table#table tr td:nth-last-of-type() { padding: 0 12px 0 0; }
            table td { border: none; } 
            /* #table tbody tr:last-child td { border-top: 1px solid black; } */
            #table tbody tr:nth-last-of-type(2) td { border-top: 1px solid black; }
            .content{
                max-width: 1920px;
                width: 100%;
                margin: 0px auto;
                overflow: scroll;
                /* margin-top: 4rem;
                margin-bottom: 4rem; */
            }
            section{
                margin-top:2rem;
                margin-bottom:2rem;
            }
            .table_tasks tr td { max-width: 120px; }
            .top_colontitutl {  width: 100%; text-align: right;  border-bottom: 2px solid #F4AAAC; margin-bottom: 2rem; }
            .top_colontitutl p { margin: 0; padding: 1rem; background: #F4AAAC; color: #fff; display: inline-block; }
            .bottom_colontitutl {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                border-bottom: 2px solid #F4AAAC;
                text-align: right;
                font-family: 'Segoe UI Light';
                font-size: 8pt;
                font-weight: bold;}
            .bottom_colontitutl p { margin: 0; padding: 1rem; background: #F4AAAC; color: #fff; display: inline-block; }
        </style>
    </head>
    <body>
        <!-- <header>
            <div class="header-private">PRIVATE AND CONFIDENTIAL</div>
        </header>

        <footer>
            <div>
                <span class="page-number"></span>
            </div>
        </footer> -->
        <div class="content">
            <section>
                <p>
                    <span lang="en-US" style="font-family: 'Segoe UI Light'; font-size: 10pt; color: #E63337; font-weight: bold;">FEES FOR SERVICES PROVIDED IN MONTH YEAR</span>
                </p>
                <!-- First table -->
                <? 
                    $amountUsdSum = 0;
                    $amountUzsSum = 0;
                    $totalHoursInSec = 0;

                ?>
                <table class="table_tasks" id="table">
                    <tbody>
                        <tr bgcolor="#F4AAAC">
                            <? array_map(function($header) {?>
                                <td bgcolor="#F4AAAC">
                                    <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                        <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?=$header['doc_name']?></span>
                                    </p>
                                </td>
                            <? }, $data["columns"]); ?>
                        </tr>
                        <? foreach ($data["rows"] as $rowData) { ?>
                            <? 
                                $rowData['docs']["RATE"][1] == "$" ? $amountUsdSum += (int)$rowData['data']["RATE"] : $amountUzsSum += (int)$rowData['data']["RATE"] ;
                            ?>
                            <tr>
                                <? array_map(function($header) use ($rowData) { ?>
                                    <?
                                        switch ($header["id"])
                                        {
                                            case "TASK":
                                                $columnValue = $rowData['docs'][$header["id"]] ;
                                                break;
                                            case "GROUP_ID":
                                                $columnValue = $rowData['docs'][$header["id"]] ;
                                                break;
                                            case "MAIN_AGREED_TIME":
                                                $columnValue = $rowData['columns'][$header["id"]] ;
                                                break;
                                            case "CREATED_DATE":
                                                $date = DateTime::createFromFormat('d.m.Y H:i:s', $rowData['data'][$header["id"]] );
                                                $dateOnly = $date->format('d.m.Y');
                                                $columnValue = $dateOnly;
                                                break;
                                            case "RESPONSIBLE_ID":
                                                $columnValue = getInitials( $rowData['docs'][$header["id"]]["name"] );
                                                break;
                                            case "RATE":
                                                $columnValue = $rowData['data'][$header["id"]] ? $rowData['docs'][$header["id"]][0] . " " . $rowData['docs'][$header["id"]][1] : "" ;
                                                break;
                                            default:
                                                $columnValue = $rowData['data'][$header["id"]] ;
                                        }
                                    ?>
                                    <td>
                                        <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                            <span style="font-family: 'Segoe UI Light'; font-size: 8pt;">
                                                <?=( $columnValue )?>
                                            </span>
                                        </p>
                                    </td>
                                <? }, $data["columns"] ); ?>
                            </tr>
                            <? 
                                $responsible_id = $rowData["data"]["RESPONSIBLE_ID"];
                                if($responsible_id){
                                    
                                    if(isset($summary_services_rows[$responsible_id])) {
                                        $rowData['data']["MAIN_AGREED_TIME"] ? $summary_services_rows[$responsible_id]["HOURS"] += $rowData['data']["MAIN_AGREED_TIME"] : null ;
                                        $summary_services_rows[$responsible_id]["TOTAL_PRICE"] ? $summary_services_rows[$responsible_id]["TOTAL_PRICE"] += $rowData['data']["RATE"] : null;
                                    }else{
                                        $summary_services_rows[$responsible_id] = [];

                                        $summary_services_rows[$responsible_id]["INITIALS"] = getInitials( $rowData['docs']["RESPONSIBLE_ID"]["name"] ); 
                                        $summary_services_rows[$responsible_id]["NAME"] = $rowData['docs']["RESPONSIBLE_ID"]["name"];
                                        $summary_services_rows[$responsible_id]["TITLE"] = $rowData['docs']["RESPONSIBLE_ID"]["title"];
                                        $summary_services_rows[$responsible_id]["HOURS"] = $rowData['data']["MAIN_AGREED_TIME"];
                                        $summary_services_rows[$responsible_id]["RATE"] = $rowData['docs']["RATE"];
                                        $summary_services_rows[$responsible_id]["TOTAL_PRICE"] = $rowData['data']["RATE"];
                                    }
                                }
                            ?>
                        <? } ?>
                        <? if($amountUzsSum != 0) { ?>
                            <tr>
                                <td>
                                    <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;">Total</span>
                                    </p>
                                </td>
                                <td>
                                    <p>&nbsp;</p>
                                </td>
                                <td>
                                    <p>&nbsp;</p>
                                </td>
                                <td>
                                    <p>&nbsp;</p>
                                </td>
                                <td>
                                    <p>&nbsp;</p>
                                </td>
                                <td>
                                    <p style="text-align: right; margin-top: 12pt;">
                                        <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?=$amountUzsSum?></span>
                                        <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"> UZS </span>
                                    </p>
                                </td>
                            </tr>
                        <? } ?>
                        <? if($amountUsdSum != 0) { ?>
                        <tr>
                            <td>
                                <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;">Total</span>
                                </p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p style="text-align: right; margin-top: 12pt;">
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?=$amountUsdSum?></span>
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"> $ </span>
                                </p>
                            </td>
                        </tr>
                        <? } ?>
                    </tbody>
                </table>

                <p>&nbsp;</p>
                <p>&nbsp;</p>
            </section>

            <section>
                <p style="margin-bottom: 6pt;">
                    <span lang="en-US" style="color: #FF0000; font-weight: bold;">Summary of services</span>
                </p>
                <!-- Second table -->
                <? 
                    $totalUsdSum = 0;
                    $totalUzsSum = 0;
                ?>
                <table class="table_costs" id="table">
                    <tbody>
                        <tr bgcolor="#F4AAAC">
                            <? array_map(function($header) {?>
                                <td bgcolor="#F4AAAC">
                                    <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                        <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?=$header['name']?></span>
                                    </p>
                                </td>
                            <? }, $columns_of_services_table); ?>
                        </tr>
                        <? foreach ($summary_services_rows as $rowData) { ?>
                            <? 
                                $rowData["RATE"][1] == "$" ? $totalUsdSum += (int)$rowData["TOTAL_PRICE"] : $totalUzsSum += (int)$rowData["TOTAL_PRICE"] ;
                                // $rowData["TOTAL_PRICE"] ? $totalSum += $rowData["TOTAL_PRICE"] : null;
                            ?>
                            <tr>
                                <? array_map(function($header) use ($rowData) { ?>
                                    <?php
                                        switch ($header["id"]) {
                                            case "HOURS":
                                                $hours = floor($rowData["HOURS"] / 3600);
                                                $minutes = ($rowData["HOURS"] / 60) % 60;
                                                
                                                $value = sprintf("%02d:%02d", $hours, $minutes);
                                                break;
                                            case "RATE":
                                                $value = $rowData[$header["id"]] ? $rowData[$header["id"]][0]." ".$rowData[$header["id"]][1] : "";
                                                break;
                                            case "TOTAL_PRICE":
                                                $value = $rowData[$header["id"]] ? $rowData[$header["id"]]." ".$rowData["RATE"][1] : "" ;
                                                break;
                                            default:
                                                $value = $rowData[$header["id"]];
                                        }
                                    ?>
                                    <td>
                                        <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                            <span style="font-family: 'Segoe UI Light'; font-size: 8pt;">
                                                <?=( $value )?>
                                            </span>
                                        </p>
                                    </td>
                                <? }, $columns_of_services_table ); ?>
                            </tr>

                        <? } ?>
                        <? if($totalUzsSum != 0) { ?>
                        <tr>
                            <td>
                                <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;">Total</span>
                                </p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p style="text-align: right; margin-top: 12pt;">
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?=$totalUzsSum?></span>
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"> UZS </span>
                                </p>
                            </td>
                        </tr>
                        <? } ?>
                        <? if($totalUsdSum != 0) { ?>
                        <tr>
                            <td>
                                <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;">Total</span>
                                </p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p style="text-align: right; margin-top: 12pt;">
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?=$totalUsdSum?></span>
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"> $ </span>
                                </p>
                            </td>
                        </tr>
                        <? } ?>
                    </tbody>
                </table>

                <p>&nbsp;</p>
                <p>&nbsp;</p>
            </section>

            <section>

                <p style="margin-bottom: 6pt;">
                    <span lang="en-US" style="color: #FF0000; font-weight: bold;">Expenses</span>
                </p>
                <!-- Third table -->
                <?
                    $expenses_columns = [
                        ["name" => "Name", "id" => "TITLE"],
                        ["name" => "Created by", "id" => "CREATED_BY"],
                        // ["name" => "Created time", "id" => "CREATED_TIME"],
                        ["name" => "Project", "id" => "UF_CRM_5_PROJECT_LINK"],
                        ["name" => "Category", "id" => "UF_CRM_5_COST_CATEGORY"],
                        ["name" => "Payable", "id" => "UF_CRM_5_ARE_PAYABLE"],
                        ["name" => "Sum", "id" => "UF_CRM_5_SUM"],
                    ];

                    $totalUsdSum = 0;
                    $totalUzsSum = 0;
                    
                ?>
                <table class="table_expenses" id="table">
                    <thead>
                        <tr bgcolor="#F4AAAC">
                            <?php array_map(function($header) {?>
                                <td bgcolor="#F4AAAC">
                                    <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                        <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?=$header['name']?></span>
                                    </p>
                                </td>
                            <?php }, $expenses_columns); ?>
                        </tr>
                    </thead>
                    <tbody>
                        <? foreach ( $expensesComponentResultRows as $item) { ?>
                            <?
                                $sum = explode("|",$item["data"]["UF_CRM_5_SUM"]);
                                $sum[1] == "USD" ? $totalUsdSum += (int)$sum[0] : $totalUzsSum += (int)$sum[0] ;
                            ?>
                            <tr>
                                <?php array_map(function($header) use ($item) { ?>  
                                <?
                                    switch ($header['id']) {
                                        case "TITLE":
                                        case "CREATED_BY":
                                            $value = strip_tags($item["columns"][$header['id']]);
                                        break;
                                        case "UF_CRM_5_PROJECT_LINK":
                                            $project = getProject ( $item["data"][$header['id']] ) ;
                                            $value = $project ? $project["NAME"] : "" ;
                                        break;
                                        case "UF_CRM_5_SUM":
                                            $value = explode("|",$item["data"][$header['id']]);
                                            $value[1] == "USD" ? $value = $value[0] . "$" : $value = $value[0] . "UZS" ;
                                            // $value = $item["data"][$header['id']];
                                        break;
                                        default:
                                            $value = $item["columns"][$header['id']];
                                    }
                                ?>
                                <td>
                                    <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                        <span style="font-family: 'Segoe UI Light'; font-size: 8pt;">
                                            <?= $value ?>
                                        </span>
                                    </p>
                                </td>
                                <?php }, $expenses_columns); ?>
                            </tr>
                        <? } ?>
                        <? if($totalUzsSum != 0) { ?>
                        <tr>
                            <td>
                                <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;">Total</span>
                                </p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p style="text-align: right; margin-top: 12pt;">
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?= $totalUzsSum ?></span>
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"> UZS </span>
                                </p>
                            </td>
                        </tr>
                        <? } ?>
                        <? if($totalUsdSum != 0) { ?>
                        <tr>
                            <td>
                                <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                                <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;">Total</span>
                                </p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p>&nbsp;</p>
                            </td>
                            <td>
                                <p style="text-align: right; margin-top: 12pt;">
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"><?= $totalUsdSum ?></span>
                                    <span style="font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;"> $ </span>
                                </p>
                            </td>
                        </tr>
                        <? } ?>
                    </tbody>
                </table>

                <p>&nbsp;</p>
                <p>&nbsp;</p>
            </section>
            

        </div>
    </body>
    </html>

<?

$html_by_ob = ob_get_contents();
ob_end_clean();

// echo $html_by_ob;
// die;

// $html_by_ob = $dom->saveHTML();

// Установка опций Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans'); // Устанавливаем шрифт по умолчанию

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html_by_ob);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="generated.pdf"');

echo $dompdf->output();

exit;

