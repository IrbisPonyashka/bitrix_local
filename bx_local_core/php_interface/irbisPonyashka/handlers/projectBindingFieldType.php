<?php  


namespace Micros\Handlers\ProjectBindingFieldType;

// use \Bitrix\Main\UserField\Types\IntegerType;
use Bitrix\Main\Loader;
use Bitrix\Main\EventManager; 
use Bitrix\Main\CDBResult;
use Bitrix\Crm;
use Bitrix\Crm\UserField\UserFieldHistory;
use Bitrix\Crm\UserField;
use Bitrix\Main\UserField\TypeBase;

Loader::includeModule("crm");
   
class ProjectBindingFieldType extends TypeBase
{

    const USER_TYPE_ID = 'projectLink';      

    static function GetUserTypeDescription()
    {
        return array(
            "USER_TYPE_ID" => 'projectLink',
            'DESCRIPTION' => 'Привязка к группе/проекту',
            'CLASS_NAME' => __CLASS__,
            'BASE_TYPE' => 'string',
            'EDIT_CALLBACK' => array( __CLASS__, 'GetPublicEdit' ),
            'VIEW_CALLBACK' => array( __CLASS__, 'GetPublicView' )
        );
    }

    // Отображение значения в списке админки
    function GetAdminListViewHTML($arUserField, $arHtmlControl)
    {
        return htmlspecialcharsbx($arHtmlControl["VALUE"]);
    }
    
	// public static function PrepareSettings(array $userField): array
	public static function prepareSettings(array $userField): array
	{
		$size = (int)($userField['SETTINGS']['SIZE'] ?? 0);
		$rows = (int)($userField['SETTINGS']['ROWS'] ?? 0);
		$min = (int)($userField['SETTINGS']['MIN_LENGTH'] ?? 0);
		$max = (int)($userField['SETTINGS']['MAX_LENGTH'] ?? 0);

		$regExp = '';
		if (
			is_array($userField['SETTINGS'])
			&& !empty($userField['SETTINGS']['REGEXP'])
			//Checking the correctness of the regular expression entered by the user
			&& @preg_match($userField['SETTINGS']['REGEXP'], null) !== false
		)
		{
			$regExp = $userField['SETTINGS']['REGEXP'];
		}

		return [
			'SIZE' => ($size <= 1 ? 20 : ($size > 255 ? 225 : $size)),
			'ROWS' => ($rows <= 1 ? 1 : ($rows > 50 ? 50 : $rows)),
			'REGEXP' => $regExp,
			'MIN_LENGTH' => $min,
			'MAX_LENGTH' => $max,
			'DEFAULT_VALUE' => is_array($userField['SETTINGS']) ? ($userField['SETTINGS']['DEFAULT_VALUE'] ?? '') : '',
		];
	}
    
