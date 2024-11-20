<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
    die();
}
?>

<?php

use Bitrix\Main;
use Bitrix\Main\UI;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\Recyclebin\Task;
use Bitrix\Tasks\Integration\Socialnetwork\Context\Context;
use Bitrix\Tasks\Slider\Exception\SliderException;
use Bitrix\Tasks\Slider\Factory\SliderFactory;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\Tasks\Update\TagConverter;

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var string $templateFolder
*/

 CJSCore::Init([
	'clipboard',
	'sidepanel',
	'documentgenerator',
	'tasks_integration_socialnetwork',
	'CJSTask'
 ]);

global $APPLICATION;

 Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/tasks/task-iframe-popup.js");
 $APPLICATION->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");
 
Extension::load([
    'ui.sidepanel-content',
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.counter',
	'ui.entity-selector',
	'ui.icons.b24',
	'ui.label',
	'ui.migrationbar',
	'ui.tour',
	'tasks.runtime',
	'tasks.task-model',
]);

 Bitrix\Main\Loader::includeModule('transformer');
 Bitrix\Main\Loader::includeModule('documentgenerator');

 Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/socialnetwork/common/socialnetwork.common.min.css?17135162224300');
 Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/grid/webform-button.css');
 Bitrix\Main\Page\Asset::getInstance()->addJs('https://code.jquery.com/jquery-3.6.0.min.js');
 Bitrix\Main\Page\Asset::getInstance()->addJs('https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.9/jquery.inputmask.min.js');
 Bitrix\Main\Page\Asset::getInstance()->addJs('/local/templates/micros/assets/libs/js/moment.min.js');
?>

<? 
if( !empty($arResult["FILTER"]) ){
    
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.filter',
        '',
        array( 
            'FILTER_ID' => 'reports_general_grid', 
            'GRID_ID' => 'reports_general_grid', 
            'FILTER' => $arResult["FILTER"],
            'ENABLE_LIVE_SEARCH' => true, 
            'ENABLE_LABEL' => true ,
        )
    );       
}
?>
<div class="upload-button-container" style="margin-top: 18px;margin-bottom: 18px;display: flex;justify-content: end;">
    <button id="pdf-upload-button" class="ui-btn ui-btn-primary">
        Скачать в PDF
    </button>
    <button id="excl-upload-button" class="ui-btn ui-btn-success">
        Скачать в Excel
    </button>
</div>

<?

$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid', 
    '', 
    [ 
        'GRID_ID' => 'reports_general_grid', 
        'HEADERS' => $arResult["COLUMNS"], 
        'ROWS' => $arResult["ROWS"], //Самое интересное, опишем ниже
        'SHOW_ROW_CHECKBOXES' => true, 
        'NAV_OBJECT' => $arResult["NAV"], 
        'DEFAULT_PAGE_SIZE' => 10,
        'AJAX_MODE' => 'Y', 
        'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''), 
        'PAGE_SIZES' => [ 
            ['NAME' => "5", 'VALUE' => '5'], 
            ['NAME' => '10', 'VALUE' => '10'], 
            ['NAME' => '20', 'VALUE' => '20'], 
            ['NAME' => '50', 'VALUE' => '50'], 
            ['NAME' => '100', 'VALUE' => '100'] 
        ], 
        "CURRENT_PAGE"				=> $arResult['CURRENT_PAGE'],
        "NAV_PARAM_NAME"			=> $arResult['NAV_PARAM_NAME'],
        "TOTAL_ROWS_COUNT"    		=> $arResult['TOTAL_ROWS_COUNT'],

        'AJAX_OPTION_JUMP'          => 'N', 
        'SHOW_CHECK_ALL_CHECKBOXES' => true, 
        'SHOW_ROW_ACTIONS_MENU'     => true, 
        'SHOW_GRID_SETTINGS_MENU'   => true, 
        'SHOW_NAVIGATION_PANEL'     => true, 
        'SHOW_PAGINATION'           => true, 
        'SHOW_SELECTED_COUNTER'     => true, 
        'SHOW_TOTAL_COUNTER'        => true, 
        'SHOW_PAGESIZE'             => true, 
        'SHOW_ACTION_PANEL'         => true,
        'SHOW_GROUP_EDIT_BUTTON'         => true,
        'ACTION_PANEL'              => $arResult["GROUP_ACTIONS"], 
        'ALLOW_COLUMNS_SORT'        => true, 
        'ALLOW_COLUMNS_RESIZE'      => true, 
        'ALLOW_HORIZONTAL_SCROLL'   => true, 
        'ALLOW_SORT'                => true, 
        'ALLOW_PIN_HEADER'          => true, 
        'AJAX_OPTION_HISTORY'       => 'N' 
    ]
);
?>

