<?php

/**
 * This file store additional requirements for project
 * etc: env data, service locator initialization
 * 
 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=14032&LESSON_PATH=3913.5062.14032
 */

use \Bitrix\Main;
use \Bitrix\Main\Context;
use \Bitrix\Main\DI\ServiceLocator;
use \Bitrix\Main\Config\Option;

/**
 * Env section
 * File moved from DOCUMENT_ROOT directory for security reasons
 */
$envPath = dirname($_SERVER['DOCUMENT_ROOT']);

if ( file_exists($envPath.'/.env') )
{
	$env = Context::getCurrent()->getEnvironment();
	$curEnv = $env->getValues();
	$iniParams = \parse_ini_file($envPath.'/.env', true, INI_SCANNER_TYPED);
	foreach ($iniParams as $key => $value)
	{
		$curEnv[$key] = $value;
	}
	$env->set($curEnv);
	unset($curEnv);
	unset($iniParams);
}
unset($envPath);

/**
 * Service locator section
 *   if exist
 */
if ( class_exists('\Bitrix\Main\DI\ServiceLocator') )
{
	$serviceLocator = ServiceLocator::getInstance();

	/**
	 * service location naming convention:
	 *     * must contant vendor.
	 *     * must be lowercase
	 *         OK: 'fusion.exchange.service', 'sber.payment.service'
	 *         BAD: 'FUSION_SOME_SERVICE', 'COOL_SERVICE', 'TaSk.SerViCE.i18n'
	 * 
	 * Examples:
	 * 
	 * $serviceLocator->addInstanceLazy('fusion.some.service', [
	 *     'constructor' => static function () use ($serviceLocator) {
	 *         return new \Fusion\SomeModule\Services\SecondService('foo', 'bar');
	 *     }
	 * ]);
	 * 
	 * $serviceLocator->addInstanceLazy('fusion.some.service', [
	 *     'className' => \Fusion\SomeModule\Services\SomeService::class,
	 * ]);
	 * 
	 */

	//$buildingEntityTypeId = (int) Option::get("flah", "CRM_ENTITY_TID_CHILDREN", 0);
	//if ( $buildingEntityTypeId > 0 )
	//{
	//	$serviceLocator->addInstanceLazy(
	//		"crm.service.factory.dynamic.".$buildingEntityTypeId,
	//		[
	//			'constructor' => function () {
	//				\Bitrix\Main\Loader::requireModule('crm');
	//				\Bitrix\Main\Loader::requireModule('flah.crm');
	//				$type = \Bitrix\Crm\Service\Container::getInstance()
	//					->getTypeByEntityTypeId(Option::get("flah", "CRM_ENTITY_TID_CHILDREN", 0));
	//				return new \Flah\Crm\Factory\Children($type);
	//			},
	//		]
	//	);
	//}
	//unset($buildingEntityTypeId);
	
	$buildingEntityTypeId = (int) Option::get("flah", "CRM_ENTITY_TID_NEGOTIATION_CONTRACT", 0);
	if ( $buildingEntityTypeId > 0 )
	{
		$serviceLocator->addInstanceLazy(
			"crm.service.factory.dynamic.".$buildingEntityTypeId,
			[
				'constructor' => function () {
					\Bitrix\Main\Loader::requireModule('crm');
					\Bitrix\Main\Loader::requireModule('flah.crm');
					$type = \Bitrix\Crm\Service\Container::getInstance()
						->getTypeByEntityTypeId(Option::get("flah", "CRM_ENTITY_TID_NEGOTIATION_CONTRACT", 0));
					return new \Flah\Crm\Factory\NegotiationContract($type);
				},
			]
		);
	}
	unset($buildingEntityTypeId);

	unset($serviceLocator);
}