	// редактирование свойства в форме (главный модуль)
    public static function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        return self::getEditHTML($arHtmlControl['NAME'], $arHtmlControl['VALUE'], false);
    }
    
    // редактирование свойства в списке (главный модуль)
    public static function GetAdminListEditHTML($arUserField, $arHtmlControl)
    {
        return self::getViewHTML($arHtmlControl['NAME'], $arHtmlControl['VALUE'], true);
    }

    // Метод для экспорта значения в CSV
    function GetExportValue($arUserField, $arHtmlControl)
    {
        return $arHtmlControl["VALUE"];
    }

    //метод возвращает MySQL тип колонки, в которой будет храниться значение одиночного поля. Множественные поля всегда хранятся как text.
    public static function GetDBColumnType ($arUserField)
    {
        global $DB;
        
        // Bitrix\Main\Diag\Debug::writeToFile($arUserField, "arUserField", "/home/bitrix/www/bitrix/local/php_interface/micros/logs/arUserField_projectLink.log");
        // self::LogB($result, $comment);

        switch (strtolower($DB->type)) {
            case "mysql":
                return "varchar(255) not null";  // Добавьте "not null" или нужные ограничения
            case "oracle":
                return "varchar2(2000 char)";
            case "mssql":
                return "varchar(2000)";
            default:
                throw new \Exception("Unsupported DB type");
        }
    }

    public static function GetPublicView ($arUserField, $arAdditionalParameters = array())
    {
        global $APPLICATION;

        ob_start();
        $APPLICATION->IncludeComponent(
            "micros:system.field.view",
            "projectLink",
            array(
                "arUserField" => $arUserField,
                "arAdditionalParameters" => $arAdditionalParameters
            )
        );
        $componentResult = ob_get_clean();

        return $componentResult;
    }

    public static function GetPublicEdit ($arUserField, $arAdditionalParameters = array())
    {
        global $APPLICATION;

        ob_start();
        $APPLICATION->IncludeComponent(
            "micros:system.field.edit",
            "projectLink",
            array(
                "arUserField" => $arUserField,
                "arAdditionalParameters" => $arAdditionalParameters
            )
        );
        $componentResult = ob_get_clean();

        return $componentResult;
    }

	/**
	 * @param null|array $userField
	 * @param array $additionalSettings
	 * @return array
	 */
	public static function getFilterData(?array $userField, array $additionalSettings): array
	{
		self::LogB([$userField,$additionalSettings], "____TEST____");
		return [
			'id' => $additionalSettings['ID'],
			'name' => $additionalSettings['NAME'],
			'filterable' => true
		];
	}

	/**
	 * This function should return a representation of the field value for the search.
	 * It is called from the OnSearchIndex method of the object $ USER_FIELD_MANAGER,
	 * which is also called the update function of the entity search index.
	 * For multiple values, the VALUE field is an array.
	 * @param array $userField
	 * @return string|null
	 */
	public static function onSearchIndex(array $userField): ?string
	{
		if(is_array($userField['VALUE']))
		{
			$result = implode('\r\n', $userField['VALUE']);
		}
		else
		{
			$result = $userField['VALUE'];
		}

		return $result;
	}
	
	/**
	 * @param array $userField
	 * @param array|null $additionalParameters
	 * @return string
	 */
	public static function getFilterHtml(array $userField, ?array $additionalParameters): string
	{
		self::LogB([$userField,$additionalParameters], "____getFilterHtml____");
		return static::renderFilter($userField, $additionalParameters);
	}

	/**
	 * This function is called when the filter is displayed on the list page.
	 *
	 * Returns html to embed in a table cell.
	 * $additionalParameters elements are html safe.
	 *
	 * @param array $userField An array describing the field.
	 * @param array|null $additionalParameters An array of controls from the form. Contains the elements NAME and VALUE.
	 * @return string
	 */
	public static function renderFilter(array $userField, ?array $additionalParameters): string
	{
		self::LogB([$userField,$additionalParameters], "____renderFilter____");
		$additionalParameters['mode'] = "main.filter_html";
		// $additionalParameters['mode'] = self::MODE_FILTER_HTML;
		return self::getHtml($userField, $additionalParameters);
	}
	

    public static function LogB($result, $comment) // метод для просмотра логов
    {
        $html = '\-------' . $comment . "---------\n";
        $html .= print_r($result, true);
        $html .= "\n" . date("d.m.Y H:i:s") . "\n--------------------\n\n\n";
        $file = $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/micros/logs/arUserField_projectLink.txt";
        $old_data = file_get_contents($file);
        file_put_contents($file, $html . $old_data);
    }
}


class CCrmProjectBindFields extends \CCrmFields
{
	private $sUFEntityID = '';

	protected $cUFM = null;

	protected $cdb = null;

	private $arUFList = array();

	private $arEntityType = array();

	private $arFieldType = array();

	private $arErrors = array();

	private $bError = false;

