<?php
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);
define('PUBLIC_AJAX_MODE', true);

if (isset($_REQUEST['site']) && is_string($_REQUEST['site']))
{
    $siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2);
    if ($siteId)
    {
        define('SITE_ID', $siteId);
    }
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use \Micros\Harvest\MicrosHarvestProjectsTable;
/**
 * @global CUser $USER
 */

global $DB, $APPLICATION;

$sendResponse = function($data, array $errors = array())
{
    if ($data instanceof Bitrix\Main\Result)
    {
        $errors = $data->getErrorMessages();
        $data = $data->getData();
    }

    $result = $data;
    $result['errors'] = $errors;
    $result['success'] = count($errors) === 0;

    header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

    echo \Bitrix\Main\Web\Json::encode($result);
    CMain::FinalActions();
    die();
};
$sendError = function($error) use ($sendResponse)
{
    $sendResponse(array(), (array)$error);
};
if (!CModule::IncludeModule('crm'))
{
    $sendError('Module CRM is not installed.');
}


$inputBodyRepsonseJson = file_get_contents("php://input");
$inputBodyRepsonse = json_decode($inputBodyRepsonseJson,1);
$inputHeaderReponse = $_REQUEST;

if( $inputHeaderReponse["type"] && $inputHeaderReponse["type"] == "add") {

    if( !empty($inputHeaderReponse["PROJECT_ID"]) && !empty($inputHeaderReponse["ESTIMATED_TIME"]) && !empty($inputHeaderReponse["PROJECT_ID"])) {

    }

//    ConfigurationTable::add([
//        'TYPE_ID' => $type,
//        'TARGET_TYPE' => $specify,
//        'TARGET_GOAL' => $item,
//        'PERIOD_TYPE' => $configuration['period_type'],
//        'PERIOD_YEAR' => $configuration['period_year'],
//        'PERIOD_MONTH' => $configuration['period_month'],
//        'EDITOR_ID' => $id,
//    ]);

}else{
    $sendError("404");
}

