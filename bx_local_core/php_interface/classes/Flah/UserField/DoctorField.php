<?php

namespace Flah\UserField;

use Bitrix\Main\Localization\Loc;
use Flah\UserField\Doctor;

Loc::loadMessages(__FILE__);

/**
 * Class CUserTypeCrmStatus
 * @deprecated
 */
class DoctorField extends \CUserTypeString
{
	public static function getUserTypeDescription()
	{
		return Doctor::getUserTypeDescription();
	}

	function prepareSettings($userField)
	{
		return Doctor::prepareSettings($userField);
	}

	function getSettingsHtml($userField, $additionalParameters, $varsFromForm)
	{
		return Doctor::renderSettings($userField, $additionalParameters, $varsFromForm);
	}

	function getEditFormHtml($userField, $additionalParameters)
	{
		return Doctor::renderEditForm($userField, $additionalParameters);
	}

	function getFilterHtml($userField, $additionalParameters)
	{
		return Doctor::renderFilter($userField, $additionalParameters);
	}

	function getAdminListViewHtml($userField, $additionalParameters)
	{
		return Doctor::renderAdminListView($userField, $additionalParameters);
	}

	function getAdminListEditHtml($userField, $additionalParameters)
	{
		return Doctor::renderAdminListEdit($userField, $additionalParameters);
	}

	function checkFields($userField, $value)
	{
		return Doctor::checkFields($userField, $value);
	}

	function getList($userField)
	{
		return Doctor::getList($userField);
	}

	function onSearchIndex($userField)
	{
		return Doctor::onSearchIndex($userField);
	}

	public static function getPublicText($userField)
	{
		return Doctor::renderText($userField);
	}

	public static function getPublicEdit($userField, $additionalParameters = array())
	{
		return Doctor::renderEdit($userField, $additionalParameters);
	}

	public static function getPublicView($userField, $additionalParameters = array())
	{
		return Doctor::renderView($userField, $additionalParameters);
	}

	public static function renderEdit($userField, $additionalParameters = []): string
	{
		return Doctor::renderEdit($userField, $additionalParameters);
	}

	public static function renderView($userField, $additionalParameters = []): string
	{
		return Doctor::renderView($userField, $additionalParameters);
	}
}