	public static function GetFieldTypes()
	{
		//'Disk File' is disabled due to GUI issues (see CCrmDocument::GetDocumentFieldTypes)
		$arFieldType = Array(
			'string' 		=> array( 'ID' =>'string', 'NAME' => GetMessage('CRM_FIELDS_TYPE_S')),
			'integer'		=> array( 'ID' =>'integer', 'NAME' => GetMessage('CRM_FIELDS_TYPE_I')),
			'double'		=> array( 'ID' =>'double', 'NAME' => GetMessage('CRM_FIELDS_TYPE_D')),
			'boolean'		=> array( 'ID' =>'boolean', 'NAME' => GetMessage('CRM_FIELDS_TYPE_B')),
			'datetime'		=> array( 'ID' =>'datetime', 'NAME' => GetMessage('CRM_FIELDS_TYPE_DT')),
			'date'			=> array( 'ID' =>'date', 'NAME' => GetMessage('CRM_FIELDS_TYPE_DATE')),
			'money' 		=> array( 'ID' =>'money', 'NAME' => GetMessage('CRM_FIELDS_TYPE_MONEY')),
			'url' 			=> array( 'ID' =>'url', 'NAME' => GetMessage('CRM_FIELDS_TYPE_URL')),
			'address'		=> array( 'ID' =>'address', 'NAME' => GetMessage('CRM_FIELDS_TYPE_ADDRESS')),
			'enumeration' 	=> array( 'ID' =>'enumeration', 'NAME' => GetMessage('CRM_FIELDS_TYPE_E')),
			'file'			=> array( 'ID' =>'file', 'NAME' => GetMessage('CRM_FIELDS_TYPE_F')),
			'employee'		=> array( 'ID' =>'employee', 'NAME' => GetMessage('CRM_FIELDS_TYPE_EM')),
			'crm_status'	=> array( 'ID' =>'crm_status', 'NAME' => GetMessage('CRM_FIELDS_TYPE_CRM_STATUS')),
			'iblock_section'=> array( 'ID' =>'iblock_section', 'NAME' => GetMessage('CRM_FIELDS_TYPE_IBLOCK_SECTION')),
			'iblock_element'=> array( 'ID' =>'iblock_element', 'NAME' => GetMessage('CRM_FIELDS_TYPE_IBLOCK_ELEMENT')),
			'c_ibel'        => array( 'ID' =>'c_ibel', 'NAME' => GetMessage('CRM_FIELDS_TYPE_IBLOCK_SORT')),
			'crm'			=> array( 'ID' =>'crm', 'NAME' => GetMessage('CRM_FIELDS_TYPE_CRM_ELEMENT'))
			//'disk_file'	=> array( 'ID' =>'disk_file', 'NAME' => GetMessage('CRM_FIELDS_TYPE_DISK_FILE')),
		);
		return $arFieldType;
	}
    
	public static function GetAdditionalFields($entityType, $fieldValue = Array())
	{
		$arFields = Array();
		switch ($entityType)
		{
			case 'iblock_section':
				$id = isset($fieldValue['IB_IBLOCK_ID'])? $fieldValue['IB_IBLOCK_ID']: 0;
				$bActiveFilter = isset($fieldValue['IB_ACTIVE_FILTER']) && $fieldValue['IB_ACTIVE_FILTER'] == 'Y'? 'Y': 'N';

				$arFields[] = array(
					'id' => 'IB_IBLOCK_TYPE_ID',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_IBLOCK_TYPE_ID'),
					'type' => 'custom',
					'value' => GetIBlockDropDownList($id, 'IB_IBLOCK_TYPE_ID', 'IB_IBLOCK_ID')
				);

				$arFilter = Array("IBLOCK_ID"=>$id);
				if($bActiveFilter === "Y")
					$arFilter["GLOBAL_ACTIVE"] = "Y";

				$rs = CIBlockElement::GetList(
					array("SORT" => "DESC", "NAME"=>"ASC"),
					$arFilter,
					false,
					false,
					array("ID", "NAME")
				);
				$rsSections = CIBlockSection::GetList(
					Array("left_margin"=>"asc"),
					$arFilter,
					false,
					array("ID", "DEPTH_LEVEL", "NAME")
				);
				$arDefault = Array(''=>GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE_ANY'));
				while($arSection = $rsSections->GetNext())
					$arDefaul[$arSection["ID"]] = str_repeat("&nbsp;.&nbsp;", $arSection["DEPTH_LEVEL"]).$arSection["NAME"];

				$arFields[] = array(
					'id' => 'IB_DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE'),
					'items' => $arDefault,
					'type' => 'list',
				);

				$arFields[] = array(
					'id' => 'IB_DISPLAY',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY'),
					'type' => 'list',
					'items' => array(
						'LIST'		=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_LIST'),
						'CHECKBOX' 	=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_CHECKBOX'),
					),
				);
				$arFields[] = array(
					'id' => 'IB_LIST_HEIGHT',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_LIST_HEIGHT'),
					'type' => 'text',
				);
				$arFields[] = array(
					'id' => 'IB_ACTIVE_FILTER',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_ACTIVE_FILTER'),
					'type' => 'checkbox',
				);
			break;
		}
		return $arFields;
	}
}

