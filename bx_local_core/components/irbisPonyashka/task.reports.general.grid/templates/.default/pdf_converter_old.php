<?php

ini_set('display_errors', 1);
error_reporting(-1);

require $_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/micros/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Dompdf\Dompdf;
use Dompdf\Options;

$json = file_get_contents('php://input');
$data = json_decode($json, true);


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

$xpath = new DOMXPath($dom);

// Пример изменения: добавление класса всем <th>
$tableElements = $dom->getElementsByTagName('table');
$tdElements = $dom->getElementsByTagName('td');
$thElements = $dom->getElementsByTagName('th');
$thElements = $dom->getElementsByTagName('th');

$firstDivSearch = $xpath->query("//body/div[1]");
$firstDiv = $firstDivSearch->item(0);
$firstDiv->removeAttribute('style');
$firstDiv->setAttribute('class', 'content');

/* if($firstDiv) {
    // Создаем новый div элемент
    $topDivColontitul = $dom->createElement('div');
    $topDivColontitul->setAttribute('class', 'top_colontitutl');

    $bottomDivColontitul = $dom->createElement('div');
    $bottomDivColontitul->setAttribute('class', 'bottom_colontitutl');

    // Создаем элемент p внутри нового div
    $pTopDivColontitul = $dom->createElement('p', 'PRIVATE AND CONFIDENTIAL');
    $pbottomDivColontitul = $dom->createElement('p', '1');

    // Добавляем p в новый div
    $topDivColontitul->appendChild($pTopDivColontitul);

    // Вставляем новый div как первый дочерний элемент $firstDiv
    if ($firstDiv->hasChildNodes()) {
        $firstDiv->insertBefore($topDivColontitul, $firstDiv->firstChild);
    } else {
        $firstDiv->appendChild($topDivColontitul);
    }

    $bottomDivColontitul->appendChild($pbottomDivColontitul);

    $firstDiv->appendChild($bottomDivColontitul);
} */


$style = $dom->createElement(
    'style', 
    " table#table { border:none }
    table#table tr td:nth-of-type(1) { padding: 0 0 0 12px; }
    table#table tr td:nth-last-of-type() { padding: 0 12px 0 0; }
    table td { border: none; } 
    #table tbody tr:last-child td { border-top: 1px solid black; }
    .content{ max-width: 1920px; width: 100%; margin: 0px auto; overflow: scroll; }
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
    "
);

$style->setAttribute('type', 'text/css');

$head = $dom->getElementsByTagName('head')->item(0);

if ($head) {
    $head->appendChild($style);
} else {
    $head = $dom->createElement('head');
    $dom->documentElement->insertBefore($head, $dom->documentElement->firstChild);
    $head->appendChild($style);
}


foreach ($tableElements as $key => $table) {
    switch ($key) {
        case 0:
            $table->setAttribute('id', 'table');
            $table->setAttribute('class', 'table_tasks');
        break;
        case 1:
            $table->setAttribute('id', 'table');
            $table->setAttribute('class', 'table_costs');
        break;
    }
}

foreach ($tdElements as $key => $td) {
    $td->removeAttribute('style');
}

//________________ #FIRST_TABLE ______________________________________________________________________________


$firstTasksRow = $xpath->query("//table[contains(@class, 'table_tasks')]/tr[1]")->item(0);

if ($firstTasksRow) {
    // Находим все td внутри найденной строки tr
    $tdElements = $xpath->query(".//td", $firstTasksRow);

    // Удаляем все найденные td
    foreach ($tdElements as $td) {
        $firstTasksRow->removeChild($td);
    }

    // Добавляем новые td, используя данные из массива $data["columns"]
    if (isset($data["columns"]) && is_array($data["columns"])) {
        
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

        foreach ($data["columns"] as $columnData) {
            // Создаем новый элемент td
            $newTd = $dom->createElement('td');
            $newTd->setAttribute('bgcolor', '#F4AAAC');

            // Создаем элемент p внутри td
            $p = $dom->createElement('p', "");
            $p->setAttribute('style', 'margin-top: 4.8pt; margin-bottom: 4.8pt;');

            // Создаем span внутри p
            $span = $dom->createElement('span', htmlspecialchars($columnData["doc_name"]));
            $span->setAttribute('style', "font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;");

            // Добавляем span в p
            $p->appendChild($span);
            // Добавляем p в td
            $newTd->appendChild($p);
            // Добавляем новый td в строку tr
            $firstTasksRow->appendChild($newTd);
        }
    }
}

