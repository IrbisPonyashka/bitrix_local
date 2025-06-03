<?php


/**
 * This file contains a full list of custom event handlers
 * Code the handlers need NOT be contained in this file. 
 * It needs to be made relevant to the PSR-[0-4] structure, classes
 */

$eventManager = \Bitrix\Main\EventManager::getInstance();

/**
 * For new core of bitrix use
 *     $eventManager->addEventHandler( #module#, #handler#, [#namespace#, #function#]);
 * 
 * For old core of bitrix use
 *     $eventManager->addEventHandlerCompatible( #module#, #handler#, [#namespace#, #function#]);
 */

/* подключение js модуля */
$eventManager->addEventHandlerCompatible(
    'main',
    'OnEpilog',
    [
        "\\irbisPonyashka\\Crm\\Request\\Page",
        "handleEpilog"
    ]
);

$eventManager->addEventHandler(
	'main',
	'OnUserTypeBuildList',
	[
		"\\Flah\\UserField\\DoctorField",
		'getUserTypeDescription'
	]
);

$eventManager->addEventHandler("main", "OnBeforeProlog", function () {
	
	function logB($result, $comment){
		$html = '\-------'.$comment."---------\n";
		$html.= print_r($result,true);
		$html.="\n".date("d.m.Y H:i:s")."\n--------------------\n\n\n";
		$file=$_SERVER["DOCUMENT_ROOT"]."/local/php_interface/logs.txt";
		$old_data=file_get_contents($file);
		file_put_contents($file, $html.$old_data);
	}

	global $USER;

	if ($USER->IsAuthorized()) {
		$userGroups = $USER->GetUserGroupArray();
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		
		// logB($request->getRequestUri(), "getRequestUri");
		
		// Исключаем редирект для группы "Все покупатели" (ID 19)
		if (in_array(19, $userGroups) && $request->getRequestUri() === '/' && $request->getRequestUri() != "/profile/") {
			LocalRedirect('/profile/');
			exit();
		}
	}
});