class CCrmProjectBindType extends \CCrmUserType
{
	public function PrepareListFilterValues(array &$arFilterFields, array $arFilter = null, $sFormName = 'form1', $bVarsFromForm = true)
    {
		global $APPLICATION;
		$arUserFields = $this->GetAbstractFields();
		foreach($arFilterFields as &$arField)
		{
			$fieldID = $arField['id'];
			if(!isset($arUserFields[$fieldID]))
			{
				continue;
			}

			$arUserField = $arUserFields[$fieldID];
			if($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'employee')
			{
				continue;
			}

			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum' ||
				$arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_element' || $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_section' || $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'c_ibel')
			{
				// Fix #29649. Allow user to add not multiple fields with height 1 item.
				if($arUserField['MULTIPLE'] !== 'Y'
					&& isset($arUserField['SETTINGS']['LIST_HEIGHT'])
					&& intval($arUserField['SETTINGS']['LIST_HEIGHT']) > 1)
				{
					$arUserField['MULTIPLE'] = 'Y';
				}

				//as the system presets the filter can not work with the field names containing []
				if ($arUserField['SETTINGS']['DISPLAY'] == 'CHECKBOX')
					$arUserField['SETTINGS']['DISPLAY'] = '';
			}

			$params = array(
				'arUserField' => $arUserField,
				'arFilter' => $arFilter,
				'bVarsFromForm' => $bVarsFromForm,
				'form_name' => 'filter_'.$sFormName,
				'bShowNotSelected' => true
			);

			$userType = $arUserField['USER_TYPE']['USER_TYPE_ID'];
			$templateName = $userType;
			if($userType === 'date')
			{
				$templateName = 'datetime';
				$params['bShowTime'] = false;
			}

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:crm.field.filter',
				$templateName,
				$params,
				false,
				array('HIDE_ICONS' => true)
			);
			$sVal = ob_get_contents();
			ob_end_clean();

