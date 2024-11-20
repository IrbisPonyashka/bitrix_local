<?php

namespace Micros\Tasks\Reports;

use Bitrix\Main\Entity;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

/**
 * Class TasksReportTable
 */
class TasksReportTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_reports';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'START_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => 'Дата начала'
			),
			'END_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => 'Дата конца'
			),
			'EMPLOYEE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => 'Сотрудник'
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => 'Задача'
			),
			'STATUS' => array(
				'data_type' => 'enum',
				'required' => true,
                'data_type' => 'enum',
                'values' => [0, 1, 2 ],
				'title' => 'Статус'
			),
			'AGREED_TIME' => array(
				'data_type' => 'integer',
				'title' => 'Согласованное время'
			),
			'ELAPSED_TIME' => array(
				'data_type' => 'integer',
				'title' => 'Затраченное время'
			)

			// references
			// 'EMPLOYEE' => array(
			// 	'data_type' => 'Bitrix\Main\UserTable',
			// 	'reference' => array('=this.USER_ID' => 'ref.ID')
			// ),
			// 'TASK' => array(
			// 	'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
			// 	'reference' => array('=this.TASK_ID' => 'ref.ID')
			// ),
		);
	}

}
