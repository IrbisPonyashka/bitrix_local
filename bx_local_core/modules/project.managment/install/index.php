<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;


Loc::loadMessages(__FILE__);

Class harvest_app extends CModule
{
    public const MODULE_ID = "harvest.app";
    public $MODULE_ID = "harvest.app";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    function __construct()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = "Harvest – модуль для учета времени.";
        $this->MODULE_DESCRIPTION = "Модуль для учета времени на основе приложения Harvest.";
    }


    function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION, $errors;
        
		$errors = false;

        $this->InstallFiles();
        $this->InstallDB();

        ModuleManager::registerModule(self::MODULE_ID);

        $APPLICATION->IncludeAdminFile("Установка модуля ". self::MODULE_ID, $DOCUMENT_ROOT."/local/modules/" . self::MODULE_ID . "/install/step.php");
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/" . self::MODULE_ID . "/install/components",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
        return true;
    }

    function InstallDB()
    {
        global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();
		$errors = null;

        $errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'].'/local/modules/'.self::MODULE_ID.'/install/db/' . $connection->getType() . '/install.sql');
		// if (!$DB->TableExists('b_harvest_project_params'))
		// {
        // }

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode('. ', $errors));
			return false;
		}
        
		return true;
    }

    function DoUnInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION, $errors;
        
		$errors = false;

        $this->UnInstallFiles();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule(self::MODULE_ID);

        $APPLICATION->IncludeAdminFile("Деинсталляция модуля ". self::MODULE_ID, $DOCUMENT_ROOT."/local/modules/" . self::MODULE_ID . "/install/unstep.php");
    }


    function UnInstallDB()
    {
        global $DB, $APPLICATION;
		$connection = \Bitrix\Main\Application::getConnection();
		$errors = null;
        
        $errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'].'/local/modules/'.self::MODULE_ID.'/install/db/' . $connection->getType() . '/uninstall.sql');
		// if ($DB->TableExists('b_harvest_project_params'))
		// {
		// }

		if (!empty($errors))
		{
			$APPLICATION->ThrowException(implode('. ', $errors));
			return false;
		}

        return true;
    }


    function UnInstallFiles()
    {
        DeleteDirFilesEx("/local/components/" . self::MODULE_ID );
        return true;
    }

}
?>