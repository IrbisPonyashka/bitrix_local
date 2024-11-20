<?php

ini_set('display_errors', 1);
error_reporting(-1);

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
require $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/micros/vendor/autoload.php';
// require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
// require_once($_SERVER["DOCUMENT_ROOT"] . "/local/components/micros/task.reports.general.grid/class.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Style;

// use Bitrix\Main\Grid\Options as GridOptions;
// use Bitrix\Main\CGridOptions;

// $json = file_get_contents('php://input');
// $data = json_decode($json, true);

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

function getInitials($name)
{
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1));
    }
    return $initials;
}

function formatUSDRateValue($rate)
{
    // Создаем форматтер для валюты USD
    $formatterUSD = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
    return $formatterUSD->formatCurrency($rate, 'USD');
}
function formatUZSRateValue($rate)
{
    // Создаем форматтер для валюты UZS
    $formatterUZS = new NumberFormatter('uz_UZ', NumberFormatter::DECIMAL);
    return $formatterUZS->formatCurrency($rate, 'UZS');
}

function getColumnWidthById($id)
{

    $width = 0;

    switch ($id) {
        case "TASK":
        case "GROUP_ID":
            $width = 30;
        break;
        case "RESPONSIBLE_ID":
        case "ACCOMPLICES":
            $width = 25;
        break;
        case "TAGS":
        case "STATUS":
            $width = 20;
        break;
        case "UF_BILLABLE":
        case "RATE":
        case "CREATED_DATE":
        case "AGREED_TIME":
        case "MAIN_AGREED_TIME":
            $width = 15;
            break;
        default:
            $width = 12;
        break;
    }
    return $width;
}


if(!isset($data["rows"]) && !isset($data["columns"]) && !isset($data["visibleColumns"])) {  
    echo "Ошибка: Не удалось получить HTML содержимое.";
    exit;
}

// Создаем новый документ Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Форматирование ячеек
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
    'fill' => [
        'fillType' => Fill::FILL_NONE,
    ],
];


// $dataVisibleColumns = $data["visibleColumns"];
// $data["columns"] = array_filter($data["columns"], function ($item) use ( $dataVisibleColumns ) {
//     return in_array($item['id'], $dataVisibleColumns);
// });
// echo '<pre>'; print_r($data["columns"]); echo '</pre>';
// die;

// Перемещаем элемент с id 'RATE' в конец
usort($data["columns"], function ($a, $b) {
    return $a['id'] === 'RATE' ? 1 : ($b['id'] === 'RATE' ? -1 : 0);
});

$data["columns"][] = [
    "id" => "CURRENCY",
    "name" => "Валюта",
    "default" => 1
];
$data["columns"] = array_values($data["columns"]);


// Установка заголовков
$columnIndex = 1;
foreach ($data["columns"] as $column)
{
    $cellCoordinate = $sheet->getCellByColumnAndRow($columnIndex, 1)->getCoordinate();
    $sheet->setCellValueByColumnAndRow($columnIndex, 1, $column["name"]);

    $sheet->getColumnDimensionByColumn($columnIndex)->setWidth( getColumnWidthById($column["id"]) );

    $sheet->getStyle($cellCoordinate)->applyFromArray([
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);
    
    $columnIndex++;
}
$rowIndex = 2; 
$totalUsdSum = 0;
$totalUzsSum = 0;
foreach ($data["rows"] as $row) {
    $columnIndex = 1;

    foreach ($data["columns"] as $columnKey) {
        
        $cellCoordinate = $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getCoordinate();
        // Устанавливаем границы только для ячеек с данными
        $sheet->getStyle($cellCoordinate)->applyFromArray($styleArray);

        switch ($columnKey["id"])
        {
            case "TASK":
                $value = $row['docs'][$columnKey["id"]] ;
                break;
            case "GROUP_ID":
                $value = $row['docs'][$columnKey["id"]] ;
                break;
            case "MAIN_AGREED_TIME":
                $value = $row['columns'][$columnKey["id"]] ;
                break;
            case "CREATED_DATE":
                $date = DateTime::createFromFormat('d.m.Y H:i:s', $row['data'][$columnKey["id"]] );
                $dateOnly = $date->format('d.m.Y');
                $value = $dateOnly;
                break;
            case "RESPONSIBLE_ID":
                $value = $row['docs'][$columnKey["id"]]["name"];
                break;
            case "RATE":
                $currency = ($row['docs']["RATE"] && $row['docs']["RATE"][1]) ? $row['docs']["RATE"][1] : "" ;
                $sum = (int)$row['data']["RATE"] ?? 0;
                // $value = $currency == "$" ? formatUSDRateValue($sum) : formatUZSRateValue($sum);
                $value = (int)$sum;
                // echo '<pre>'; print_r($columnKey["id"] . " => " .$value); echo '</pre>';
                $sheet->getStyle($cellCoordinate)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ]
                ]);
                break;
            case "CURRENCY":
                $currency = ($row['docs']["RATE"] && $row['docs']["RATE"][1]) ? $row['docs']["RATE"][1] : "" ;

                $value = $currency == "$" ? "USD" : $currency;
                $sheet->getStyle($cellCoordinate)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ]
                ]);
                break;
            default:
                $value = $row['docs'][$columnKey["id"]] ;
        }

        $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);

        $columnIndex++;
    }

    if(isset($row['docs']["RATE"][1])){
        $rate = (int)$row['data']["RATE"] ?? 0;
        $row['docs']["RATE"][1] == "$" ? (int)$totalUsdSum += $rate : (int)$totalUzsSum += $rate ;
    }
    $rowIndex++;
}