			$arField['value'] = $sVal;
		}
		unset($field);
    }
	
	// public function PrepareListFilterFields(&$arFilterFields, &$arFilterLogic)
	// {
	// 	$arUserFields = $this->GetAbstractFields();
	// 	foreach($arUserFields as $FIELD_NAME => $arUserField)
	// 	{
	// 		if ($arUserField['SHOW_FILTER'] === 'N' || $arUserField['USER_TYPE']['BASE_TYPE'] === 'file')
	// 		{
	// 			continue;
	// 		}

	// 		$ID = $arUserField['ID'];
	// 		$typeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];
	// 		$isMultiple = isset($arUserField['MULTIPLE']) && $arUserField['MULTIPLE'] === 'Y';

	// 		if($typeID === 'employee')
	// 		{
	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'custom_entity',
	// 				'selector' => array(
	// 					'TYPE' => 'user',
	// 					'DATA' => array('ID' => strtolower($FIELD_NAME), 'FIELD_ID' => $FIELD_NAME)
	// 				)
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'string' || $typeID === 'url' || $typeID === 'address' || $typeID === 'money')
	// 		{
	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'text'
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'integer' || $typeID === 'double')
	// 		{
	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'number'
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'boolean')
	// 		{
	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'checkbox',
	// 				'valueType' => 'numeric'
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'datetime' || $typeID === 'date')
	// 		{
	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'date',
	// 				'time' => $typeID === 'datetime'
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'enumeration')
	// 		{
	// 			$enumEntity = new \CUserFieldEnum();
	// 			$dbResultEnum = $enumEntity->GetList(
	// 				array('SORT' => 'ASC'),
	// 				array('USER_FIELD_ID' => $ID)
	// 			);

	// 			$listItems = array();
	// 			while($enum = $dbResultEnum->Fetch())
	// 			{
	// 				$listItems[$enum['ID']] = $enum['VALUE'];
	// 			}

	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'list',
	// 				'params' => array('multiple' => 'Y'),
	// 				'items' => $listItems
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'iblock_element')
	// 		{
	// 			$listItems = array();
	// 			$enity = new CUserTypeIBlockElement();
	// 			$dbResult = $enity->GetList($arUserField);
	// 			if(is_object($dbResult))
	// 			{
	// 				$qty = 0;
	// 				$limit = 200;

	// 				while($ary = $dbResult->Fetch())
	// 				{
	// 					$listItems[$ary['ID']] = $ary['NAME'];
	// 					$qty++;
	// 					if($qty === $limit)
	// 					{
	// 						break;
	// 					}
	// 				}
	// 			}

	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'list',
	// 				'params' => array('multiple' => 'Y'),
	// 				'items' => $listItems
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'c_ibel')
	// 		{
	// 			$listItems = array();
	// 			$enity = new CUserTypeIBlockElement();
	// 			$dbResult = $enity->GetList($arUserField);
	// 			if(is_object($dbResult))
	// 			{
	// 				$qty = 0;
	// 				$limit = 200;

	// 				while($ary = $dbResult->Fetch())
	// 				{
	// 					$listItems[$ary['ID']] = $ary['NAME'];
	// 					$qty++;
	// 					if($qty === $limit)
	// 					{
	// 						break;
	// 					}
	// 				}
	// 			}

	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'list',
	// 				'params' => array('multiple' => 'Y'),
	// 				'items' => $listItems
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'iblock_section')
	// 		{
	// 			$listItems = array();
	// 			$enity = new CUserTypeIBlockSection();
	// 			$dbResult = $enity->GetList($arUserField);

	// 			if(is_object($dbResult))
	// 			{
	// 				$qty = 0;
	// 				$limit = 200;

	// 				while($ary = $dbResult->Fetch())
	// 				{
	// 					$listItems[$ary['ID']] = isset($ary['DEPTH_LEVEL']) && $ary['DEPTH_LEVEL']  > 1
	// 						? str_repeat('. ', ($ary['DEPTH_LEVEL'] - 1)).$ary['NAME'] : $ary['NAME'];
	// 					$qty++;
	// 					if($qty === $limit)
	// 					{
	// 						break;
	// 					}
	// 				}
	// 			}

	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'list',
	// 				'params' => array('multiple' => 'Y'),
	// 				'items' => $listItems
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'crm')
	// 		{
	// 			$settings = isset($arUserField['SETTINGS']) && is_array($arUserField['SETTINGS'])
	// 				? $arUserField['SETTINGS'] : array();

	// 			$entityTypeNames = array();
	// 			$supportedEntityTypeNames = array(
	// 				CCrmOwnerType::LeadName,
	// 				CCrmOwnerType::DealName,
	// 				CCrmOwnerType::ContactName,
	// 				CCrmOwnerType::CompanyName
	// 			);
	// 			foreach($supportedEntityTypeNames as $entityTypeName)
	// 			{
	// 				if(isset($settings[$entityTypeName]) && $settings[$entityTypeName] === 'Y')
	// 				{
	// 					$entityTypeNames[] = $entityTypeName;
	// 				}
	// 			}

	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'custom_entity',
	// 				'selector' => array(
	// 					'TYPE' => 'crm_entity',
	// 					'DATA' => array(
	// 						'ID' => strtolower($FIELD_NAME),
	// 						'FIELD_ID' => $FIELD_NAME,
	// 						'ENTITY_TYPE_NAMES' => $entityTypeNames,
	// 						'IS_MULTIPLE' => $isMultiple
	// 					)
	// 				)
	// 			);
	// 			continue;
	// 		}
	// 		elseif($typeID === 'crm_status')
	// 		{
	// 			$listItems = array();
	// 			if(isset($arUserField['SETTINGS'])
	// 				&& is_array($arUserField['SETTINGS'])
	// 				&& isset($arUserField['SETTINGS']['ENTITY_TYPE'])
	// 			)
	// 			{
	// 				$entityType = $arUserField['SETTINGS']['ENTITY_TYPE'];
	// 				if($entityType !== '')
	// 				{
	// 					$listItems = CCrmStatus::GetStatusList($entityType);
	// 				}
	// 			}

	// 			$arFilterFields[] = array(
	// 				'id' => $FIELD_NAME,
	// 				'name' => $arUserField['LIST_FILTER_LABEL'],
	// 				'type' => 'list',
	// 				'params' => array('multiple' => 'Y'),
	// 				'items' => $listItems
	// 			);
	// 			continue;
	// 		}

	// 		$arFilterFields[] = array(
	// 			'id' => $FIELD_NAME,
	// 			'name' => htmlspecialcharsex($arUserField['LIST_FILTER_LABEL']),
	// 			'type' => 'custom',
	// 			'value' => ''
	// 		);

	// 		// Fix issue #49771 - do not treat 'crm' type values as strings. To suppress filtration by LIKE.
	// 		// Fix issue #56844 - do not treat 'crm_status' type values as strings. To suppress filtration by LIKE.
	// 		if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'string' && $arUserField['USER_TYPE']['USER_TYPE_ID'] !== 'crm' && $arUserField['USER_TYPE']['USER_TYPE_ID'] !== 'crm_status')
	// 			$arFilterLogic[] = $FIELD_NAME;
	// 	}
    // }
	
	public function ListAddEnumFieldsValue($arParams, &$arValue, &$arReplaceValue, $delimiter = '<br />', $textonly = false, $arOptions = array())
	{
		$arUserFields = $this->GetAbstractFields();
		$bSecondLoop = false;
		$arValuePrepare = array();

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		// The first loop to collect all the data fields
		foreach($arUserFields as $FIELD_NAME => &$arUserField)
		{
			$isMultiple = $arUserField['MULTIPLE'] == 'Y';
			foreach ($arValue as $ID => $data)
			{
				if(!$isMultiple)
				{
					$isEmpty = !isset($arValue[$ID][$FIELD_NAME]) && $arUserField['USER_TYPE']['USER_TYPE_ID'] != 'boolean';
				}
				else
				{
					$isEmpty = !isset($arValue[$ID][$FIELD_NAME]) || $arValue[$ID][$FIELD_NAME] === false;
				}

				if($isEmpty)
				{
					continue;
				}

				if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
				{
					if (isset($arValue[$ID][$FIELD_NAME]))
						$arValue[$ID][$FIELD_NAME] == ($arValue[$ID][$FIELD_NAME] == 1 || $arValue[$ID][$FIELD_NAME] == 'Y' ? 'Y' : 'N');

					$arVal = $arValue[$ID][$FIELD_NAME];
					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $val)
					{
						$val = (string)$val;

						if (strlen($val) <= 0)
						{
							//Empty value is always 'N' (not default field value)
							$val = 'N';
						}

						$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').($val == 1 ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'));
						if ($isMultiple)
						{
							$arValue[$ID][$FIELD_NAME][] = ($val == 1 || $val == 'Y') ? 'Y' : 'N';
						}
						else
						{
							$arValue[$ID][$FIELD_NAME] = ($val == 1 || $val == 'Y') ? 'Y' : 'N';
						}
					}
				}
				elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'crm_status')
				{
					$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
					$arReplaceValue[$ID][$FIELD_NAME] = isset($ar[$arValue[$ID][$FIELD_NAME]])? $ar[$arValue[$ID][$FIELD_NAME]]: '';
				}
				elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'crm')
				{
					$arParams['CRM_ENTITY_TYPE'] = Array();
					if ($arUserField['SETTINGS']['LEAD'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'LEAD';
					if ($arUserField['SETTINGS']['CONTACT'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'CONTACT';
					if ($arUserField['SETTINGS']['COMPANY'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'COMPANY';
					if ($arUserField['SETTINGS']['DEAL'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'DEAL';

					$arParams['CRM_PREFIX'] = false;
					if (count($arParams['CRM_ENTITY_TYPE']) > 1)
						$arParams['CRM_PREFIX'] = true;

					$bSecondLoop = true;
					$arVal = $arValue[$ID][$FIELD_NAME];
					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $value)
					{
						if($arParams['CRM_PREFIX'])
						{
							$ar = explode('_', $value);
							$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
							$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][CUserTypeCrm::GetLongEntityType($ar[0])][intval($ar[1])] = intval($ar[1]);
						}
						else
						{
							if (is_numeric($value))
							{
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][$arParams['CRM_ENTITY_TYPE'][0]][] = $value;
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][$arParams['CRM_ENTITY_TYPE'][0]][$value] = $value;
							}
							else
							{
								$ar = explode('_', $value);
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][CUserTypeCrm::GetLongEntityType($ar[0])][intval($ar[1])] = intval($ar[1]);
							}
						}
					}
					$arReplaceValue[$ID][$FIELD_NAME] = '';
				}
				elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'file'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'employee'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_element'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'c_ibel'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'enumeration'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_section')
				{
					$bSecondLoop = true;
					$arVal = $arValue[$ID][$FIELD_NAME];
					$arReplaceValue[$ID][$FIELD_NAME] = '';

					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $value)
					{
						if($value === '' || $value <= 0)
						{
							continue;
						}
						$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][$value] = $value;
						$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['ID'][] = $value;
					}
				}
				elseif(!$textonly
					&& ($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'address'
						|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'money'
						|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'url'))
				{
					if($isMultiple)
					{
						$value = array();
						if(is_array($arValue[$ID][$FIELD_NAME]))
						{
							$valueCount = count($arValue[$ID][$FIELD_NAME]);
							for($i = 0; $i < $valueCount; $i++)
							{
								$value[] = htmlspecialcharsback($arValue[$ID][$FIELD_NAME][$i]);
							}
						}
					}
					else
					{
						$value = htmlspecialcharsback($arValue[$ID][$FIELD_NAME]);
					}

					$arReplaceValue[$ID][$FIELD_NAME] = $this->cUFM->GetPublicView(
						array_merge(
							$arUserField,
							array('ENTITY_VALUE_ID' => $ID, 'VALUE' => $value)
						)
					);
				}
				else if ($isMultiple && is_array($arValue[$ID][$FIELD_NAME]))
				{
					array_walk($arValue[$ID][$FIELD_NAME], create_function('&$v',  '$v = htmlspecialcharsbx($v);'));
					$arReplaceValue[$ID][$FIELD_NAME] = implode($delimiter, $arValue[$ID][$FIELD_NAME]);
				}
			}
		}
		unset($arUserField);

		// The second loop for special field
		if($bSecondLoop)
		{
			$arValueReplace = Array();
			$arList = Array();
			foreach($arValuePrepare as $KEY => $VALUE)
			{
				// collect multi data
				if ($KEY == 'iblock_section')
				{
					$dbRes = CIBlockSection::GetList(array('left_margin' => 'asc'), array('ID' => $VALUE['ID']), false);
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'file')
				{
					$dbRes = CFile::GetList(Array(), array('@ID' => implode(',', $VALUE['ID'])));
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'iblock_element')
				{
					$dbRes = CIBlockElement::GetList(array('SORT' => 'DESC', 'NAME' => 'ASC'), array('ID' => $VALUE['ID']), false);
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'c_ibel')
				{
					$dbRes = CIBlockElement::GetList(array('SORT' => 'DESC', 'NAME' => 'ASC'), array('ID' => $VALUE['ID']), false);
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'employee')
				{
					$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', array('ID' => implode('|', $VALUE['ID'])));
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'enumeration')
				{
					foreach ($VALUE['ID'] as $___value)
					{
						$rsEnum = CUserFieldEnum::GetList(array(), array('ID' => $___value));
						while ($arRes = $rsEnum->Fetch())
							$arList[$KEY][$arRes['ID']] = $arRes;
					}
				}
				elseif ($KEY == 'crm')
				{
					if (isset($VALUE['LEAD']) && !empty($VALUE['LEAD']))
					{
						$dbRes = CCrmLead::GetListEx(array('TITLE' => 'ASC', 'LAST_NAME' => 'ASC', 'NAME' => 'ASC'), array('ID' => $VALUE['LEAD']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['LEAD'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['CONTACT']) && !empty($VALUE['CONTACT']))
					{
						$dbRes = CCrmContact::GetListEx(array('LAST_NAME' => 'ASC', 'NAME' => 'ASC'), array('=ID' => $VALUE['CONTACT']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['CONTACT'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['COMPANY']) && !empty($VALUE['COMPANY']))
					{
						$dbRes = CCrmCompany::GetListEx(array('TITLE' => 'ASC'), array('ID' => $VALUE['COMPANY']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['COMPANY'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['DEAL']) && !empty($VALUE['DEAL']))
					{
						$dbRes = CCrmDeal::GetListEx(array('TITLE' => 'ASC'), array('ID' => $VALUE['DEAL']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['DEAL'][$arRes['ID']] = $arRes;
					}
				}

				// assemble multi data
				foreach ($VALUE['FIELD'] as $ID => $arFIELD_NAME)
				{
					foreach ($arFIELD_NAME as $FIELD_NAME => $FIELD_VALUE)
					{
						foreach ($FIELD_VALUE as $FIELD_VALUE_NAME => $FIELD_VALUE_ID)
						{
							if ($KEY == 'iblock_section')
							{
								$sname = htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['NAME']);
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							if ($KEY == 'iblock_element')
							{
								$sname = htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['NAME']);
								if(!$textonly)
								{
									$surl = GetIBlockElementLinkById($arList[$KEY][$FIELD_VALUE_ID]['ID']);
									if ($surl && strlen($surl) > 0)
									{
										$sname = '<a href="'.$surl.'">'.$sname.'</a>';
									}
								}
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							if ($KEY == 'c_ibel')
							{
								$sname = htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['NAME']);
								if(!$textonly)
								{
									$surl = GetIBlockElementLinkById($arList[$KEY][$FIELD_VALUE_ID]['ID']);
									if ($surl && strlen($surl) > 0)
									{
										$sname = '<a href="'.$surl.'">'.$sname.'</a>';
									}
								}
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'employee')
							{
								$sname = '';
								if(is_array($arList[$KEY][$FIELD_VALUE_ID]))
								{
									$sname = CUser::FormatName(CSite::GetNameFormat(false), $arList[$KEY][$FIELD_VALUE_ID], false, true);
									if(!$textonly)
									{
										$ar['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_user_profile'), array('user_id' => $arList[$KEY][$FIELD_VALUE_ID]['ID']));
										$sname = 	'<a href="'.$ar['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arParams['GRID_ID'].'_'.$arList[$KEY][$FIELD_VALUE_ID]['ID'].'">'.$sname.'</a>'.
											'<script type="text/javascript">BX.tooltip('.$arList[$KEY][$FIELD_VALUE_ID]['ID'].', "balloon_'.$arParams['GRID_ID'].'_'.$arList[$KEY][$FIELD_VALUE_ID]['ID'].'", "");</script>';
									}
								}
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'enumeration')
							{
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['VALUE']);
							}
							else if ($KEY == 'file')
							{
								$fileInfo = $arList[$KEY][$FIELD_VALUE_ID];
								if($textonly)
								{
									$fileUrl = CFile::GetFileSRC($fileInfo);
								}
								else
								{
									$fileUrlTemplate = isset($arOptions['FILE_URL_TEMPLATE'])
										? $arOptions['FILE_URL_TEMPLATE'] : '';

									$fileUrl = $fileUrlTemplate === ''
										? CFile::GetFileSRC($fileInfo)
										: CComponentEngine::MakePathFromTemplate(
											$fileUrlTemplate,
											array('owner_id' => $ID, 'field_name' => $FIELD_NAME, 'file_id' => $fileInfo['ID'])
										);
								}

								$sname = $textonly ? $fileUrl : '<a href="'.htmlspecialcharsbx($fileUrl).'" target="_blank">'.htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['FILE_NAME']).'</a>';
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'crm')
							{
								foreach($FIELD_VALUE_ID as $CID)
								{
									$link = '';
									$title = '';
									$prefix = '';
									if ($FIELD_VALUE_NAME == 'LEAD')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'), array('lead_id' => $CID));
										$title = $arList[$KEY]['LEAD'][$CID]['TITLE'];
										$prefix = 'L';
									}
									elseif ($FIELD_VALUE_NAME == 'CONTACT')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'), array('contact_id' => $CID));
										if(isset($arList[$KEY]['CONTACT'][$CID]))
										{
											$title = CCrmContact::PrepareFormattedName($arList[$KEY]['CONTACT'][$CID]);
										}
										$prefix = 'C';
									}
									elseif ($FIELD_VALUE_NAME == 'COMPANY')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'), array('company_id' => $CID));
										$title = $arList[$KEY]['COMPANY'][$CID]['TITLE'];
										$prefix = 'CO';
									}
									elseif ($FIELD_VALUE_NAME == 'DEAL')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'), array('deal_id' => $CID));
										$title = $arList[$KEY]['DEAL'][$CID]['TITLE'];
										$prefix = 'D';
									}

									$sname = htmlspecialcharsbx($title);
									if(!$textonly)
									{
										$tooltip = '<script type="text/javascript">BX.tooltip('.$CID.', "balloon_'.$ID.'_'.$FIELD_NAME.'_'.$FIELD_VALUE_NAME.'_'.$CID.'", "/bitrix/components/bitrix/crm.'.strtolower($FIELD_VALUE_NAME).'.show/card.ajax.php", "crm_balloon'.($FIELD_VALUE_NAME == 'LEAD' || $FIELD_VALUE_NAME == 'DEAL' || $FIELD_VALUE_NAME == 'QUOTE' ? '_no_photo': '_'.strtolower($FIELD_VALUE_NAME)).'", true);</script>';
										$sname = '<a href="'.$link.'" target="_blank" id="balloon_'.$ID.'_'.$FIELD_NAME.'_'.$FIELD_VALUE_NAME.'_'.$CID.'">'.$sname.'</a>'.$tooltip;
									}
									else
									{
										$sname = "[$prefix]$sname";
									}
									$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
								}
							}
						}
					}
				}
			}
		}
    }

}

AddEventHandler("main", "OnUserTypeBuildList", array("\Micros\Handlers\ProjectBindingFieldType\ProjectBindingFieldType", "GetUserTypeDescription"), 5000);
