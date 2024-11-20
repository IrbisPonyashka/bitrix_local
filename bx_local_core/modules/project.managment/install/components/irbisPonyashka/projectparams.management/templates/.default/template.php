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

//Asset::getInstance()->addCss('/bitrix/css/main/grid/webform-button.css');
//Asset::getInstance()->addCss($templateFolder.'/style.css');
//echo '<pre>'; print_r($projectsTableMap); echo '</pre>';
?>

<?php
    \Bitrix\UI\Toolbar\Facade\Toolbar::addFilter(array(
        'FILTER_ID' => 'project_params_filter',
        'GRID_ID' => 'project_params_list',
        'FILTER' => $arResult["FILTER_COLUMNS"],
        'ENABLE_LIVE_SEARCH' => true,
        'ENABLE_LABEL' => true
    ));
?>
<?php
    $linkButton = new \Bitrix\UI\Buttons\Button(
        array(
            "color" => \Bitrix\UI\Buttons\Color::SUCCESS,
            "click" => new \Bitrix\UI\Buttons\JsCode("BX.SidePanel.Instance.open(\"/bitrix/components/micros/projectparams.management/templates/.default/elements/sidepanel.php?action=add\",
                { 
                    width: 800,
                    Title: \"Создание конфигурации\",
                    allowChangeHistory: true,
                    requestMethod: \"post\",
                    requestParams: { // post-параметры
                        RESPONSE: \"add\",
                        MAP: ". json_encode($arResult["MAP"]) .",
                    },
                    events: {
                        onLoad: (e) => {
                            const sliderDocument = e.slider.iframe.contentWindow.document; 
                       }
                    }
                })" //произвольный код, который будет выполнен при клике на кнопку
            ),
            "text" => "Добавить"
        )
    );
    \Bitrix\UI\Toolbar\Facade\Toolbar::addButton($linkButton);
?>

<?php
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    array(
        'GRID_ID' => 'project_params_list',
        'COLUMNS' => $arResult["COLUMNS"],
        'ROWS' => $arResult["ROWS"],
        'NAV_OBJECT' => $arResult["NAV"],
        'AJAX_MODE' => 'Y',
        'AJAX_OPTION_JUMP' => 'N',
        'AJAX_OPTION_HISTORY' => 'N',
        // "FILTER" => $filter,
        "ALLOW_SORT" => true,
        'SHOW_PAGESIZE' => true
    )
);
?>




