<?php

namespace IrbisPonyashka\Harvest;

use Bitrix\Main\Loader;
use Bitrix\Main\Entity;


class HarvestProjectsTable extends Entity\DataManager
{

    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_harvest_project_params';
    }

    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
            ),
            'PROJECT_ID' => array(
                'data_type' => 'integer',
                'title' => "Проект",
            ),
            'ESTIMATED_TIME' => array(
                'data_type' => 'string',
                'title' => "Затрачиваемое время",
            ),
            'CUSTOMER_ID' => array(
                'data_type' => 'integer',
                'title' => "Клиент",
            ),
            'CUSTOMER_TYPE' => array(
                'data_type' => 'enum',
                'title' => "",
                'values' => ['COMPANY', 'CONTACT'],
            ),
        );
    }

}
