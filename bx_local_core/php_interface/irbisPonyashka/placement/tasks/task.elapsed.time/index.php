<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\UI;
use \Bitrix\Tasks\Helper\Analytics;
use \Bitrix\Tasks\Helper\RestrictionUrl;
use \Bitrix\Tasks\Integration\Intranet\Settings;
use \Bitrix\Tasks\Internals\Task\MetaStatus;
use \Bitrix\Tasks\Internals\Task\Priority;
use \Bitrix\Tasks\Slider\Path\TaskPathMaker;
use \Bitrix\Tasks\Util;

// use Bitrix\Tasks\Internals\Task\ElapsedTimeTable;

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->RestartBuffer(); //сбрасываем весь вывод

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

$taskId = json_decode($_REQUEST["PLACEMENT_OPTIONS"],1)["taskId"];

$rsTask  = CTasks::GetByID( $taskId, true);
$arTask = $rsTask->GetNext();
if($arTask)
{
    $cTaskElapsedTime = CTaskElapsedTime::GetList(
        Array(), 
        Array("TASK_ID" => $taskId)
    );
    $arElapsedTimes = [];
    while ($arElapsed = $cTaskElapsedTime->Fetch())
    {
        $arElapsedTimes[] = $arElapsed;
    }
    
    // return;
    $asset = \Bitrix\Main\Page\Asset::getInstance();

    UI\Extension::load([
        'ui.design-tokens',
        'ui.fonts.opensans',
        'ui.analytics',
        'tasks.comment-action-controller',
        'tasks.analytics',
    ]);
    ?>
    <!DOCTYPE html>
    <html>
        <head>
            <?$APPLICATION->ShowHead(); ?>
            <title><? $APPLICATION->ShowTitle() ?></title>
            <?
                // $asset->addCss(SITE_TEMPLATE_PATH . '/assets/libs/css/all.min.css');
            ?>
            <style>
                .task-detail-page .workarea-content-paddings{
                    padding: 0;
                }
    
                .task-detail {
                    font-family: var(--ui-font-family-primary, var(--ui-font-family-helvetica));
                }
    
                .task-detail-info {
                    background-color: #fff;
                    margin: 0 0 20px 0;
                    border-radius: var(--ui-border-radius-md);
                }
    
                .template-bitrix24 .task-detail-info {
                    margin: 0;
                    border-bottom-right-radius: 0;
                    border-bottom-left-radius: 0;
                }
    
                .task-info-panel-important {
                    order: 1;
                }
    
                .task-info-panel-important span {
                    position: relative;
                    display: inline-block;
                    vertical-align: middle;
                    font-size: 13px;
                    color: rgba(0, 0, 0, 1);
                    padding: 0 20px;
                    opacity: .9;
                }
    
                .task-info-panel-important:hover span {
                    opacity: 1 !important;
                }
    
                .task-info-panel-important span:after {
                    content: '';
                    position: absolute;
                    right: 0;
                    top: 0;
                    bottom: 0;
                    margin: auto;
                    background: url(/bitrix/js/tasks/css/images/media.png) no-repeat 0 -122px;
                    width: 12px;
                    height: 16px;
                }
    
                .task-info-panel-important.no span {
                    opacity: .7;
                    color: gray;
                }
    
                .task-info-panel-important.no span:after {
                    background-position: 0 -102px;
                }
    
                .task-info-panel-important.mutable span {
                    cursor: pointer;
                }
    
                .task-info-panel-important .if-no {
                    display: none;
                }
    
                .task-info-panel-important.no .if-no {
                    display: inline-block;
                }
    
                .task-info-panel-important.no .if-not-no {
                    display: none;
                }
    
                .task-detail-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 11px 13px 12px 20px;
                    border-bottom: 1px solid #e9eaec;
                }
    
                .task-detail-header-title {
                    color: #000;
                    font: 22px/24px var(--ui-font-family-secondary, var(--ui-font-family-open-sans));
                    font-weight: var(--ui-font-weight-regular, 400);
                    padding-right: 10px;
                    word-wrap: break-word;
                }
    
                .task-detail-subtitle-status {
                    font: 15px/24px var(--ui-font-family-secondary, var(--ui-font-family-open-sans));
                    font-weight: var(--ui-font-weight-regular, 400);
                    color: #525c69;
                    vertical-align: middle;
                }
    
                .task-detail-subtitle-status-delay-message {
                    color: #f93000;
                    font-weight: normal;
                }
    
                .task-detail-content {
                    position: relative;
                    padding: 2px 23px 0;
                    color: #151515;
                    font-family: var(--ui-font-family-primary, var(--ui-font-family-helvetica));
                    font-size: 14.5px;
                    line-height: var(--ui-font-line-height-lg, 22px);
                    overflow: hidden;
                }
    
                .task-detail-content > div:last-child {
                    border-bottom: none;
                }
    
                .task-detail-author-info {
                    display: none;
                }
    
                .task-detail-favorite {
                    cursor: pointer;
                    position: absolute;
                    width: 0;
                    height: 0;
                    border: 46px solid transparent;
                    border-right: none;
                    top: -30px;
                    right: -7px;
                    transition: all .2s linear;
                    z-index: 1;
                    -webkit-transform: rotate(-45deg);
                    -moz-transform: rotate(-45deg);
                    -ms-transform: rotate(-45deg);
                    -o-transform: rotate(-45deg);
                    transform: rotate(-45deg);
                    background: transparent;
                    opacity: .5;
                }
    
                .task-detail-favorite:hover {
                    border-left: 46px solid #e3eaed;
                    background: #e3eaed;
                    opacity: 1;
                }
    
                .task-detail-favorite-active,
                .task-detail-favorite-active:hover {
                    border-left: 46px solid #f9a800;
                    background: #f9a800;
                }
    
                .task-detail-favorite-star {
                    position: relative;
                    display: block;
                    width: 0;
                    height: 0;
                    border-right: 8px solid transparent;
                    border-top: 6px solid #7e8690;
                    border-left: 8px solid transparent;
                    top: -2px;
                    left: -37px;
                    margin: auto;
                    -webkit-transform: rotate(45deg);
                    -moz-transform: rotate(45deg);
                    -ms-transform: rotate(45deg);
                    -o-transform: rotate(45deg);
                    transform: rotate(45deg);
                }
    
                .task-detail-favorite-star:before {
                    position: absolute;
                    display: block;
                    top: -6px;
                    left: -8px;
                    width: 0;
                    height: 0;
                    border-right: 8px solid transparent;
                    border-top: 6px solid #7e8690;
                    border-left: 8px solid transparent;
                    -webkit-transform: rotate(-71deg);
                    -moz-transform: rotate(-71deg);
                    -ms-transform: rotate(-71deg);
                    -o-transform: rotate(-71deg);
                    transform: rotate(-71deg);
                    content: '';
                    z-index: 4;
                }
    
                .task-detail-favorite-star:after {
                    position: absolute;
                    display: block;
                    top: -6px;
                    left: -8px;
                    width: 0;
                    height: 0;
                    border-right: 8px solid transparent;
                    border-top: 6px solid #7e8690;
                    border-left: 8px solid transparent;
                    -webkit-transform: rotate(71deg);
                    -moz-transform: rotate(71deg);
                    -ms-transform: rotate(71deg);
                    -o-transform: rotate(71deg);
                    transform: rotate(71deg);
                    content: "";
                }
    
                .task-detail-favorite-active .task-detail-favorite-star,
                .task-detail-favorite-active .task-detail-favorite-star:before,
                .task-detail-favorite-active .task-detail-favorite-star:after {
                    border-top: 6px solid #fff;
                }
    
                .task-detail-description,
                .task-detail-checklist,
                .task-detail-files,
                .task-detail-extra,
                .task-detail-properties {
                    padding: 15px 0;
                    border-bottom: 1px solid rgba(234, 235, 237, .78);
                }
    
                .task-detail-description {
                    word-wrap: break-word;
                    padding-bottom: 30px;
                    min-height: 80px;
                    overflow-x: auto;
                }
    
                .task-detail-description-only {
                    border-bottom: none;
                }
    
                .task-detail-content .task-checklist-field-edit .task-checklist-field-inner {
                    max-width: 455px;
                }
    
                .task-detail-content input.task-checklist-field-add {
                    max-width: 100%;
                    box-sizing: border-box;
                }
    
                .task-detail-files {
                    /*margin-bottom: 15px;*/
                    padding-top: 20px;
                }
    
                .task-detail-title {
                    display: inline-block;
                    color: #535c69;
                    font-size: 14px;
                    font-weight: var(--ui-font-weight-bold);
                    line-height: 18px;
                    margin: 0 8px 0 0;
                    padding: 0;
                }
    
                .task-detail-checkstatus {
                    font-size: 13px;
                    color: rgba(83, 92, 105, .7);
                    font-weight: normal;
                }
    
                .task-detail-subtitle {
                    position: relative;
                    color: rgba(83, 92, 105, .6);
                    font-weight: var(--ui-font-weight-bold);
                    font-size: 9px;
                    text-transform: uppercase;
                    margin: 15px 5px 5px;
                }
    
                .task-detail-subtitle span {
                    cursor: pointer;
                }
    
                .task-detail-resolved .task-detail-subtitle span:before {
                    content: '';
                    height: 0;
                    border: 3px solid transparent;
                    border-left-color: #c6cacf;
                    border-right: none;
                    display: inline-block;
                    vertical-align: middle;
                    margin: -2px 0 0 2px;
                    width: 4px;
                }
    
                .task-detail-resolved-open .task-detail-subtitle span:before {
                    content: '';
                    width: 0;
                    height: 0;
                    border: 3px solid transparent;
                    border-top: 3px solid #c6cacf;
                    border-bottom: none;
                    display: inline-block;
                    vertical-align: middle;
                    margin: -3px 3px 0 0;
                }
    
                .task-detail-resolved .task-detail-field {
                    display: none;
                }
    
                .task-detail-resolved-open .task-detail-field {
                    display: block;
                }
    
                .task-detail-field {
                    position: relative;
                    margin-bottom: 1px;
                    overflow: hidden;
                }
    
                input.task-detail-field-add {
                    border: 1px solid #c6cdd3;
                    outline: none;
                    line-height: 28px;
                    color: #535c69;
                    font-size: 14px;
                    padding: 0 6px;
                    width: 298px;
                    margin: 10px 15px 5px 25px;
                    float: left;
                    height: 28px;
                    border-radius: 2px;
                    top: auto;
                }
    
                .task-dashed-link {
                    display: inline-block;
                    color: #1f67b0;
                    cursor: pointer;
                }
    
                .task-detail-field-add + .task-dashed-link {
                    line-height: 30px;
                    margin: 10px 0 5px;
                    overflow: hidden;
                    display: block;
                }
    
                .task-dashed-link-inner {
                    font-size: 13px;
                    border-bottom: 1px dashed rgba(31, 103, 176, .5);
                    cursor: pointer;
                }
    
                .task-dashed-link .task-dashed-link-inner:hover {
                    border-bottom: 1px dashed rgba(31, 103, 176, 1);
                }
    
                .task-detail-label {
                    display: inline-block;
                    font-size: 14px;
                    color: #535c69;
                    vertical-align: middle;
                    line-height: 18px;
                }
    
                .task-field-checkbox + .task-detail-label {
                    overflow: hidden;
                    display: block;
                }
    
                .task-field-checkbox:checked + .task-detail-label {
                    text-decoration: line-through;
                }
    
                .task-detail-content-main .task-dashed-link {
                    padding-left: 30px;
                    margin-bottom: 20px;
                    text-decoration: underline;
                }
    
                .task-detail-img {
                    display: inline-block;
                    vertical-align: top;
                    width: 75px;
                    height: 75px;
                    border: 1px solid #eeeeed;
                    background: #fff;
                    overflow: hidden;
                }
    
                .task-detail-file {
                    display: inline-block;
                    overflow: visible;
                }
    
                .task-detail-file-name {
                    color: #2067b0;
                    cursor: pointer;
                    font-weight: var(--ui-font-weight-bold);
                    border-bottom: 1px solid transparent;
                    transition: border-bottom-color .2s linear;
                }
    
                .task-detail-file-name:hover {
                    border-bottom: 1px solid;
                }
    
                .task-detail-file-size {
                    color: #7e838c;
                    font-size: 12px;
                    font-weight: normal;
                    margin: 0 0 0 4px;
                    vertical-align: middle;
                }
    
                .task-detail-file-more {
                    border-bottom: 1px solid transparent;
                    display: inline-block;
                    color: #999;
                    cursor: pointer;
                    font-size: 11px;
                    margin-right: 19px;
                    position: relative;
                }
    
                .task-detail-file-more:after {
                    content: '';
                    position: absolute;
                    border: 3px solid transparent;
                    border-top-color: #a3a6ab;
                    border-bottom: none;
                    width: 0;
                    height: 0;
                    top: 0;
                    bottom: 0;
                    margin: auto auto auto 4px;
                    opacity: .7;
                }
    
                .task-detail-file-more:hover {
                    text-decoration: underline;
                }
    
                .task-detail-file-more:hover:after {
                    opacity: 1;
                }
    
                .task-detail-extra {
                    display: flex;
                    align-items: flex-start;
                }
    
                .task-detail-extra.--flex-wrap {
                    flex-wrap: wrap;
                }
    
                .task-detail-extra-right {
                    display: flex;
                    align-items: center;
                    margin-left: auto;
                }
    
                .task-detail-extra .feed-post-emoji-top-panel-outer {
                    display: inline-flex;
                    padding-bottom: 0;
                    padding-left: 9px;
                }
    
                .task-detail-extra .feed-post-emoji-top-panel-box.feed-post-emoji-top-panel-container-active {
                    display: inline-flex;
                    padding-bottom: 0;
                }
    
                .task-detail-extra .feed-post-emoji-top-panel-container-active.feed-post-emoji-top-panel-box {
                    max-height: 22px;
                }
    
                .task-detail-extra .feed-post-emoji-container-nonempty .feed-post-emoji-icon-container {
                    min-width: auto;
                }
    
                .task-detail-group-member-selector {
                    display: inline-flex;
                }
    
                .task-detail-group.--flex-center {
                    display: inline-flex;
                    align-items: center;
                    flex-wrap: wrap;
                }
    
                .task-detail-group.--flex-center > span {
                    display: inline-flex;
                }
    
                .task-detail-group-label {
                    margin-right: 5px;
                }
    
                .task-detail-group-loader {
                    display: none;
                    vertical-align: middle;
                }
    
                .mode-loading .task-detail-group-loader {
                    display: inline-block;
                }
    
                .mode-loading .task-dashed-link {
                    display: none;
                }
    
                .task-group-field {
                    position: relative;
                    display: inline-block;
                    vertical-align: middle;
                    margin: 0;
                }
    
                .group-item-set-empty-false .task-dashed-link {
                    display: none;
                }
    
                .task-group-field-inner {
                    display: inline-block;
                    position: relative;
                }
    
                .task-detail-group-wrap .task-group-field-inner {
                    padding-right: 22px;
                    margin-right: 20px;
                }
    
                .task-group-field-label {
                    display: inline-block;
                    font-size: 14px;
                    vertical-align: unset;
                    line-height: unset;
                    color: #2067b0;
                    border-bottom: 1px solid transparent;
                    transition: border-bottom-color .2s linear;
                }
    
                .task-group-field-inner a.task-group-field-label {
                    display: unset;
                }
    
                .task-group-field-inner .task-group-field-title-del {
                    position: relative;
                    display: none;
                    vertical-align: middle;
                    margin-top: 2px;
                    width: 20px;
                    height: 16px;
                    top: auto;
                    right: auto;
                    cursor: pointer;
                    opacity: 0.5;
                }
    
                .task-group-field:hover .task-group-field-title-del {
                    display: inline-block;
                }
    
                .task-detail-group-wrap .task-group-field-inner .task-group-field-title-del {
                    position: absolute;
                    top: 4px;
                    right: 0;
                    margin-top: 0;
                }
    
                .task-group-field-title-del:hover {
                    opacity: 1;
                }
    
                .task-group-field-title-del:before {
                    content: '';
                    background: url(/bitrix/js/tasks/css/images/media.png) no-repeat 0 -14px;
                    position: absolute;
                    top: 0;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    margin: auto;
                    width: 13px;
                    height: 12px;
                }
    
                .task-group-field:hover .task-group-field-title-del {
                    display: inline-block;
                }
    
                .task-detail-group-link {
                    border-bottom: 1px solid transparent;
                    transition: border-bottom-color .2s linear;
                }
    
                .task-detail-group-link:hover {
                    border-bottom: 1px solid;
                }
    
                .task-detail-supertask-label,
                .task-detail-supertask-name {
                    display: inline-block;
                    vertical-align: middle;
                    margin-right: 5px;
                }
    
                .task-detail-extra .task-detail-supertask {
                    margin-top: 0;
                    min-width: 100%;
                }
    
                .task-detail-properties-layout a:hover {
                    border-bottom: 1px solid;
                }
    
                .task-detail-property-name {
                    text-align: left;
                }
    
                .task-detail-like.--flex {
                    display: inline-flex;
                    z-index: 9;
                }
    
                .task-detail-like .feed-inform-ilike .bx-you-like-button a {
                    color: #0b66c3;
                }
    
                .task-detail-like .feed-inform-ilike a:hover {
                    color: #3A3D42;
                    text-decoration: none;
                }
    
                .task-detail-like .feed-inform-ilike.feed-new-like {
                    display: inline-flex;
                    align-items: center;
                    margin-right: 0;
                }
    
                .task-detail-like .ilike-light .bx-ilike-right {
                    background-position: 0 8px;
                    font-size: 13px;
                    padding-left: 14px !important;
                }
    
                .task-detail-like .ilike-light .bx-you-like .bx-ilike-right {
                    background-position: 0 8px;
                    font-size: 13px;
                    padding-left: 14px !important;
                }
    
                .task-detail-like a {
                    text-decoration: none;
                    font-size: 13px;
                }
    
                .task-detail-contentview {
                    padding-left: 10px;
                }
    
                .task-detail-buttons {
                    padding: 13px 15px 15px;
                    text-align: left;
                    border-top: 1px solid #eef2f4;
                    background: #fff;
                    line-height: 50px;
                    margin-bottom: var(--ui-space-stack-md, 15px);
                    border-radius: var(--ui-border-radius-md);
                    border-top-right-radius: 0;
                    border-top-left-radius: 0;
                }
    
                .task-button-edit-link {
                    color: #393939;
                    font-size: 14px;
                }
    
                .webform-button-link,
                .webform-button-link:hover {
                    color: #5e636b;
                }
    
                .webform-small-button-transparent .webform-small-button-text {
                    display: inline-block;
                    position: relative;
                    vertical-align: middle;
                }
    
                .webform-small-button-transparent .webform-small-button-text:after {
                    content: '';
                    width: 0;
                    height: 0;
                    display: inline-block;
                    vertical-align: middle;
                    margin-left: 7px;
                    border: 3px solid transparent;
                    border-top: 3px solid;
                }
    
                .task-detail-list {
                    background: #fff;
                    padding: 20px;
                    overflow-x: auto;
                    border-radius: var(--ui-border-radius-md);
                    margin-bottom: var(--ui-space-stack-md, 15px);
                }
    
                .task-detail-list-title {
                    color: #535c69;
                    font-size: 14px;
                    font-weight: var(--ui-font-weight-bold);
                    margin: 0 0 20px;
                }
    
                #sidebar {
                    width: 300px !important;
                }
    
                .task-detail-sidebar-content {
                    background: #fff;
                    padding: 0 16px 16px;
                    color: #535c69;
                    font: 13px var(--ui-font-family-primary, var(--ui-font-family-helvetica));
                    border-radius: var(--ui-border-radius-md);
                }
    
                .task-detail-sidebar-status {
                    background: #56d1e0;
                    padding: 7px 15px 8px;
                    position: relative;
                    margin: 0 -16px;
                    min-height: 32px;
                    border-radius: var(--ui-border-radius-md);
                    border-bottom-right-radius: 0;
                    border-bottom-left-radius: 0;
                }
    
                .task-detail-sidebar-status-content {
                    position: absolute;
                    background: #56d1e0;
                    left: 0;
                    right: 0;
                    top: 50%;
                    padding: 0 15px;
                    -webkit-transform: translateY(-50%);
                    -moz-transform: translateY(-50%);
                    -ms-transform: translateY(-50%);
                    -o-transform: translateY(-50%);
                    transform: translateY(-50%);
                    font-weight: var(--ui-font-weight-bold);
                    color: #fff;
                }
    
                .task-detail-sidebar-status-date {
                    font-weight: normal;
                    white-space: nowrap;
                }
    
                .task-detail-sidebar-item {
                    padding: 15px 0 17px;
                    border-bottom: 1px solid #eef2f4;
                    overflow: hidden;
                }
    
                .task-detail-sidebar-item-title {
                    font-size: 13px;
                    color: #858c96;
                    width: 98px;
                    float: left;
                }
    
                .task-detail-sidebar-item-value {
                    color: #000;
                    margin-left: 100px;
                    padding-left: 5px;
                }
    
                .task-detail-sidebar-item-value span {
                    border-bottom: 1px dashed;
                    cursor: pointer;
                    display: inline-block;
                }
    
                .task-detail-sidebar-item-readonly span {
                    border: none;
                    cursor: text;
                }
    
                .task-detail-sidebar-item-value .task-detail-sidebar-item-mark-plus,
                .task-detail-sidebar-item-value .task-detail-sidebar-item-mark-p {
                    color: #15c945;
                }
    
                .task-detail-sidebar-item-value .task-detail-sidebar-item-mark-minus,
                .task-detail-sidebar-item-value .task-detail-sidebar-item-mark-n {
                    color: #ff4200;
                }
    
                .task-detail-sidebar-item-value .task-detail-sidebar-item-value-del {
                    width: 16px;
                    height: 12px;
                    background: url(/bitrix/js/tasks/css/images/media.png) no-repeat 5px -410px;
                    border-bottom: none;
                    vertical-align: middle;
                    display: none;
                    opacity: .3;
                }
    
                .task-detail-sidebar-item-value-del:hover {
                    opacity: .7;
                }
    
                .task-detail-sidebar-item-deadline .task-detail-sidebar-item-value:hover .task-detail-sidebar-item-value-del {
                    display: inline-block;
                }
    
                .task-detail-sidebar-item-deadline .task-detail-sidebar-item-value span {
                    font-weight: var(--ui-font-weight-bold);
                }
    
                .task-detail-sidebar-item-delay {
                    padding: 8px 0 0;
                    border-top: 1px solid #eef2f4;
                    margin: 15px auto -7px;
                }
    
                .task-detail-sidebar-item-delay-message {
                    font-size: 13px;
                    font-weight: var(--ui-font-weight-bold);
                    line-height: 20px;
                    color: #f73100;
                    padding: 5px 8px;
                    background: #ffe5e0;
                    border-radius: var(--ui-border-radius-xs);
                }
    
                .task-detail-sidebar-item-delay-message-icon {
                    position: relative;
                    display: inline-block;
                    vertical-align: middle;
                    border: 8px solid transparent;
                    border-bottom: 15px solid #f73100;
                    border-top: none;
                    margin-top: -2px;
                }
    
                .task-detail-sidebar-item-delay-message-icon:before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -10px;
                    width: 16px;
                    height: 15px;
                    border-radius: 50%;
                    border: 2px solid #ffe5e0;
                }
    
                .task-detail-sidebar-item-delay-message-icon:after {
                    content: '';
                    position: absolute;
                    top: 6px;
                    left: -1px;
                    right: 0;
                    margin: auto;
                    width: 2px;
                    height: 4px;
                    background: #fff;
                    box-shadow: 0 2px 0 0 #f73100, 0 3px 0 #fff;
                    opacity: 0.9;
                }
    
                .task-detail-sidebar-item-delay-message-text {
                    display: inline-block;
                    vertical-align: middle;
                }
    
                .task-detail-sidebar-item-reminder .task-detail-sidebar-item-value:before {
                    content: '';
                    width: 10px;
                    height: 12px;
                    background: url(/bitrix/js/tasks/css/images/media.png) no-repeat 0 -38px;
                    display: inline-block;
                    vertical-align: middle;
                    margin-right: 5px;
                }
    
                .task-detail-sidebar-info-title {
                    margin-top: 15px;
                    color: #525c69;
                    border-bottom: 1px solid #edeef0;
                }
    
                .task-detail-sidebar-info-title-line {
                    border-bottom: 1px solid #f0f1f2;
                }
    
                .task-detail-sidebar-info {
                    padding: 10px 0;
                    line-height: 18px;
                    word-wrap: break-word;
                }
    
                .task-detail-sidebar-info-title.hide {
                    display: none;
                }
    
                .task-detail-sidebar-info.hide {
                    display: none;
                }
    
                .task-detail-sidebar-info a:hover {
                    border-bottom: 1px solid;
                }
    
                .task-detail-sidebar-info-tag {
                    font-size: 14px;
                    color: #5e6676;
                }
    
                .task-detail-sidebar-info-tag .task-tags-line {
                    display: block;
                }
    
                .task-tags-line {
                    word-wrap: break-word;
                    word-break: break-all;
                }
    
                .task-detail-sidebar-info-link {
                    font-size: 12px;
                    color: #8d949c;
                    float: right;
                    line-height: 34px;
                    cursor: pointer;
                }
    
                .task-detail-sidebar-content .webform-small-button {
                    display: block;
                    margin: 13px auto 0;
                    width: 185px;
                    text-align: center;
                }
    
                .task-detail-sidebar-info-users-list {
                    margin-top: 4px;
                }
    
                .task-detail-sidebar-info-users-list .task-detail-sidebar-info-user {
                    background: none;
                }
    
                .task-detail-sidebar-info-users-list .task-dashed-link {
                    font-size: 12px;
                    color: #8d949c;
                    margin-left: 12px;
                }
    
                .task-detail-sidebar-info-users-list .task-dashed-link-inner {
                    border-bottom: 1px dashed rgba(141, 148, 156, .5);
                }
    
                .task-detail-sidebar-info-users-list .task-dashed-link-inner:hover {
                    border-bottom: 1px dashed rgba(141, 148, 156, 1);
                }
    
                .task-detail-sidebar-info-user-more {
                    font-size: 14px;
                    font-weight: var(--ui-font-weight-bold);
                    color: #535c69;
                    line-height: 39px;
                    text-align: center;
                    width: 39px;
                    height: 39px;
                    border-radius: 50%;
                    background: #eef2f4;
                    float: left;
                    margin: 15px 10px 0 0;
                }
    
                .task-detail-sidebar-info.template-source {
                    margin-top: 20px;
                }
    
                .task-detail-sidebar-placement {
                    margin-top: var(--ui-space-stack-md);
                }
    
                .task-comments-log-switcher {
                    margin: 0;
                    white-space: nowrap;
                    position: relative;
                    bottom: -1px;
                }
    
                .template-bitrix24 .task-comments-log-switcher {
                    bottom: 0;
                }
    
                .task-switcher {
                    display: inline-block;
                    cursor: pointer;
                    margin: 0;
                    border-bottom: none;
                }
    
                .task-switcher-selected {
                    cursor: default;
                }
    
                .template-bitrix24 .task-switcher-selected {
                    border: none;
                }
    
                .task-switcher-text {
                    display: inline-block;
                    padding: 3px 12px;
                    font: normal 12px/33px var(--ui-font-family-primary, var(--ui-font-family-helvetica));
                    color: #5e6675;
                    font-size: 14px;
                    line-height: 30px;
                    height: 30px;
                    background: #e0e5e9;
                    border-radius: var(--ui-border-radius-xs, 3px);
                    border-bottom-right-radius: 0;
                    border-bottom-left-radius: 0;
                    transition: all 220ms linear;
                    position: relative;
                    border: 1px solid transparent;
                }
    
                .task-switcher-text:hover,
                .task-switcher-selected .task-switcher-text {
                    color: #5c6470;
                    background: #fff;
                    border: 1px solid #eef2f4;
                    border-bottom: 1px solid #fff;
                }
    
                .template-bitrix24 .task-switcher-text:hover,
                .template-bitrix24 .task-switcher-selected .task-switcher-text {
                    border: 1px solid transparent;
                    border-bottom: 1px solid #fff;
                }
    
                .template-bitrix24 .task-switcher-text:hover {
                    background: #fff;
                }
    
    
                .task-switcher-text-counter {
                    background: #d2d7dc;
                    border-radius: 8px;
                    height: 16px;
                    vertical-align: middle;
                    display: inline-block;
                    color: #535c69;
                    font: normal 11px/16px var(--ui-font-family-primary, var(--ui-font-family-helvetica));
                    text-align: center;
                    padding: 0 7px;
                    box-sizing: border-box;
                    min-width: 20px;
                    transition: all 220ms linear;
                }
    
                .task-switcher-text:hover .task-switcher-text-counter,
                .task-switcher-selected .task-switcher-text-counter {
                    background-color: #ebf1f4;
                }
    
                .task-detail-properties {
                    /*border-top: 1px solid #e7e6d6;*/
                    /*margin: 20px 0 0;*/
                    padding-top: 20px;
                }
    
                .task-detail-property-name {
                    text-align: right;
                    vertical-align: top;
                    padding: 0 10px 9px 0;
                    color: #535c69;
                    font-size: 14px;
                    font-weight: var(--ui-font-weight-bold);
                    width: 30%;
                }
    
                .task-detail-property-value {
                    vertical-align: top;
                    padding: 0 0 9px;
                    width: 70%;
                }
    
                .task-detail-property-value div.fields {
                    margin-bottom: 1px;
                }
    
                .task-detail-property-value .separator {
                    border: none;
                    margin: 0;
                    width: auto;
                }
    
                .task-detail-property-value .separator:before {
                    content: ', ';
                }
    
                .task-detail-property-image {
                    border: 1px solid #eee;
                    border-radius: 1px;
                    box-shadow: 0 0 1px #eee, inset 0 0 1px #eee;
                    display: inline-block;
                    margin: 0 8px 8px 0;
                    padding: 3px;
                }
    
                .task-comments-and-log .feed-comments-block {
                    margin: 0;
                }
    
                .task-comments-and-log .feed-com-corner {
                    display: none;
                }
    
                .task-switcher-block {
                    display: none;
                    margin: 0;
                    background: #fff;
                    padding: 11px;
                    border-radius: var(--ui-border-radius-md);
                    border-top-left-radius: 0;
                }
    
                .task-switcher-block-selected {
                    display: block;
                    border: 1px solid #eef2f4;
                }
    
                .template-bitrix24 .task-switcher-block-selected {
                    border: none;
                }
    
                .task-files-block {
                    padding: 15px;
                }
    
                .task-time-table th {
                    background-color: #e5e5e5;
                    text-align: left;
                    padding: 5px 11px;
                }
    
                .task-log-date-column,
                .task-log-author-column,
                .task-log-where-column,
                .task-log-what-column,
                .task-time-date-column,
                .task-time-author-column,
                .task-time-spent-column,
                .task-time-comment-column {
                    font-size: 13px;
                    vertical-align: top;
                    border-bottom: 1px solid #edeef0;
                    padding: 13px 7px;
                    word-wrap: break-word;
                }
    
                .task-time-table tr:last-child td,
                .task-log-table tr:last-child td,
                .task-time-add-link-row td {
                    border: none;
                }
    
                th.task-log-date-column,
                th.task-log-author-column,
                th.task-log-where-column,
                th.task-log-what-column,
                th.task-time-date-column,
                th.task-time-author-column,
                th.task-time-spent-column,
                th.task-time-comment-column {
                    vertical-align: middle;
                    height: 39px;
                    padding: 0 7px;
                    background: #f0f5f6;
                    color: rgba(94, 102, 117, .8);
                    border: none;
                    overflow: hidden;
                    white-space: nowrap;
                    text-overflow: ellipsis;
                    text-align: left;
                    font-weight: var(--ui-font-weight-bold);
                }
    
                .task-log-table, .task-time-table {
                    min-width: 100%;
                    table-layout: fixed;
                    color: #333;
                    border-collapse: collapse;
                }
    
                .task-log-date-column {
                    width: 16%;
                }
    
                .task-log-author-column {
                    width: 24%;
                }
    
                .task-log-where-column {
                    width: 20%;
                }
    
                .task-log-what-column {
                    width: 40%;
                }
    
                .task-time-date-column {
                    width: 13%;
                }
    
                .task-time-author-column {
                    width: 24%;
                }
    
                .task-time-spent-column {
                    width: 20%;
                }
    
                .task-time-comment-column {
                    width: 43%;
                }
    
                .task-log-what, .task-time-comment {
                    color: rgba(94, 102, 117, .8);
                }
    
                .task-log-arrow, .task-time-arrow {
                    color: #000;
                    font-size: 14px;
                    margin: 0 .5em;
                }
    
                .task-time-table-manually .task-time-table-note-img {
                    background: url(/bitrix/js/tasks/css/images/media.png) no-repeat 0 -141px;
                    width: 16px;
                    height: 16px;
                    display: inline-block;
                    vertical-align: middle;
                }
    
                .task-time-table-unknown .task-time-table-note-img {
                    background: url(/bitrix/js/tasks/css/images/media.png) no-repeat 0 -160px;
                    width: 16px;
                    height: 16px;
                    display: inline-block;
                    vertical-align: middle;
                }
    
                .task-table-edit,
                .task-table-remove,
                .task-table-edit-ok,
                .task-table-edit-remove {
                    height: 12px;
                    width: 14px;
                    opacity: .5;
                    cursor: pointer;
                    background: url(/bitrix/js/tasks/css/images/media.png) no-repeat;
                    display: none;
                }
    
                .task-table-edit {
                    background-position: 0 0;
                }
    
                .task-table-remove {
                    background-position: 4px -14px;
                }
    
                .task-table-edit:hover,
                .task-table-remove:hover,
                .task-table-edit-ok:hover,
                .task-table-edit-remove:hover {
                    opacity: 1;
                }
    
                .task-time-table-remove:hover .task-table-remove,
                .task-time-table-edit:hover .task-table-edit {
                    display: inline-block;
                }
    
                .task-table-edit-ok {
                    display: inline-block;
                    background-position: 0 -25px;
                }
    
                .task-table-edit-remove {
                    display: inline-block;
                    background-position: 4px -14px;
                }
    
                .task-log-author-column .task-log-author:hover,
                .task-time-author-column .task-log-author:hover {
                    border-bottom: 1px solid;
                    text-decoration: none;
                }
    
                .task-log-author-text {
                    display: none;
                }
    
                .task-time-table-public .task-log-author-text {
                    display: inline;
                }
    
                .task-time-table-public .task-log-author {
                    display: none;
                }
    
                .task-log-table .task-log-what a:hover {
                    border-bottom: 1px solid;
                    text-decoration: none;
                }
    
                .task-log-date, .task-time-date {
                    color: #999;
                }
    
                .task-log-date {
                    word-wrap: normal;
                }
    
                .task-log-arrow,
                .task-time-arrow {
                    font-size: 14px;
                    margin: 0 .5em;
                }
    
                .task-time-comment-container {
                    position: relative;
                    margin-right: 40px;
                }
    
                .task-time-comment-action {
                    display: block;
                    height: 12px;
                    top: 3px;
                    margin: auto;
                    right: -43px;
                    position: absolute;
                }
    
                .task-time-form-row .task-time-comment-action {
                    top: 6px;
                }
    
                .task-time-field-container {
                    margin: 0 15px 0 -15px;
                }
    
                .task-time-field-textbox {
                    height: 28px;
                    line-height: 28px;
                    padding: 0 5px;
                    border: 1px solid #c6cdd3;
                    font-size: 13px;
                    color: #000;
                    border-radius: var(--ui-field-border-radius);
                }
    
                .task-time-date-column input {
                    width: 100%;
                }
    
                .task-time-spent-column input {
                    width: 15px;
                }
    
                .task-time-comment-column input {
                    width: 100%;
                    box-sizing: border-box;
                }
    
                .task-time-spent-hours {
                    white-space: nowrap;
                }
    
                .task-time-spent-minutes {
                    white-space: nowrap;
                }
    
                .task-message-label {
                    font-size: 14px;
                    color: #5e6675;
                    background: #ebf5b5;
                    line-height: 17px;
                    padding: 11px 15px;
                    position: relative;
                    margin-bottom: 10px;
                }
    
                .task-options-field-warning,
                .task-message-label.error {
                    background: #fee7e7;
                }
    
                .task-options-field-warning:before,
                .task-message-label.error:before {
                    content: '';
                    width: 16px;
                    height: 16px;
                    background: url(/bitrix/js/tasks/css/images/media.png) no-repeat 0 -232px;
                    float: left;
                    margin-right: 10px;
                    margin-top: 2px;
                }
    
                .task-detail-checklist {
                    padding-bottom: 15px;
                }
    
                .task-iframe-get-link-btn{
                    color: #7f8792;
                    font: normal 13px/17px var(--ui-font-family-primary, var(--ui-font-family-helvetica));
                    border-bottom: 1px dashed;
                    vertical-align: middle;
                    padding-bottom: 2px;
                    transition: all 300ms linear;
                    line-height: 23px;
                }
    
                .task-iframe-get-link-btn:hover{
                    cursor: pointer;
                    color: #525c69;
                }
    
                table.data-table td {
                    border: 1px solid #efefef !important;
                }
    
                .task-page__mobile-divider {
                    display: inline-block;
                    margin: 1px 17px 0 0;
                    width: 1px;
                    height: 14px;
                    background-color: var(--ui-color-palette-gray-20);
                    vertical-align: middle;
                }
    
                .task-page__mobile {
                    position: relative;
                    display: inline-block;
                    margin-top: 1px;
                    font-size: 12px;
                    line-height: initial;
                    vertical-align: middle;
                }
    
                .task-page__mobile_name,
                .task-page__mobile_value {
                    display: inline-block;
                }
    
                .task-page__mobile_name {
                    position: relative;
                    padding-left: 15px;
                    margin-right: 4px;
                    color: var(--ui-color-palette-gray-50);
                }
    
                .task-page__mobile_name:before {
                    content: '';
                    left: 0;
                    position: absolute;
                    top: 1px;
                    width: 8px;
                    height: 13px;
                    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='13' fill='none'%3E%3Cpath fill='%23BDC1C6' fill-rule='evenodd' d='M6.588.11H1.411C.778.11.294.57.294 1.172v9.906c0 .566.484 1.061 1.117 1.061h5.177c.634 0 1.118-.495 1.118-1.061V1.172C7.706.57 7.222.11 6.588.11ZM4 11.786a.71.71 0 0 1-.708-.708A.71.71 0 0 1 4 10.37a.71.71 0 0 1 .707.708.71.71 0 0 1-.707.708Zm2.677-1.769H1.322V1.879h5.356l-.001 8.138Z' clip-rule='evenodd'/%3E%3C/svg%3E") no-repeat;
                }
    
                .task-page__mobile_value {
                    border-bottom: 1px dashed rgba(130, 139, 149, .4);
                    color: var(--ui-color-palette-gray-70);
                    transition: border-bottom .3s;
                    cursor: pointer;
                }
    
                .task-page__mobile_value:hover {
                    border-bottom: 1px dashed var(--ui-color-background-transparent);
                }
    
                /*=========================== main.post.list patches ======================*/
                .task-comments-block .feed-com-block,
                .task-comments-block .feed-com-add-box {
                    margin-left: 56px;
                }
    
                .task-comments-block .feed-com-add-box-outer .feed-com-avatar {
                    left: 12px;
                }
    
                .task-switcher-block.task-comments-block {
                    padding-top: 20px;
                    padding-right: 24px;
                }
                /*=========================== main.post.list patches ======================*/
    
                .task-footer-wrap {
                    visibility: collapse;
                }
    
                .task-footer-wrap.task-footer-wrap-active {
                    visibility: visible;
                }
    
            </style>
            <script src="//api.bitrix24.com/api/v1/"></script>
        </head>
        <body>
            <?
            $jsonarData = '{
                "TASK": {
                    "ID": "2",
                    "TITLE": "Тест",
                    "DESCRIPTION": "",
                    "DESCRIPTION_IN_BBCODE": "Y",
                    "DECLINE_REASON": "",
                    "PRIORITY": "1",
                    "NOT_VIEWED": "N",
                    "STATUS_COMPLETE": "2",
                    "REAL_STATUS": "5",
                    "MULTITASK": "N",
                    "STAGE_ID": "0",
                    "RESPONSIBLE_ID": "1",
                    "RESPONSIBLE_NAME": "Admin",
                    "RESPONSIBLE_LAST_NAME": "Admin",
                    "RESPONSIBLE_SECOND_NAME": "",
                    "RESPONSIBLE_LOGIN": "admin",
                    "RESPONSIBLE_WORK_POSITION": "",
                    "RESPONSIBLE_PHOTO": null,
                    "DATE_START": "13.06.2024 16:09:19",
                    "DURATION_FACT": "60",
                    "TIME_ESTIMATE": "0",
                    "TIME_SPENT_IN_LOGS": "3627",
                    "REPLICATE": "N",
                    "DEADLINE": "30.05.2024 14:00:00",
                    "DEADLINE_ORIG": "30.05.2024 14:00:00",
                    "START_DATE_PLAN": "29.05.2024 18:00:00",
                    "END_DATE_PLAN": "30.05.2024 03:00:00",
                    "CREATED_BY": "1",
                    "CREATED_BY_NAME": "Admin",
                    "CREATED_BY_LAST_NAME": "Admin",
                    "CREATED_BY_SECOND_NAME": "",
                    "CREATED_BY_LOGIN": "admin",
                    "CREATED_BY_WORK_POSITION": "",
                    "CREATED_BY_PHOTO": null,
                    "CREATED_DATE": "29.05.2024 14:53:48",
                    "CHANGED_BY": "1",
                    "CHANGED_DATE": "13.06.2024 16:09:42",
                    "STATUS_CHANGED_BY": "1",
                    "CLOSED_BY": "1",
                    "CLOSED_DATE": "13.06.2024 16:09:42",
                    "ACTIVITY_DATE": "13.06.2024 16:09:43",
                    "GUID": "{7518874c-499c-46f8-86d0-5d32a64e7b64}",
                    "XML_ID": null,
                    "MARK": null,
                    "ALLOW_CHANGE_DEADLINE": "Y",
                    "ALLOW_TIME_TRACKING": "Y",
                    "MATCH_WORK_TIME": "N",
                    "TASK_CONTROL": "N",
                    "ADD_IN_REPORT": "N",
                    "FORUM_TOPIC_ID": "1",
                    "PARENT_ID": "0",
                    "COMMENTS_COUNT": 8,
                    "SERVICE_COMMENTS_COUNT": "8",
                    "FORUM_ID": "8",
                    "SITE_ID": "s1",
                    "SUBORDINATE": "N",
                    "EXCHANGE_MODIFIED": null,
                    "EXCHANGE_ID": null,
                    "OUTLOOK_VERSION": "15",
                    "VIEWED_DATE": "24.06.2024 17:23:44",
                    "DEADLINE_COUNTED": "0",
                    "FORKED_BY_TEMPLATE_ID": null,
                    "SORTING": "1024.0000000",
                    "DURATION_PLAN_SECONDS": "32400",
                    "DURATION_TYPE_ALL": "days",
                    "SCENARIO_NAME": [
                        "default"
                    ],
                    "IS_REGULAR": "N",
                    "IS_MUTED": "N",
                    "IS_PINNED": "N",
                    "IS_PINNED_IN_GROUP": "N",
                    "UF_CRM_TASK": false,
                    "UF_MAIL_MESSAGE": null,
                    "UF_BILLABLE": null,
                    "STATUS": "5",
                    "STATUS_CHANGED_DATE": "13.06.2024 16:09:42",
                    "DURATION_PLAN": "0",
                    "DURATION_TYPE": "days",
                    "FAVORITE": "N",
                    "GROUP_ID": "0",
                    "AUDITORS": [],
                    "ACCOMPLICES": [],
                    "TAGS": [],
                    "CHECKLIST": [],
                    "FILES": [],
                    "DEPENDS_ON": [],
                    "ACTION": {
                        "ACCEPT": false,
                        "DECLINE": false,
                        "COMPLETE": false,
                        "APPROVE": false,
                        "DISAPPROVE": false,
                        "START": false,
                        "PAUSE": false,
                        "DELEGATE": false,
                        "REMOVE": true,
                        "EDIT": true,
                        "DEFER": false,
                        "RENEW": true,
                        "CREATE": true,
                        "CHANGE_DEADLINE": true,
                        "CHECKLIST_ADD_ITEMS": true,
                        "ADD_FAVORITE": true,
                        "DELETE_FAVORITE": false,
                        "RATE": true,
                        "EDIT.ORIGINATOR": false,
                        "CHECKLIST.REORDER": true,
                        "ELAPSEDTIME.ADD": true,
                        "DAYPLAN.TIMER.TOGGLE": false,
                        "EDIT.PLAN": true,
                        "CHECKLIST.ADD": true,
                        "FAVORITE.ADD": true,
                        "FAVORITE.DELETE": false,
                        "DAYPLAN.ADD": false,
                        "ADD_TO_DAY_PLAN": false,
                        "SORT": false
                    },
                    "SE_PARAMETER": [
                        {
                            "ID": "7",
                            "TASK_ID": "2",
                            "CODE": "1",
                            "VALUE": "N"
                        },
                        {
                            "ID": "8",
                            "TASK_ID": "2",
                            "CODE": "2",
                            "VALUE": "N"
                        },
                        {
                            "ID": "9",
                            "TASK_ID": "2",
                            "CODE": "3",
                            "VALUE": "N"
                        }
                    ],
                    "SE_ORIGINATOR": {
                        "ID": 1,
                        "NAME": "Admin",
                        "LAST_NAME": "Admin",
                        "SECOND_NAME": "",
                        "LOGIN": "admin",
                        "WORK_POSITION": "",
                        "PERSONAL_PHOTO": null,
                        "PERSONAL_GENDER": "",
                        "IS_EXTRANET_USER": false,
                        "IS_CRM_EMAIL_USER": false,
                        "IS_EMAIL_USER": false,
                        "IS_NETWORK_USER": false,
                        "NAME_FORMATTED": "Admin Admin"
                    },
                    "SE_RESPONSIBLE": [
                        {
                            "ID": 1,
                            "NAME": "Admin",
                            "LAST_NAME": "Admin",
                            "SECOND_NAME": "",
                            "LOGIN": "admin",
                            "WORK_POSITION": "",
                            "PERSONAL_PHOTO": null,
                            "PERSONAL_GENDER": "",
                            "IS_EXTRANET_USER": false,
                            "IS_CRM_EMAIL_USER": false,
                            "IS_EMAIL_USER": false,
                            "IS_NETWORK_USER": false
                        }
                    ],
                    "SE_ELAPSEDTIME": {
                        "3": {
                            "ID": "3",
                            "CREATED_DATE": "29.05.2024 14:54:00",
                            "DATE_START": "29.05.2024 05:54:26",
                            "DATE_STOP": "29.05.2024 05:54:26",
                            "USER_ID": "1",
                            "TASK_ID": "2",
                            "MINUTES": "60",
                            "SECONDS": "3600",
                            "SOURCE": "2",
                            "COMMENT_TEXT": "",
                            "USER_NAME": "Admin",
                            "USER_LAST_NAME": "Admin",
                            "USER_SECOND_NAME": "",
                            "USER_LOGIN": "admin"
                        },
                        "4": {
                            "ID": "4",
                            "CREATED_DATE": "29.05.2024 14:54:55",
                            "DATE_START": "29.05.2024 14:54:55",
                            "DATE_STOP": "29.05.2024 14:55:05",
                            "USER_ID": "1",
                            "TASK_ID": "2",
                            "MINUTES": "0",
                            "SECONDS": "10",
                            "SOURCE": "3",
                            "COMMENT_TEXT": "",
                            "USER_NAME": "Admin",
                            "USER_LAST_NAME": "Admin",
                            "USER_SECOND_NAME": "",
                            "USER_LOGIN": "admin"
                        },
                        "5": {
                            "ID": "5",
                            "CREATED_DATE": "13.06.2024 16:09:19",
                            "DATE_START": "13.06.2024 16:09:19",
                            "DATE_STOP": "13.06.2024 16:09:23",
                            "USER_ID": "1",
                            "TASK_ID": "2",
                            "MINUTES": "0",
                            "SECONDS": "4",
                            "SOURCE": "3",
                            "COMMENT_TEXT": "",
                            "USER_NAME": "Admin",
                            "USER_LAST_NAME": "Admin",
                            "USER_SECOND_NAME": "",
                            "USER_LOGIN": "admin"
                        },
                        "6": {
                            "ID": "6",
                            "CREATED_DATE": "13.06.2024 16:09:29",
                            "DATE_START": "13.06.2024 16:09:29",
                            "DATE_STOP": "13.06.2024 16:09:42",
                            "USER_ID": "1",
                            "TASK_ID": "2",
                            "MINUTES": "0",
                            "SECONDS": "13",
                            "SOURCE": "3",
                            "COMMENT_TEXT": "",
                            "USER_NAME": "Admin",
                            "USER_LAST_NAME": "Admin",
                            "USER_SECOND_NAME": "",
                            "USER_LOGIN": "admin"
                        }
                    },
                    "SE_PROJECTDEPENDENCE": [],
                    "IN_DAY_PLAN": true,
                    "TIME_ELAPSED": 3627,
                    "TIMER_IS_RUNNING_FOR_CURRENT_USER": false,
                    "SE_AUDITOR": [],
                    "SE_ACCOMPLICE": [],
                    "SE_PARENT": []
                },
                "GROUP": [],
                "USER": {
                    "1": {
                        "ID": "1",
                        "LOGIN": "admin",
                        "EMAIL": "dmitriy.karakov@micros.uz",
                        "ACTIVE": "Y",
                        "BLOCKED": "N",
                        "DATE_REGISTER": {},
                        "LAST_LOGIN": {},
                        "LAST_ACTIVITY_DATE": {},
                        "TIMESTAMP_X": {},
                        "NAME": "Admin",
                        "SECOND_NAME": "",
                        "LAST_NAME": "Admin",
                        "TITLE": "",
                        "EXTERNAL_AUTH_ID": null,
                        "XML_ID": "",
                        "BX_USER_ID": null,
                        "CONFIRM_CODE": null,
                        "LID": "s1",
                        "LANGUAGE_ID": "ru",
                        "TIME_ZONE": "",
                        "TIME_ZONE_OFFSET": "32400",
                        "PERSONAL_PROFESSION": "",
                        "PERSONAL_PHONE": "",
                        "PERSONAL_MOBILE": "",
                        "PERSONAL_WWW": "",
                        "PERSONAL_ICQ": "",
                        "PERSONAL_FAX": "",
                        "PERSONAL_PAGER": "",
                        "PERSONAL_STREET": "",
                        "PERSONAL_MAILBOX": "",
                        "PERSONAL_CITY": "",
                        "PERSONAL_STATE": "",
                        "PERSONAL_ZIP": "",
                        "PERSONAL_COUNTRY": "0",
                        "PERSONAL_BIRTHDAY": null,
                        "PERSONAL_GENDER": "",
                        "PERSONAL_PHOTO": null,
                        "PERSONAL_NOTES": "",
                        "WORK_COMPANY": "",
                        "WORK_DEPARTMENT": "",
                        "WORK_PHONE": "",
                        "WORK_POSITION": "",
                        "WORK_WWW": "",
                        "WORK_FAX": "",
                        "WORK_PAGER": "",
                        "WORK_STREET": "",
                        "WORK_MAILBOX": "",
                        "WORK_CITY": "",
                        "WORK_STATE": "",
                        "WORK_ZIP": "",
                        "WORK_COUNTRY": "0",
                        "WORK_PROFILE": "",
                        "WORK_LOGO": null,
                        "WORK_NOTES": "",
                        "ADMIN_NOTES": "",
                        "UF_DEPARTMENT": [
                            1
                        ],
                        "UF_USER_CRM_ENTITY": null,
                        "IS_EXTRANET_USER": false,
                        "IS_EMAIL_USER": false,
                        "IS_CRM_EMAIL_USER": false,
                        "IS_NETWORK_USER": false
                    }
                },
            }';
    
            $jsonArCan = `{
                "TASK": {
                    "ACTION": {
                        "ELAPSEDTIME.ADD": true,
                    },
                    "SE_ELAPSEDTIME": {
                        "3": {
                            "ACTION": {
                                "MODIFY": true,
                                "REMOVE": true
                            }
                        },
                        "4": {
                            "ACTION": {
                                "MODIFY": true,
                                "REMOVE": true
                            }
                        },
                        "5": {
                            "ACTION": {
                                "MODIFY": true,
                                "REMOVE": true
                            }
                        },
                        "6": {
                            "ACTION": {
                                "MODIFY": true,
                                "REMOVE": true
                            }
                        }
                    }
                }
            }`;
    
            // echo '<pre>'; print_r(json_decode($jsonArCan,1)); echo '</pre>';
            ?>
            <?
                $APPLICATION->IncludeComponent(
                    "micros:tasks.task.detail.parts",
                    "flat",
                    [
                        "MODE" => "VIEW TASK",
                        "BLOCKS" => ["time"],
                        "PATH_TO_TASKS_TASK" => "/company/personal/user/1/tasks/task/#action#/#task_id#/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER",
                        "PATH_TO_USER_PROFILE" => "/company/personal/user/#user_id#/",
                        "PATH_TO_GROUP" => "/workgroups/group/#group_id#/",
                        "NAME_TEMPLATE" => "#NAME# #LAST_NAME#",
                        "TASK_ID" => "2",
                        "PUBLIC_MODE" => "",
                        "TEMPLATE_DATA" => array(
                            "DATA" => json_decode($jsonarData,1),
                            "CAN" => json_decode($jsonArCan,1)
                        )
                    ],
                    false,
                    ["HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y"]
                );
            ?>
            <script>
                // BX24.resizeWindow(document.body.clientWidth, document.querySelector('#task-time-table').clientHeight);
            </script>
        </body>
    
    </html>
    
    <?
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");