// die;

// Добавляем итого
$totalTextCellCoordinate = $sheet->getCellByColumnAndRow( $columnIndex-3, $rowIndex)->getCoordinate();
$sheet->setCellValueByColumnAndRow($columnIndex-3, $rowIndex, "Итого");
$sheet->getColumnDimensionByColumn($columnIndex-3)->setWidth( 15 );
$sheet->getStyle($totalTextCellCoordinate)->applyFromArray($styleArray);

$sheet->getStyle($totalTextCellCoordinate)->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

// Добавляем итого (Значение USD)
$totalUsdCellCoordinate = $sheet->getCellByColumnAndRow( $columnIndex-2, $rowIndex)->getCoordinate();
$sheet->setCellValueByColumnAndRow($columnIndex-2, $rowIndex, $totalUsdSum);
// $sheet->setCellValueByColumnAndRow($columnIndex-2, $rowIndex, formatUSDRateValue($totalUsdSum));
$sheet->getColumnDimensionByColumn($columnIndex-2)->setWidth( 15 );
$sheet->getStyle($totalUsdCellCoordinate)->applyFromArray($styleArray);

$sheet->getStyle($totalUsdCellCoordinate)->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
    ],
]);

// Добавляем итого (Валюту USD)
$totalUsdCellCoordinate = $sheet->getCellByColumnAndRow( $columnIndex-1, $rowIndex)->getCoordinate();
$sheet->setCellValueByColumnAndRow($columnIndex-1, $rowIndex, "USD");
$sheet->getColumnDimensionByColumn($columnIndex-1)->setWidth( 15 );
$sheet->getStyle($totalUsdCellCoordinate)->applyFromArray($styleArray);

$sheet->getStyle($totalUsdCellCoordinate)->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
    ],
]);


// Добавляем итого
$totalTextCellCoordinate = $sheet->getCellByColumnAndRow( $columnIndex-3, $rowIndex+1)->getCoordinate();
$sheet->setCellValueByColumnAndRow($columnIndex-3, $rowIndex+1, "Итого");
$sheet->getColumnDimensionByColumn($columnIndex-3)->setWidth( 15 );
$sheet->getStyle($totalTextCellCoordinate)->applyFromArray($styleArray);

$sheet->getStyle($totalTextCellCoordinate)->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

// Добавляем итого (Значение UZS)
$totalUsdCellCoordinate = $sheet->getCellByColumnAndRow( $columnIndex-2, $rowIndex+1)->getCoordinate();
$sheet->setCellValueByColumnAndRow($columnIndex-2, $rowIndex+1, $totalUzsSum);
// $sheet->setCellValueByColumnAndRow($columnIndex-2, $rowIndex+1, formatUZSRateValue($totalUzsSum));
$sheet->getColumnDimensionByColumn($columnIndex-2)->setWidth( 15 );
$sheet->getStyle($totalUsdCellCoordinate)->applyFromArray($styleArray);

$sheet->getStyle($totalUsdCellCoordinate)->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
    ],
]);

// Добавляем итого (Валюту UZS)
$totalUsdCellCoordinate = $sheet->getCellByColumnAndRow( $columnIndex-1, $rowIndex+1)->getCoordinate();
$sheet->setCellValueByColumnAndRow($columnIndex-1, $rowIndex+1, "UZS");
$sheet->getColumnDimensionByColumn($columnIndex-1)->setWidth( 15 );
$sheet->getStyle($totalUsdCellCoordinate)->applyFromArray($styleArray);

$sheet->getStyle($totalUsdCellCoordinate)->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
    ],
]);

$sheet->setShowGridlines(false);

$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="data.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');

exit;

