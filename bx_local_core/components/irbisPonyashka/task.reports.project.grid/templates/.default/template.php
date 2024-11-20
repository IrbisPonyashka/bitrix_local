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
            'FILTER_ID' => 'reports_project_grid', 
            'GRID_ID' => 'reports_project_grid', 
            'FILTER' => $arResult["FILTER"],
            'ENABLE_LIVE_SEARCH' => true, 
            'ENABLE_LABEL' => true 
        )
    );       
}
?>

<?
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid', 
    '', 
    [ 
        'GRID_ID' => 'reports_project_grid', 
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