<?php

namespace Micros\Tasks\Internals\Task;

use Bitrix\Main\Entity;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

/**
 * Class ElapsedTimeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ElapsedTime_Query query()
 * @method static EO_ElapsedTime_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ElapsedTime_Result getById($id)
 * @method static EO_ElapsedTime_Result getList(array $parameters = [])
 * @method static EO_ElapsedTime_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection wakeUpCollection($rows)
 */
class AgreedTimeTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_agreed_time';
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
			'CREATED_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'DATE_START' => array(
				'data_type' => 'datetime',
			),
			'DATE_STOP' => array(
				'data_type' => 'datetime',
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'MINUTES' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SECONDS' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SOURCE' => array(
				'data_type' => 'integer',
			),
			'COMMENT_TEXT' => array(
				'data_type' => 'text',
			),

			// references
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'TASK' => array(
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
		);
	}

}
