<?php
 
use Bitrix\Main\Loader;
use Bitrix\Main\EventManager; 
use Bitrix\Crm;
use Bitrix\Crm\UserField\UserFieldHistory;
use Bitrix\Crm\UserField;

// require dirname(__FILE__)."/lang/".LANGUAGE_ID."/handlers.php";

Loader::includeModule("crm");
$eventManager = \Bitrix\Main\EventManager::getInstance();
 
class ProjectBindTypeClass extends Bitrix\Main\UserField\Types\StringType
{
    public const
        USER_TYPE_ID = 'project_bynd_type';
        // RENDER_COMPONENT = 'multibank:main.field.multibank_request_data';


	static function getDescription(): array
	{
		return array(
			"USER_TYPE_ID" => self::USER_TYPE_ID,
			'DESCRIPTION' => 'Привязка к группе/проекту (crm)',
			'CLASS_NAME' => __CLASS__,
			'BASE_TYPE' => CUserTypeManager::BASE_TYPE_STRING,
			'EDIT_CALLBACK' => array( __CLASS__, 'GetPublicEdit' ),
			'VIEW_CALLBACK' => array( __CLASS__, 'GetPublicView' )
		);
	}

    // Отображение значения в списке админки
    static function GetAdminListViewHTML($arUserField, $arHtmlControl)
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
    public static function GetEditFormHTML(array $userField, ?array $additionalParameters): string
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
    // public static function GetDBColumnType ($arUserField)
    // {
    //     global $DB;
        
    //     // Bitrix\Main\Diag\Debug::writeToFile($arUserField, "arUserField", "/home/bitrix/www/bitrix/local/php_interface/micros/logs/arUserField_projectLink.log");
    //     self::LogB($result, $comment);

    //     switch (strtolower($DB->type)) {
    //         case "mysql":
    //             return "varchar(255) not null";  // Добавьте "not null" или нужные ограничения
    //         case "oracle":
    //             return "varchar2(2000 char)";
    //         case "mssql":
    //             return "varchar(2000)";
    //         default:
    //             throw new \Exception("Unsupported DB type");
    //     }
    // }

    public static function GetPublicView (array $userField, ?array $additionalParameters = []): string
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

    public static function GetPublicEdit (array $userField, ?array $additionalParameters = []): string
    {
        // global $APPLICATION;

        // ob_start();
        // $APPLICATION->IncludeComponent(
        //     "micros:system.field.edit",
        //     "projectLink",
        //     array(
        //         "arUserField" => $arUserField,
        //         "arAdditionalParameters" => $arAdditionalParameters
        //     )
        // );
        // $componentResult = ob_get_clean();
		
        // return $componentResult;
        return $userField;
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

AddEventHandler("main", "OnUserTypeBuildList", array("ProjectBindTypeClass", "getDescription"));