$rows = $xpath->query("//table[contains(@class, 'table_tasks')]/tr");

$headerRow = $rows->item(0);
$footerRow = $rows->item($rows->length - 1);

foreach ($rows as $key => $row) {
    if ($key > 0 && $key < ($rows->length - 1) ) {
        $row->parentNode->removeChild($row);
    }
}

$summaryServicesRow = [];
$amountSum = 0;
foreach ($data["rows"] as $rowData) {
    // Создаем новый элемент tr
    $newTr = $dom->createElement('tr');

    $rowData["data"]["RATE"] ? $amountSum += $rowData["data"]["RATE"] : null;

    foreach ( $data["columns"] as $columnKey)
    {
        $newTd = $dom->createElement('td');

        switch ($columnKey["id"])
        {
            case "TASK":
                $columnValue = $rowData['docs'][$columnKey["id"]] ;
                break;
            case "GROUP_ID":
                $columnValue = $rowData['docs'][$columnKey["id"]] ;
                break;
            case "MAIN_AGREED_TIME":
                $columnValue = $rowData['columns'][$columnKey["id"]] ;
                break;
            case "CREATED_DATE":
                $date = DateTime::createFromFormat('d.m.Y H:i:s', $rowData['data'][$columnKey["id"]] );
                $dateOnly = $date->format('d.m.Y');
                $columnValue = $dateOnly;
                break;
            case "RESPONSIBLE_ID":
                $columnValue = getInitials( $rowData['docs'][$columnKey["id"]]["name"] );
                break;
            case "RATE":
                $columnValue = $rowData['columns'][$columnKey["id"]] ? $rowData['columns'][$columnKey["id"]][0] . $rowData['columns'][$columnKey["id"]][1] : 0 ;
                break;
            default:
                $columnValue = $rowData['data'][$columnKey["id"]] ;
        }

        $p = $dom->createElement('p');
        $p->setAttribute('style', 'margin-top: 4.8pt; margin-bottom: 4.8pt;');
        $span = $dom->createElement('span', $columnValue ?? '' );
        $span->setAttribute('style', "font-family: 'Segoe UI Light'; font-size: 8pt;");

        $p->appendChild($span);

        $newTd->appendChild($p);

        $newTr->appendChild($newTd);
    }

    $responsible_id = $rowData["data"]["RESPONSIBLE_ID"];
    if($responsible_id){
        
        if(isset($summaryServicesRow[$responsible_id])) {
            $rowData['data']["MAIN_AGREED_TIME"] ? $summaryServicesRow[$responsible_id]["HOURS"] += $rowData['data']["MAIN_AGREED_TIME"] : null ;
            $summaryServicesRow[$responsible_id]["TOTAL_PRICE"] ? $summaryServicesRow[$responsible_id]["TOTAL_PRICE"] += $rowData['data']["RATE"] : null;
        }else{
            $summaryServicesRow[$responsible_id] = [];

            $summaryServicesRow[$responsible_id]["INITIALS"] = getInitials( $rowData['docs']["RESPONSIBLE_ID"]["name"] ); 
            $summaryServicesRow[$responsible_id]["NAME"] = $rowData['docs']["RESPONSIBLE_ID"]["name"];
            $summaryServicesRow[$responsible_id]["TITLE"] = $rowData['docs']["RESPONSIBLE_ID"]["title"];
            $summaryServicesRow[$responsible_id]["HOURS"] = $rowData['data']["MAIN_AGREED_TIME"];
            $summaryServicesRow[$responsible_id]["RATE"] = $rowData['docs']["RATE"];
            $summaryServicesRow[$responsible_id]["TOTAL_PRICE"] = $rowData['data']["RATE"];
        }
    }
    
    $footerCells = $footerRow->getElementsByTagName('td');

    // Если ячейки существуют, заменяем содержимое последней ячейки
    if ($footerCells->length > 0) {
        $lastTd = $footerCells->item($footerCells->length - 1); // Последняя ячейка td

        // Создаем новый элемент p для вставки
        $p = $dom->createElement('p');
        $p->setAttribute('style', 'text-align: right; margin-top: 12pt;');

        // Создаем элемент span для вставки значения $amountSum
        $span1 = $dom->createElement('span', '$ ');
        $span1->setAttribute('style', "font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;");

        $span2 = $dom->createElement('span', htmlspecialchars($amountSum));
        $span2->setAttribute('style', "font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;");

        // Вставляем span'ы внутрь p
        $p->appendChild($span1);
        $p->appendChild($span2);

        // Очищаем содержимое последнего td и добавляем новый p
        $lastTd->nodeValue = ''; // Очистка содержимого
        $lastTd->appendChild($p);
    }

    // Добавляем новую строку tr в таблицу после заголовка или первой строки
    $headerRow->parentNode->appendChild($newTr);
    $footerRow->parentNode->insertBefore($newTr, $footerRow);
}

