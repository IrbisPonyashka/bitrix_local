<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** Название модуля */
$module = basename(__DIR__);

/** Подгружаем классы */
\Bitrix\Main\Loader::registerAutoLoadClasses($module, array(
    "IrbisPonyashka\Harvest\HarvestProjectsTable" => "lib/m_harvest_projects.php",
));

/** Подключаем обработчики для событий */
// require_once __DIR__ . '/handlers.php';
?>
