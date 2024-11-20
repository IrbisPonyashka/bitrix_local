<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Grid\Columns;
use Bitrix\Main\Grid\Row\Rows;
use Bitrix\Main\Grid\Panel\Panel;
use Bitrix\Main\Filter\Filter;

class MyGrid extends Grid
{
    public function getVisibleColumns()
    {
        return $this->getGridData();
    }
}
