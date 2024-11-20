<?php


// \Bitrix\Main\Loader::registerAutoLoadClasses(null, [
//     '\IrbisPonyashka\Events\OnTasks\taskHandler' => '/local/php_interface/irbisPonyashka/events/onTask.php',
//     '\IrbisPonyashka\Tasks\Internals\Task\AgreedTimeTable' => '/local/php_interface/irbisPonyashka/libs/tasksAgreedTimeTable.php',
//     '\IrbisPonyashka\Tasks\Reports\TasksReportTable' => '/local/php_interface/irbisPonyashka/libs/tasksReportsTable.php',
// ]);

// require_once("irbisPonyashka/events/autoload.php");

// require_once("irbisPonyashka/handlers/project_bind_type.php");
// require_once("irbisPonyashka/handlers/projectBindingFieldType.php");

// // require_once("irbisPonyashka/events/extendedFactoryDynamicClass.php");

// require_once($_SERVER["DOCUMENT_ROOT"].'/local/php_interface/irbisPonyashka/classes/crm/container.php'); //класс с контейнером
// require_once($_SERVER["DOCUMENT_ROOT"].'/local/php_interface/irbisPonyashka/classes/crm/factory.php'); //класс с фабрикой
// \Bitrix\Main\DI\ServiceLocator::getInstance()->addInstanceLazy('crm.service.container', [
//    'className' => '\\IrbisPonyashka\\classes\\crm\\Container',
// ]);



/**
 * - /local/classes/{Path|raw}/{*|raw}.php
 * - /local/classes/{Path|ucfirst,lowercase}/{*|ucfirst,lowercase}.php
 */
spl_autoload_register(function($sClassName)
{
	$sClassFile = __DIR__.'/classes';

	if ( file_exists($sClassFile.'/'.str_replace('\\', '/', $sClassName).'.php') )
	{
		require_once($sClassFile.'/'.str_replace('\\', '/', $sClassName).'.php');
		return;
	}

	$arClass = explode('\\', strtolower($sClassName));
	foreach($arClass as $sPath )
	{
		$sClassFile .= '/'.ucfirst($sPath);
	}
	$sClassFile .= '.php';
	if (file_exists($sClassFile))
	{
		require_once($sClassFile);
	}
});

/**
 * Project bootstrap files
 * Include
 * 
 */
foreach( [
	/**
	 * File for other kernel data:
	 *    Service local integration
	 *    Env file with local variables
	 *        external service credentials
	 *        feature enable flags
	 */
	__DIR__.'/kernel.php',

	/**
	 * Events subscribe
	 */
	__DIR__.'/events.php',

	/**
	 * Include composer libraries
	 */
	__DIR__.'/vendor/autoload.php',

	/**
	 * Include old legacy code
	 *   constant initiation etc
	 */
	__DIR__.'/legacy.php',
	]
	as $filePath )
{
	if ( file_exists($filePath) )
	{
		require_once($filePath);
	}
}