//________________ #FIRST_TABLE END______________________________________________________________________________


// _______________ #SECOND_TABLE _______________________________________________________________________________

$columnsOfServicesTable = [
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

$firstCostsRow = $xpath->query("//table[contains(@class, 'table_costs')]/tr[1]")->item(0);

if ($firstCostsRow) {
    // Находим все td внутри найденной строки tr
    $tdElements = $xpath->query(".//td", $firstCostsRow);

    // Удаляем все найденные td
    foreach ($tdElements as $td) {
        $firstCostsRow->removeChild($td);
    }

    // Добавляем новые td, используя данные из массива $data["columns"]
    if (isset($data["columns"]) && is_array($data["columns"])) {
        

        foreach ($columnsOfServicesTable as $columnData) {
            // Создаем новый элемент td
            $newTd = $dom->createElement('td');
            $newTd->setAttribute('bgcolor', '#F4AAAC');

            // Создаем элемент p внутри td
            $p = $dom->createElement('p', "");
            $p->setAttribute('style', 'margin-top: 4.8pt; margin-bottom: 4.8pt;');

            // Создаем span внутри p
            $span = $dom->createElement('span', htmlspecialchars($columnData["name"]));
            $span->setAttribute('style', "font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;");

            // Добавляем span в p
            $p->appendChild($span);
            // Добавляем p в td
            $newTd->appendChild($p);
            // Добавляем новый td в строку tr
            $firstCostsRow->appendChild($newTd);
        }

    }
}


$rows = $xpath->query("//table[contains(@class, 'table_costs')]/tr");

$headerRow = $rows->item(0);
$footerRow = $rows->item($rows->length - 1);

foreach ($rows as $key => $row) {
    if ($key > 0 && $key < ($rows->length - 1) ) {
        $row->parentNode->removeChild($row);
    }
}

$totalSum = 0;
foreach ($summaryServicesRow as $rowData) {

    // Создаем новый элемент tr
    $newTr = $dom->createElement('tr');
    // echo '<pre>'; print_r($rowData["TOTAL_PRICE"]); echo '</pre>';
    $rowData["TOTAL_PRICE"] ? $totalSum += $rowData["TOTAL_PRICE"] : null;

    foreach ($columnsOfServicesTable as $columnKey)
    {
        $newTd = $dom->createElement('td');

        switch ($columnKey["id"]) {
            case "HOURS":
                $value = gmdate("H:i", $rowData[$columnKey["id"]] ?? 0);
                break;
            case "RATE":
                $value = $rowData[$columnKey["id"]] ? $rowData[$columnKey["id"]][0].$rowData[$columnKey["id"]][1] : "";
                break;
            case "TOTAL_PRICE":
                $value = $rowData[$columnKey["id"]] ? $rowData[$columnKey["id"]]."$" : "";
                break;
            default:
                $value = $rowData[$columnKey["id"]];
        }

        $p = $dom->createElement('p');
        $p->setAttribute('style', 'margin-top: 4.8pt; margin-bottom: 4.8pt;');

        $span = $dom->createElement('span', $value ?? '' );
        $span->setAttribute('style', "font-family: 'Segoe UI Light'; font-size: 8pt;");

        $p->appendChild($span);

        $newTd->appendChild($p);

        $newTr->appendChild($newTd);
    }
    
    $footerCells = $footerRow->getElementsByTagName('td');

    // Если ячейки существуют, заменяем содержимое последней ячейки
    if ($footerCells->length > 0) {
        $lastTd = $footerCells->item($footerCells->length - 1); // Последняя ячейка td

        // Создаем новый элемент p для вставки
        $p = $dom->createElement('p');
        $p->setAttribute('style', 'text-align: right; margin-top: 12pt;');

        // Создаем элемент span для вставки значения $totalSum
        $span1 = $dom->createElement('span', '$ ');
        $span1->setAttribute('style', "font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;");

        $span2 = $dom->createElement('span', htmlspecialchars($totalSum));
        $span2->setAttribute('style', "font-family: 'Segoe UI Light'; font-size: 8pt; font-weight: bold;");

        // Вставляем span'ы внутрь p
        $p->appendChild($span1);
        $p->appendChild($span2);

        // Очищаем содержимое последнего td и добавляем новый p
        $lastTd->nodeValue = ''; // Очистка содержимого
        $lastTd->appendChild($p);
    }

    // Добавляем новую строку tr в таблицу после заголовка или первой строки
    $headerRow->parentNode->appendChild($newTr);
    $footerRow->parentNode->insertBefore($newTr, $footerRow);
}

// _______________ #SECOND_TABLE END _______________________________________________________________________________



// _______________ #THIRD_TABLE _______________________________________________________________________________


$arHeaders = [
    ["name"=>"test","id" => "col1"],
    ["name"=>"test2","id" => "col2"],
    ["name"=>"test3","id" => "col3"],
    ["name"=>"test4","id" => "col4"],
];
$arItems = [
    [ 
        "col1" =>  "row1",
        "col2" =>  "row2",
        "col3" =>  "row3",
        "col4" =>  "ro4"
    ],
    [ 
        "col1" =>  "row21",
        "col2" =>  "row22",
        "col3" =>  "row3",
        "col4" =>  "row4"
    ],
    [ 
        "col1" =>  "row31",
        "col2" =>  "row32",
        "col3" =>  "row3",
        "col4" =>  "row4"
    ],
    [ 
        "col1" =>  "row41",
        "col2" =>  "row42",
        "col3" =>  "row3",
        "col4" =>  "row4"
    ],
];

ob_start();
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
			<?php }, $arHeaders); ?>
		</tr>
	</thead>
	<tbody>
	    <? foreach ( $arItems as $item) { ?>
            <tr>
                <?php array_map(function($header) use ($item) { ?>
                <td>
                    <p style="margin-top: 4.8pt; margin-bottom: 4.8pt;">
                        <span style="font-family: 'Segoe UI Light'; font-size: 8pt;">
                            <?=( $item[$header['id']] ?? '')?>
                        </span>
                    </p>
                </td>
                <?php }, $arHeaders); ?>
            </tr>
        <? } ?>
	</tbody>
</table>

<?

$exportData = ob_get_contents();
ob_end_clean();

echo $exportData;
die;


// _______________ #THIRD_TABLE END _______________________________________________________________________________


$modifiedHtml = $dom->saveHTML();

echo $modifiedHtml;
die;

// Установка опций Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans'); // Устанавливаем шрифт по умолчанию

$dompdf = new Dompdf($options);
$dompdf->loadHtml($modifiedHtml);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="generated.pdf"');

echo $dompdf->output();

exit;

