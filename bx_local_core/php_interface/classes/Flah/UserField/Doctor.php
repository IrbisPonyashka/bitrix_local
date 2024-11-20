<?php

namespace Flah\UserField;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\TypeBase;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\UserField\Types\StringType;
use Bitrix\Main\Engine\ActionFilter\Authentication;

class Doctor extends StringType
{
	use ErrorableImplementation;

	public const
		USER_TYPE_ID = 'flah.doctor',
		RENDER_COMPONENT = 'flah:field.doctor';

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public static function getDescription(): array
	{
		return [
			'DESCRIPTION' => Loc::getMessage('FLAH_USERFIELD_DOCTOR_NAME'),
			'BASE_TYPE'   => \CUserTypeManager::BASE_TYPE_STRING,
		];
	}

	/**
	 * @see Engine\Contract\Controllerable::configureActions()
	 * @return array
	 */
	public function configureActions()
	{
		return [
			'getCompanies' => [
				'prefilters' => [
					new Authentication()
				],
				'postfilters' => []
			],
			'getDepartments' => [
				'prefilters' => [
					new Authentication()
				],
				'postfilters' => []
			],
			'getDoctors' => [
				'prefilters' => [
					new Authentication()
				],
				'postfilters' => []
			]
		];
	}

	/**
	 * @return     array
	 */
	public function getCompaniesAction()
	{
		$structure = [];

			//$this->errorCollection->add( new Main\Error('Error №'.$i) );

		return $structure;
	}

	/**
	 * @param array $userField
	 * @return array
	 */
	public static function prepareSettings(array $userField): array
	{
		return [];
	}

	/**
	 * @param array $userField
	 * @param array|string $value
	 * @return array
	 */
	public static function checkFields(array $userField, $value): array
	{
		return [];
	}

	public static function onSearchIndex(array $userField): ?string
	{
		return "";
	}

	public static function renderEdit(array $userField, ?array $additionalParameters = []): string
	{
		return parent::renderEdit($userField, $additionalParameters);
	}

	public static function renderView(array $userField, ?array $additionalParameters = []): string
	{
		return parent::renderView($userField, $additionalParameters);
	}

	/**
	 * @array $userField
	 * @param $userField
	 * @return string
	 */
	public static function getEmptyCaption(array $userField): string
	{
		return Loc::getMessage('FLAH_USERFIELD_DOCTOR_NO_VALUE');
	}
}
