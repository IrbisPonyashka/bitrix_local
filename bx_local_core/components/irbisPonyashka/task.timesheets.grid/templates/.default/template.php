<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
    die();
}
?>

<?php


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
	'CJSTask',
]);

global $APPLICATION;

Asset::getInstance()->addJs("/bitrix/js/tasks/task-iframe-popup.js");
$APPLICATION->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

Extension::load([
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

 Asset::getInstance()->addCss('/bitrix/css/main/grid/webform-button.css');
 Asset::getInstance()->addJs('https://code.jquery.com/jquery-3.6.0.min.js');
 Asset::getInstance()->addJs('https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.9/jquery.inputmask.min.js');
 Asset::getInstance()->addJs('/local/templates/micros/assets/libs/js/moment.min.js');
?>


<? 
if( !empty($arResult["FILTER"]) ){
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.filter',
        '',
        array( 
            'FILTER_ID' => 'timesheets_grid', 
            'GRID_ID' => 'timesheets_grid', 
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
        'GRID_ID' => 'timesheets_grid', 
        'HEADERS' => $arResult["COLUMNS"], 
        'ROWS' => $arResult["ROWS"], 

        'SHOW_ROW_CHECKBOXES' => true, 
        'NAV_OBJECT' => $arResult["NAV"], 
        'DEFAULT_PAGE_SIZE' => 10,
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

		'AJAX_MODE'           => 'Y',
		//Strongly required
		"AJAX_OPTION_JUMP"    => "N",
		"AJAX_OPTION_STYLE"   => "N",
		"AJAX_OPTION_HISTORY" => "N",

		"ALLOW_COLUMNS_SORT"      => true,
		"ALLOW_ROWS_SORT"         => $arResult['CAN']['SORT'],
		"ALLOW_COLUMNS_RESIZE"    => true,
		"ALLOW_HORIZONTAL_SCROLL" => true,
		"ALLOW_SORT"              => true,
		"ALLOW_PIN_HEADER"        => true,
		'ALLOW_CONTEXT_MENU'      => true,
		"ACTION_PANEL"            => $arResult['GROUP_ACTIONS'],

		"SHOW_CHECK_ALL_CHECKBOXES" => true,
		"SHOW_ROW_CHECKBOXES"       => true,
		"SHOW_ROW_ACTIONS_MENU"     => true,
		"SHOW_GRID_SETTINGS_MENU"   => true,
		"SHOW_NAVIGATION_PANEL"     => true,
		"SHOW_PAGINATION"           => true,
		"SHOW_SELECTED_COUNTER"     => true,
		"SHOW_TOTAL_COUNTER"        => true,
		"SHOW_PAGESIZE"             => true,
		"SHOW_ACTION_PANEL"         => true,
		"SHOW_MORE_BUTTON"			=> true,
        'SHOW_GROUP_EDIT_BUTTON'    => true,

		"ENABLE_COLLAPSIBLE_ROWS" => true,
    )
);
?>

<script>
	BX.ready(() => {

	})
</script>

<script>
	BX.ready(
		function() {
			BX.Tasks.GridActions.gridId = '<?=$arParams['GRID_ID']?>';
			BX.Tasks.GridActions.defaultPresetId = '<?=$arResult['DEFAULT_PRESET_KEY']?>';
			BX.Tasks.GridActions.tagsAreConverting = '<?=$tagsAreConverting?>';

			BX.Tasks.GridInstance = new BX.Tasks.Grid(<?=Json::encode([
				'gridId' => $arParams['GRID_ID'],
				'userId' => $arResult['USER_ID'],
				'ownerId' => $arResult['OWNER_ID'],
				'groupId' => (int)$arParams['GROUP_ID'],
				'sorting' => $arResult['SORTING'],
				'groupByGroups' => ($arResult['GROUP_BY_PROJECT'] ? 'true' : 'false'),
				'groupBySubTasks' => ($arResult['GROUP_BY_SUBTASK'] ? 'true' : 'false'),
				'taskList' => $arResult['LIST'],
				'arParams' => $arParams,
				'calendarSettings' => $arResult['CALENDAR_SETTINGS'],
				'lastGroupId' => $arResult['LAST_GROUP_ID'],
				'migrationBarOptions' => [
					'title' => Loc::getMessage('TASKS_GRID_STUB_MIGRATION_TITLE'),
					'buttonMigrate' => Loc::getMessage('TASKS_GRID_STUB_MIGRATION_BUTTON_MIGRATE'),
					'other' => Loc::getMessage('TASKS_GRID_STUB_MIGRATION_OTHER'),
					'items' => [
						"{$templateFolder}/images/tasks-projects-jira.svg",
						"{$templateFolder}/images/tasks-projects-asana.svg",
						"{$templateFolder}/images/tasks-projects-trello.svg",
					],
				],
			])?>);
			new BX.Tasks.Grid.Sorting({
				gridId: '<?=$arParams['GRID_ID']?>',
				currentGroupId: <?=intval($arParams['GROUP_ID'])?>,
				treeMode: <?=($arParams["NEED_GROUP_BY_SUBTASKS"] === "Y") ? "true" : "false"?>,
				messages: {
					TASKS_ACCESS_DENIED: "<?=GetMessageJS("TASKS_ACCESS_DENIED")?>"
				}
			});
            

			BX.Tasks.TourGuideController = new BX.Tasks.TourGuideController(<?=
				Json::encode([
					'gridId' => $arParams['GRID_ID'],
					'userId' => $arResult['USER_ID'],
					'tours' => [
						'firstGridTaskCreation' => [
							'show' => false,
							'popupData' => [],
						],
						'expiredTasksDeadlineChange' => [
							'show' => false,
							'popupData' => [],
							'backgroundCheck' => false,
						],
					],
				])
			?>);
		}
	);

</script>