// $jsonarData = '{"SCRUM":{"EPIC":[]},"CHECKLIST_CONVERTED":true,"STAGES":[],"TASK":{"ID":"2","TITLE":"Тест","DESCRIPTION":"","DESCRIPTION_IN_BBCODE":"Y","DECLINE_REASON":"","PRIORITY":"1","NOT_VIEWED":"N","STATUS_COMPLETE":"2","REAL_STATUS":"5","MULTITASK":"N","STAGE_ID":"0","RESPONSIBLE_ID":"1","RESPONSIBLE_NAME":"Admin","RESPONSIBLE_LAST_NAME":"Admin","RESPONSIBLE_SECOND_NAME":"","RESPONSIBLE_LOGIN":"admin","RESPONSIBLE_WORK_POSITION":"","RESPONSIBLE_PHOTO":null,"DATE_START":"13.06.2024 16:09:19","DURATION_FACT":"60","TIME_ESTIMATE":"0","TIME_SPENT_IN_LOGS":"3627","REPLICATE":"N","DEADLINE":"30.05.2024 14:00:00","DEADLINE_ORIG":"30.05.2024 14:00:00","START_DATE_PLAN":"29.05.2024 18:00:00","END_DATE_PLAN":"30.05.2024 03:00:00","CREATED_BY":"1","CREATED_BY_NAME":"Admin","CREATED_BY_LAST_NAME":"Admin","CREATED_BY_SECOND_NAME":"","CREATED_BY_LOGIN":"admin","CREATED_BY_WORK_POSITION":"","CREATED_BY_PHOTO":null,"CREATED_DATE":"29.05.2024 14:53:48","CHANGED_BY":"1","CHANGED_DATE":"13.06.2024 16:09:42","STATUS_CHANGED_BY":"1","CLOSED_BY":"1","CLOSED_DATE":"13.06.2024 16:09:42","ACTIVITY_DATE":"13.06.2024 16:09:43","GUID":"{7518874c-499c-46f8-86d0-5d32a64e7b64}","XML_ID":null,"MARK":null,"ALLOW_CHANGE_DEADLINE":"Y","ALLOW_TIME_TRACKING":"Y","MATCH_WORK_TIME":"N","TASK_CONTROL":"N","ADD_IN_REPORT":"N","FORUM_TOPIC_ID":"1","PARENT_ID":"0","COMMENTS_COUNT":8,"SERVICE_COMMENTS_COUNT":"8","FORUM_ID":"8","SITE_ID":"s1","SUBORDINATE":"N","EXCHANGE_MODIFIED":null,"EXCHANGE_ID":null,"OUTLOOK_VERSION":"15","VIEWED_DATE":"24.06.2024 17:23:44","DEADLINE_COUNTED":"0","FORKED_BY_TEMPLATE_ID":null,"SORTING":"1024.0000000","DURATION_PLAN_SECONDS":"32400","DURATION_TYPE_ALL":"days","SCENARIO_NAME":["default"],"IS_REGULAR":"N","IS_MUTED":"N","IS_PINNED":"N","IS_PINNED_IN_GROUP":"N","UF_CRM_TASK":false,"UF_MAIL_MESSAGE":null,"UF_BILLABLE":null,"STATUS":"5","STATUS_CHANGED_DATE":"13.06.2024 16:09:42","DURATION_PLAN":"0","DURATION_TYPE":"days","FAVORITE":"N","GROUP_ID":"0","AUDITORS":[],"ACCOMPLICES":[],"TAGS":[],"CHECKLIST":[],"FILES":[],"DEPENDS_ON":[],"ACTION":{"ACCEPT":false,"DECLINE":false,"COMPLETE":false,"APPROVE":false,"DISAPPROVE":false,"START":false,"PAUSE":false,"DELEGATE":false,"REMOVE":true,"EDIT":true,"DEFER":false,"RENEW":true,"CREATE":true,"CHANGE_DEADLINE":true,"CHECKLIST_ADD_ITEMS":true,"ADD_FAVORITE":true,"DELETE_FAVORITE":false,"RATE":true,"EDIT.ORIGINATOR":false,"CHECKLIST.REORDER":true,"ELAPSEDTIME.ADD":true,"DAYPLAN.TIMER.TOGGLE":false,"EDIT.PLAN":true,"CHECKLIST.ADD":true,"FAVORITE.ADD":true,"FAVORITE.DELETE":false,"DAYPLAN.ADD":false,"ADD_TO_DAY_PLAN":false,"SORT":false},"SE_PARAMETER":[{"ID":"7","TASK_ID":"2","CODE":"1","VALUE":"N"},{"ID":"8","TASK_ID":"2","CODE":"2","VALUE":"N"},{"ID":"9","TASK_ID":"2","CODE":"3","VALUE":"N"}],"SE_ORIGINATOR":{"ID":1,"NAME":"Admin","LAST_NAME":"Admin","SECOND_NAME":"","LOGIN":"admin","WORK_POSITION":"","PERSONAL_PHOTO":null,"PERSONAL_GENDER":"","IS_EXTRANET_USER":false,"IS_CRM_EMAIL_USER":false,"IS_EMAIL_USER":false,"IS_NETWORK_USER":false,"NAME_FORMATTED":"Admin Admin"},"SE_PARENTTASK":[],"SE_PROJECT":[],"SE_RESPONSIBLE":[{"ID":1,"NAME":"Admin","LAST_NAME":"Admin","SECOND_NAME":"","LOGIN":"admin","WORK_POSITION":"","PERSONAL_PHOTO":null,"PERSONAL_GENDER":"","IS_EXTRANET_USER":false,"IS_CRM_EMAIL_USER":false,"IS_EMAIL_USER":false,"IS_NETWORK_USER":false}],"SE_CHECKLIST":[],"SE_REMINDER":[],"SE_LOG":[{"ID":"33","CREATED_DATE":"13.06.2024 16:09:43","USER_ID":"1","TASK_ID":"2","FIELD":"COMMENT","FROM_VALUE":null,"TO_VALUE":"9","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"32","CREATED_DATE":"13.06.2024 16:09:42","USER_ID":"1","TASK_ID":"2","FIELD":"STATUS","FROM_VALUE":"3","TO_VALUE":"5","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"31","CREATED_DATE":"13.06.2024 16:09:42","USER_ID":"1","TASK_ID":"2","FIELD":"TIME_SPENT_IN_LOGS","FROM_VALUE":"3614","TO_VALUE":"3627","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"30","CREATED_DATE":"13.06.2024 16:09:23","USER_ID":"1","TASK_ID":"2","FIELD":"TIME_SPENT_IN_LOGS","FROM_VALUE":"3610","TO_VALUE":"3614","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"29","CREATED_DATE":"13.06.2024 16:09:19","USER_ID":"1","TASK_ID":"2","FIELD":"STATUS","FROM_VALUE":"2","TO_VALUE":"3","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"26","CREATED_DATE":"30.05.2024 14:02:30","USER_ID":"1","TASK_ID":"2","FIELD":"COMMENT","FROM_VALUE":null,"TO_VALUE":"8","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"22","CREATED_DATE":"29.05.2024 15:04:48","USER_ID":"1","TASK_ID":"2","FIELD":"DURATION_PLAN_SECONDS","FROM_VALUE":"","TO_VALUE":"32400","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"21","CREATED_DATE":"29.05.2024 15:04:48","USER_ID":"1","TASK_ID":"2","FIELD":"END_DATE_PLAN","FROM_VALUE":"","TO_VALUE":1717052400,"USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"20","CREATED_DATE":"29.05.2024 15:04:48","USER_ID":"1","TASK_ID":"2","FIELD":"START_DATE_PLAN","FROM_VALUE":"","TO_VALUE":1717020000,"USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"19","CREATED_DATE":"29.05.2024 15:04:45","USER_ID":"1","TASK_ID":"2","FIELD":"COMMENT","FROM_VALUE":null,"TO_VALUE":"7","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"18","CREATED_DATE":"29.05.2024 15:04:45","USER_ID":"1","TASK_ID":"2","FIELD":"DEADLINE","FROM_VALUE":1716984000,"TO_VALUE":1717092000,"USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"17","CREATED_DATE":"29.05.2024 15:04:41","USER_ID":"1","TASK_ID":"2","FIELD":"COMMENT","FROM_VALUE":null,"TO_VALUE":"6","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"16","CREATED_DATE":"29.05.2024 15:04:36","USER_ID":"1","TASK_ID":"2","FIELD":"COMMENT","FROM_VALUE":null,"TO_VALUE":"5","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"15","CREATED_DATE":"29.05.2024 15:04:36","USER_ID":"1","TASK_ID":"2","FIELD":"DEADLINE","FROM_VALUE":1717020000,"TO_VALUE":1716984000,"USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"14","CREATED_DATE":"29.05.2024 15:01:50","USER_ID":"1","TASK_ID":"2","FIELD":"COMMENT","FROM_VALUE":null,"TO_VALUE":"4","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"13","CREATED_DATE":"29.05.2024 15:01:50","USER_ID":"1","TASK_ID":"2","FIELD":"DEADLINE","FROM_VALUE":"","TO_VALUE":1717020000,"USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"12","CREATED_DATE":"29.05.2024 14:55:59","USER_ID":"1","TASK_ID":"2","FIELD":"COMMENT","FROM_VALUE":null,"TO_VALUE":"3","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"11","CREATED_DATE":"29.05.2024 14:55:59","USER_ID":"1","TASK_ID":"2","FIELD":"STATUS","FROM_VALUE":"5","TO_VALUE":"2","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"10","CREATED_DATE":"29.05.2024 14:55:05","USER_ID":"1","TASK_ID":"2","FIELD":"COMMENT","FROM_VALUE":null,"TO_VALUE":"2","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"9","CREATED_DATE":"29.05.2024 14:55:05","USER_ID":"1","TASK_ID":"2","FIELD":"STATUS","FROM_VALUE":"3","TO_VALUE":"5","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"8","CREATED_DATE":"29.05.2024 14:55:05","USER_ID":"1","TASK_ID":"2","FIELD":"TIME_SPENT_IN_LOGS","FROM_VALUE":"3600","TO_VALUE":"3610","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"7","CREATED_DATE":"29.05.2024 14:54:55","USER_ID":"1","TASK_ID":"2","FIELD":"STATUS","FROM_VALUE":"2","TO_VALUE":"3","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"6","CREATED_DATE":"29.05.2024 14:54:26","USER_ID":"1","TASK_ID":"2","FIELD":"TIME_SPENT_IN_LOGS","FROM_VALUE":"0","TO_VALUE":"3600","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},{"ID":"5","CREATED_DATE":"29.05.2024 14:53:48","USER_ID":"1","TASK_ID":"2","FIELD":"NEW","FROM_VALUE":null,"TO_VALUE":null,"USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"}],"SE_ELAPSEDTIME":{"3":{"ID":"3","CREATED_DATE":"29.05.2024 14:54:00","DATE_START":"29.05.2024 05:54:26","DATE_STOP":"29.05.2024 05:54:26","USER_ID":"1","TASK_ID":"2","MINUTES":"60","SECONDS":"3600","SOURCE":"2","COMMENT_TEXT":"","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},"4":{"ID":"4","CREATED_DATE":"29.05.2024 14:54:55","DATE_START":"29.05.2024 14:54:55","DATE_STOP":"29.05.2024 14:55:05","USER_ID":"1","TASK_ID":"2","MINUTES":"0","SECONDS":"10","SOURCE":"3","COMMENT_TEXT":"","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},"5":{"ID":"5","CREATED_DATE":"13.06.2024 16:09:19","DATE_START":"13.06.2024 16:09:19","DATE_STOP":"13.06.2024 16:09:23","USER_ID":"1","TASK_ID":"2","MINUTES":"0","SECONDS":"4","SOURCE":"3","COMMENT_TEXT":"","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"},"6":{"ID":"6","CREATED_DATE":"13.06.2024 16:09:29","DATE_START":"13.06.2024 16:09:29","DATE_STOP":"13.06.2024 16:09:42","USER_ID":"1","TASK_ID":"2","MINUTES":"0","SECONDS":"13","SOURCE":"3","COMMENT_TEXT":"","USER_NAME":"Admin","USER_LAST_NAME":"Admin","USER_SECOND_NAME":"","USER_LOGIN":"admin"}},"SE_PROJECTDEPENDENCE":[],"IN_DAY_PLAN":true,"TIME_ELAPSED":3627,"TIMER_IS_RUNNING_FOR_CURRENT_USER":false,"SE_AUDITOR":[],"SE_ACCOMPLICE":[],"SE_PARENT":[]},"RELATED_TASK":[],"GROUP":[],"USER":{"1":{"ID":"1","LOGIN":"admin","EMAIL":"dmitriy.karakov@micros.uz","ACTIVE":"Y","BLOCKED":"N","DATE_REGISTER":{},"LAST_LOGIN":{},"LAST_ACTIVITY_DATE":{},"TIMESTAMP_X":{},"NAME":"Admin","SECOND_NAME":"","LAST_NAME":"Admin","TITLE":"","EXTERNAL_AUTH_ID":null,"XML_ID":"","BX_USER_ID":null,"CONFIRM_CODE":null,"LID":"s1","LANGUAGE_ID":"ru","TIME_ZONE":"","TIME_ZONE_OFFSET":"32400","PERSONAL_PROFESSION":"","PERSONAL_PHONE":"","PERSONAL_MOBILE":"","PERSONAL_WWW":"","PERSONAL_ICQ":"","PERSONAL_FAX":"","PERSONAL_PAGER":"","PERSONAL_STREET":"","PERSONAL_MAILBOX":"","PERSONAL_CITY":"","PERSONAL_STATE":"","PERSONAL_ZIP":"","PERSONAL_COUNTRY":"0","PERSONAL_BIRTHDAY":null,"PERSONAL_GENDER":"","PERSONAL_PHOTO":null,"PERSONAL_NOTES":"","WORK_COMPANY":"","WORK_DEPARTMENT":"","WORK_PHONE":"","WORK_POSITION":"","WORK_WWW":"","WORK_FAX":"","WORK_PAGER":"","WORK_STREET":"","WORK_MAILBOX":"","WORK_CITY":"","WORK_STATE":"","WORK_ZIP":"","WORK_COUNTRY":"0","WORK_PROFILE":"","WORK_LOGO":null,"WORK_NOTES":"","ADMIN_NOTES":"","UF_DEPARTMENT":[1],"UF_USER_CRM_ENTITY":null,"IS_EXTRANET_USER":false,"IS_EMAIL_USER":false,"IS_CRM_EMAIL_USER":false,"IS_NETWORK_USER":false}},"IS_NETWORK_TASK":false,"GROUP_VIEWED":{"UNREAD_MID":[]},"EFFECTIVE":{"COUNT":0,"ITEMS":[]}}';

