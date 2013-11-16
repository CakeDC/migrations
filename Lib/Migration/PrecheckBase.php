<?php
/**
 * CakePHP Migrations
 *
 * Copyright 2009 - 2013, Cake Development Corporation
 *						1785 E. Sahara Avenue, Suite 490-423
 *						Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2009 - 2013, Cake Development Corporation
 * @link	  http://codaset.com/cakedc/migrations/
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class PrecheckBase {

/**
 * @var CakeMigration
 */
	protected $_migration;

/**
 * Perform check before field create.
 *
 * @param string $table
 * @param string $field
 * @return boolean
 */
	abstract public function checkAddField($table, $field);

/**
 * Perform check before table create.
 *
 * @param string $table
 * @return boolean
 */
	abstract public function checkCreateTable($table);

/**
 * Perform check before table drop.
 *
 * @param string $table
 * @return boolean
 */
	abstract public function checkDropTable($table);

/**
 * Perform check before field drop.
 *
 * @param string $table
 * @param string $field
 * @return boolean
 */
	abstract public function checkDropField($table, $field);

/**
 * Check that table exists.
 *
 * @param string $table
 * @return boolean
 */
	public function tableExists($table) {
		$this->_migration->db->cacheSources = false;
		$tables = $this->_migration->db->listSources();
		return in_array($this->_migration->db->fullTableName($table, false, false), $tables);
	}

/**
 * Check that field exists.
 *
 * @param string $table
 * @param string $field
 * @return boolean
 */
	public function fieldExists($table, $field) {
		if (!$this->tableExists($table)) {
			return false;
		}
		$fields = $this->_migration->db->describe($table);
		return !empty($fields[$field]);
	}

/**
 * Before action precheck callback.
 *
 * @param $migration
 * @param string $type
 * @param array $data
 * @throws MigrationException
 * @return boolean
 */
	public function beforeAction($migration, $type, $data) {
		$this->_migration = $migration;
		switch ($type) {
			case 'create_table':
				return $this->checkCreateTable($data['table']);
			case 'drop_table':
				return $this->checkDropTable($data['table']);
			case 'rename_table':
				return $this->checkCreateTable($data['new_name']) && $this->checkDropTable($data['old_name']);
			case 'add_field':
				return $this->checkAddField($data['table'], $data['field']);
			case 'drop_field':
				return $this->checkDropField($data['table'], $data['field']);
			case 'change_field':
				return true;
			case 'rename_field':
				return $this->checkAddField($data['table'], $data['new_name']) && $this->checkDropField($data['table'], $data['old_name']);
			case 'add_index':
			case 'drop_index':
				return true;
			default:
				throw new MigrationException($this->_migration, sprintf(
					__d('migrations', 'Migration action type (%s) is not one of valid actions type.'), $type
				), E_USER_NOTICE);
		}
	}

}
