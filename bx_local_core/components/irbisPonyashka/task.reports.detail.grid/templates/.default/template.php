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

 Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/socialnetwork/common/socialnetwork.common.min.css?17135162224300');
 Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/grid/webform-button.css');
 Bitrix\Main\Page\Asset::getInstance()->addJs('https://code.jquery.com/jquery-3.6.0.min.js');
 Bitrix\Main\Page\Asset::getInstance()->addJs('https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.9/jquery.inputmask.min.js');
 Bitrix\Main\Page\Asset::getInstance()->addJs('/local/templates/micros/assets/libs/js/moment.min.js');

?>

<?

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [ 
    'GRID_ID' => 'reports_detail_grid', 
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
]);
?>

<script type="text/javascript">
    var reloadParams = { apply_filter: 'Y', clear_nav: 'Y' };
    var gridObject = BX.Main.gridManager.getById('reports_detail_grid'); // Идентификатор грида

    BX.ready( (event) => {
        var parentBottomBlockNode = document.querySelector('#reports_detail_grid_bottom_panels');
        if (parentBottomBlockNode) {
            var rightBlockNode = parentBottomBlockNode.querySelector('.main-grid-cell-right');
            var rightBlockNodeParent = rightBlockNode.parentNode;
            var tdNodeBlock = document.createElement("td");
                tdNodeBlock.className="main-grid-panel-total main-grid-panel-cell main-grid-cell-left";
                tdNodeBlock.innerHTML = `<div class="main-grid-panel-content">
                                <span class="main-grid-panel-content-title">Всего внесено:</span>&nbsp; <span class="main-grid-panel-content-text" id="EMPLOYEY_TIME_SUM_TEXT"><?=$arResult["EMPLOYEY_TIME_SUM"]["CONVERTED"]?></span>
                            </div>`;
            var tdNodeBlockNext = document.createElement("td");
                tdNodeBlockNext.className="main-grid-panel-total main-grid-panel-cell main-grid-cell-left";
                tdNodeBlockNext.innerHTML = `<div class="main-grid-panel-content">
                                <span class="main-grid-panel-content-title">Всего согласовано:</span>&nbsp; <span class="main-grid-panel-content-text" id="AGREED_TIME_SUM_TEXT"><?=$arResult["AGREED_TIME_SUM"]["CONVERTED"]?></span>
                            </div>`;


            rightBlockNodeParent.insertBefore(tdNodeBlockNext, rightBlockNode);
            rightBlockNodeParent.insertBefore(tdNodeBlock, tdNodeBlockNext);
        }
        
        var docuementPageTitle = document.querySelector(".ui-side-panel-wrap-title");
        if(docuementPageTitle){
            docuementPageTitleSpan = docuementPageTitle.querySelector(".ui-side-panel-wrap-title-box");
            docuementPageTitleStatus = docuementPageTitleSpan.cloneNode(true);
            
            // docuementPageTitleStatus.querySelector("span.ui-side-panel-wrap-title-name-item.ui-side-panel-wrap-title-name").innerHTML = "Статус - ";
            docuementPageTitleStatus.querySelector("span.ui-side-panel-wrap-title-name-item.ui-side-panel-wrap-title-name").innerHTML = "<?= $_REQUEST["STATUS"] == 0 ? "Ожидает подтверждения" : ($_REQUEST["STATUS"] == 1 ? "Подтвержден" : "Не принят") ?>";
            
            docuementPageTitle.append(docuementPageTitleStatus);
            docuementPageTitle.style = "justify-content:space-between;"
        }
    });
   
    BX.addCustomEvent('Grid::updated', function(event) {
        let time = 0;
        for (const key in event.arParams.EDITABLE_DATA) {
            if (Object.hasOwnProperty.call(event.arParams.EDITABLE_DATA, key)) {
                const timeField = event.arParams.EDITABLE_DATA[key];
                time += timeField.MAIN_AGREED_TIME ? Number(timeField.MAIN_AGREED_TIME) : 0;
            }
        }
        time = secondsToHHMM(time);
        document.querySelector('#AGREED_TIME_SUM_TEXT').innerHTML = time;
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

<div id="ui-page-slider-content expenses" class="ui-side-panel-content">
    <?$APPLICATION->IncludeComponent(
        "bitrix:crm.item.list",
        "sidepanel",
        Array(
            "id" => 5,
            "entityTypeId" => 190
        )
    );?>
</div>

<?
$rsUser = CUser::GetByID($_REQUEST["EMPLOYEE_ID"]);
$employee = $rsUser->Fetch();
$text = $employee["LAST_NAME"]." ".$employee["NAME"]." ".$employee["SECOND_NAME"]." за период с ".date("d.m.Y",strtotime($_REQUEST["START"]))." по ".date("d.m.Y",strtotime($_REQUEST["END"]));

$APPLICATION->SetTitle("Отчёт сотрудника - ". $text); 
?>
