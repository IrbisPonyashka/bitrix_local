<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
    die();
}
?>

<?php
use Bitrix\Main;
use Bitrix\Main\UI;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var string $templateFolder
 */

 Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/grid/webform-button.css');
 
?>


<? 
if( !empty($arResult["FILTER"]) ){
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.filter',
        '',
        array( 
            'FILTER_ID' => 'reports_grid', 
            'GRID_ID' => 'reports_grid', 
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
    Array(
        'GRID_ID' => 'reports_grid', 
        'COLUMNS' => $arResult["COLUMNS"], 
        'ROWS' => $arResult["ROWS"], //Самое интересное, опишем ниже
        'SHOW_ROW_CHECKBOXES' => true, 
        'NAV_OBJECT' => $arResult["NAV"], 
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
    )
);

?>

<script type="text/javascript">
    var reloadParams = { apply_filter: 'Y', clear_nav: 'Y' };
    var gridObject = BX.Main.gridManager.getById('reports_grid'); // Идентификатор грида

    gridObject?.instance.reloadTable('POST', reloadParams);
</script>