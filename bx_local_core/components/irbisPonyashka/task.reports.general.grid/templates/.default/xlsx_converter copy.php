<?php

ini_set('display_errors', 1);
error_reporting(-1);

require $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/micros/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if(!isset($data["rows"]) && !isset($data["columns"]) && !isset($data["visibleColumns"])) {  
    echo "Ошибка: Не удалось получить HTML содержимое.";
    exit;
}

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1));
    }
    return $initials;
}


// Создаем новый документ Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();


$dataVisibleColumns = $data["visibleColumns"];
$data["columns"] = array_filter($data["columns"], function ($item) use ( $dataVisibleColumns ) {
    return in_array($item['id'], $dataVisibleColumns);
});
$data["columns"] = array_values($data["columns"]);

// Установка заголовков
$columnIndex = 1;
foreach ($data["columns"] as $column) {
    $sheet->setCellValueByColumnAndRow($columnIndex, 1, $column["name"]);
    $columnIndex++;
}

$rowIndex = 2; 
foreach ($data["rows"] as $row) {
    $columnIndex = 1;
    foreach ($data["columns"] as $columnKey) {
        
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
                $value = getInitials( $row['docs'][$columnKey["id"]]["name"] );
                break;
            case "RATE":
                $value = $row['columns'][$columnKey["id"]] ? $row['columns'][$columnKey["id"]] : 0 ;
                break;
            default:
                $value = $row['docs'][$columnKey["id"]] ;
        }

        $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
        
        $columnIndex++;
    }
    $rowIndex++;
}

$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="data.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');

exit;