<script type="text/javascript">
    var reloadParams = { apply_filter: 'Y', clear_nav: 'Y' };
    console.log(BX.Main.gridManager.getById('reports_general_grid_table'));
    // var gridObject = BX.Main.gridManager.getById('reports_general_grid'); // Идентификатор грида
    // console.log( JSON.parse('<?=json_encode($arResult["COLUMNS"])?>') );

    BX.addCustomEvent('Grid::updated', (event) => {
        // location.reload();    
    });
    BX.addCustomEvent('BX.Main.Filter:apply', (event) => {
        // location.reload();    
    });

    BX.ready( () => {
        var parentBottomBlockNode = document.querySelector('#reports_general_grid_bottom_panels')
        
        if (parentBottomBlockNode) {
            var rightBlockNode = parentBottomBlockNode.querySelector('.main-grid-cell-right');
            var rightBlockNodeParent = rightBlockNode.parentNode;
            var tdNodeBlock = document.createElement("td");
                tdNodeBlock.className="main-grid-panel-total main-grid-panel-cell main-grid-cell-left";
                tdNodeBlock.innerHTML = `<div class="main-grid-panel-content">
                                <span class="main-grid-panel-content-title">Общее Согласованное время:</span>&nbsp; <span class="main-grid-panel-content-text"><?=$arResult["AGREED_TIME_SUM"]["CONVERTED"]?></span>
                            </div>`;


            rightBlockNodeParent.insertBefore(tdNodeBlock, rightBlockNode);
        }

        var uploadPdfButton = document.querySelector('#pdf-upload-button');
        uploadPdfButton?.addEventListener('click', (e) => {
            e.preventDefault();

            if(document.querySelector('#reports_general_grid_table')){
                const table = document.querySelector('#reports_general_grid_table');
                if (table) {

                    const myHeaders = new Headers();
                    myHeaders.append("Content-Type", "application/json");

                    // columns: <?= json_encode($arResult["COLUMNS"]) ?>,
                    // rows: <?= json_encode($arResult["ROWS"]) ?>,
                    
                    const raw = JSON.stringify({});
                    const requestOptions = {
                        method: "POST",
                        headers: myHeaders,
                        body: raw,
                        redirect: "follow"
                    };

                    fetch("/local/components/micros/task.reports.general.grid/templates/.default/pdf_converter.php", requestOptions)
                        .then((response) => response.blob())
                        .then((result) => {
                            console.log(result);
                            const url = window.URL.createObjectURL(result);

                            // Создаем элемент <a> для скачивания
                            const a = document.createElement("a");
                            a.href = url;
                            a.download = "report.pdf"; // Имя скачиваемого файла
                            document.body.appendChild(a);
                            a.click();

                            // Удаляем элемент <a> после скачивания
                            a.remove();
                            window.URL.revokeObjectURL(url);
                        })
                        .catch((error) => console.error('Error:', error));
                }
            }
        });
        
        var uploadExcButton = document.querySelector('#excl-upload-button');
        uploadExcButton?.addEventListener('click', (e) => {
            e.preventDefault();
            const myHeaders = new Headers();
            myHeaders.append("Content-Type", "application/json");

            const raw = JSON.stringify({
                // columns: <?= json_encode($arResult["COLUMNS"]) ?>,
                // rows: <?= json_encode($arResult["ROWS"]) ?>,
                // tableHtml: tableHtml 
            });
            const requestOptions = {
                method: "POST",
                headers: myHeaders,
                body: raw,
                redirect: "follow"
            };

            fetch("/local/components/micros/task.reports.general.grid/templates/.default/xlsx_converter.php", requestOptions)
                .then((response) => response.blob())
                .then((blob) => {
                    console.log(blob);
                    const url = window.URL.createObjectURL(blob); // Создаем ссылку на объект Blob
                    const a = document.createElement("a"); // Создаем элемент <a>
                    a.href = url; // Устанавливаем URL в качестве ссылки
                    a.download = "data.xlsx"; // Указываем имя файла для скачивания
                    document.body.appendChild(a); // Добавляем элемент <a> в DOM
                    a.click(); // Имитируем клик для скачивания
                    a.remove(); // Удаляем элемент <a> после скачивания
                    window.URL.revokeObjectURL(url); // Освобождаем память, удаляя объект URL
                })
                .catch((error) => console.error('Error:', error));
        });
    });

    
</script>


<div class="ui-side-panel-toolbar">
    <div class="ui-side-panel-wrap-title-wrap" style="">
        <div class="ui-side-panel-wrap-title-inner-container">
        <div class="ui-side-panel-wrap-title-menu ui-side-panel-wrap-title-last-item-in-a-row"></div>
            <div class="ui-side-panel-wrap-title">
                <div class="ui-side-panel-wrap-title-box">
                    <span id="pagetitle" class="ui-side-panel-wrap-title-item">
                        <span class="ui-side-panel-wrap-title-name-item ui-side-panel-wrap-title-name">Затраты</span>
                        <span class="ui-side-panel-wrap-title-edit-button" style="display: none;"></span>
                        <input type="text" class="ui-side-panel-wrap-title-item ui-side-panel-wrap-title-input" style="display: none;">
                    </span>
                    <span class="ui-side-panel-wrap-subtitle-box">
                        <span class="ui-side-panel-wrap-subtitle-item"></span>
                        <span class="ui-side-panel-wrap-subtitle-control"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<? 

//echo '<pre>'; print_r($_SESSION); echo '</pre>';
?>

<div id="ui-page-slider-content expenses" class="ui-side-panel-content">
    <?
		$APPLICATION->IncludeComponent(
			"micros:crm.item.list",
			"sidepanel",
			Array(
				"id" => 5,
				"entityTypeId" => 190,
				'TAB_ID' => 'smart_process_tab',
				'COMPONENT_TEMPLATE' => '.default',
				'ENABLE_FILTER' => 'Y',
				'SHOW_FILTER' => 'Y',
                'FILTER' => array(
                    "=ASSIGNED_BY_ID" => 1,  // Пример фильтра по ID ответственного
                    ">DATE_CREATE" => '01.01.2024'  // Пример фильтра по дате
                ),
			)
		);
    ?>
</div>

<?
// $rsUser = CUser::GetByID($_REQUEST["EMPLOYEE_ID"]);
// $employee = $rsUser->Fetch();
// $text = $employee["LAST_NAME"]." ".$employee["NAME"]." ".$employee["SECOND_NAME"]." за период с ".date("d.m.Y",strtotime($_REQUEST["START"]))." по ".date("d.m.Y",strtotime($_REQUEST["END"]));

$APPLICATION->SetTitle("Общий отчёт"); 
?>
