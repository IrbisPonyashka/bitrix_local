<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
\Bitrix\Main\Loader::includeModule('tasks');

$eventManager = \Bitrix\Main\EventManager::getInstance();

// Добавление затраченного времени
$eventManager->addEventHandler('tasks', 'OnTaskElapsedTimeAdd', [
    '\IrbisPonyashka\Events\OnTasks\taskHandler',
    'onTaskElapsedTimeAdd'
]);

// Обновление затраченного времени
$eventManager->addEventHandler('tasks', 'OnTaskElapsedTimeUpdate', [
    '\IrbisPonyashka\Events\OnTasks\taskHandler',
    'onTaskElapsedTimeUpdate'
]);

// Удаление затраченного времени
$eventManager->addEventHandler('tasks', 'OnTaskElapsedTimeDelete', [
    '\IrbisPonyashka\Events\OnTasks\taskHandler',
    'onTaskElapsedTimeDelete'
]);

// Удаление задачи
$eventManager->addEventHandler('tasks', 'OnTaskDelete', [
    '\IrbisPonyashka\Events\OnTasks\taskHandler',
    'onTaskDelete'
]);

// Перед обновлнением задачи
$eventManager->addEventHandler('tasks', 'OnBeforeTaskUpdate', [
    '\IrbisPonyashka\Events\OnTasks\taskHandler',
    'onBeforeTaskUpdate'
]);

// Перед удалением задачи
$eventManager->addEventHandler('tasks', 'OnBeforeTaskDelete', [
    '\IrbisPonyashka\Events\OnTasks\taskHandler',
    'onBeforeTaskDelete'
]);


// $eventManager->addEventHandler('crm', 'onBeforeCrmDynamicItemUpdate', [
//     '\IrbisPonyashka\Events\OnDynamicItems\DynamicItemHandler',
//     'onBeforeCrmDynamicItemUpdate'
// ]);