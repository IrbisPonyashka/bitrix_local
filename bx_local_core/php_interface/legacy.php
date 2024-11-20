<?php

/**
 * This file contain old legacy code include constant initialization
 * All code in this file in next updates should be moved into new
 * app structure if can.
 * 
 * For constant definitions:
 *     - each constant must contain a reasonable prefix and postfix
 *     - each constant must use english named
 * Example: 
 *     STRUCTURE_IBLOCK_ID is a valid constant
 *     INFOBLOK_VIZUALNOY_STRUCTURI is a invalid
 */

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

\Bitrix\Main\EventManager::getInstance()
	->addEventHandler(
	'main',
	'OnEpilog',
	function()
	{
		\Bitrix\Main\UI\Extension::load(["flah.doctor.field"]);
	}
);

\Bitrix\Main\Loader::includeModule('flah.crm');

define('VUEJS_DEBUG', true);