// $jsonArCan = `{
//     "TASK": {
//         "ACTION": {
//             "ACCEPT": false,
//             "DECLINE": false,
//             "COMPLETE": false,
//             "APPROVE": false,
//             "DISAPPROVE": false,
//             "START": false,
//             "PAUSE": false,
//             "DELEGATE": false,
//             "REMOVE": true,
//             "EDIT": true,
//             "DEFER": false,
//             "RENEW": true,
//             "CREATE": true,
//             "CHANGE_DEADLINE": true,
//             "CHECKLIST_ADD_ITEMS": true,
//             "ADD_FAVORITE": true,
//             "DELETE_FAVORITE": false,
//             "RATE": true,
//             "EDIT.ORIGINATOR": false,
//             "CHECKLIST.REORDER": true,
//             "ELAPSEDTIME.ADD": true,
//             "DAYPLAN.TIMER.TOGGLE": false,
//             "EDIT.PLAN": true,
//             "CHECKLIST.ADD": true,
//             "FAVORITE.ADD": true,
//             "FAVORITE.DELETE": false,
//             "DAYPLAN.ADD": false,
//             "ADD_TO_DAY_PLAN": false,
//             "SORT": false
//         },
//         "SE_TAG": [],
//         "SE_ELAPSEDTIME": {
//             "3": {
//                 "ACTION": {
//                     "MODIFY": true,
//                     "REMOVE": true
//                 }
//             },
//             "4": {
//                 "ACTION": {
//                     "MODIFY": true,
//                     "REMOVE": true
//                 }
//             },
//             "5": {
//                 "ACTION": {
//                     "MODIFY": true,
//                     "REMOVE": true
//                 }
//             },
//             "6": {
//                 "ACTION": {
//                     "MODIFY": true,
//                     "REMOVE": true
//                 }
//             }
//         }
//     }
// }`;