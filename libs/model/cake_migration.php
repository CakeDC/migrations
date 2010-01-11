<?php
/**
 * CakePHP Migrations
 *
 * Copyright 2009 - 2010, Cake Development Corporation
 *                        1785 E. Sahara Avenue, Suite 490-423
 *                        Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2009 - 2010, Cake Development Corporation
 * @link      http://codaset.com/cakedc/migrations/
 * @package   plugns.migrations
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Model', 'CakeSchema', false);

/**
 * Base Class for Migration management
 *
 * @package       migrations
 * @subpackage    migrations.libs.model
 */
class CakeMigration extends Object {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	var $description = '';

/**
 * Migration information
 *
 * This variable will be set while the migration is runned and contains:
 * - `name` - File name without extension
 * - `class` - Class name
 * - `version` - What version represent on mapping
 * - `type` - Can be 'app' or a plugin name
 * - `migrated` - Datetime of when it was applied, or null
 *
 * @var array
 * @access public
 */
	var $info = null;

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	var $migration = array(
		'up' => array(),
		'down' => array()
	);

/**
 * Running direction
 *
 * @var string $direction
 * @access public
 */
	var $direction = null;

/**
 * Connection used
 *
 * @var string
 * @access public
 */
	var $connection = 'default';

/**
 * DataSource used
 *
 * @var DataSource
 * @access public
 */
	var $db = null;

/**
 * CakeSchema instace
 *
 * @var CakeSchema
 * @access public
 */
	var $Schema = null;

/**
 * Callback class that will be called before/after every action
 *
 * @var object
 * @access public
 */
	var $callback = null;

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	function after($direction) {
		return true;
	}

/**
 * Constructor
 *
 * @param array $options optional load object properties
 */
	function __construct($options = array()) {
		parent::__construct();

		if (!empty($options['up'])) {
			$this->migration['up'] = $options['up'];
		}
		if (!empty($options['down'])) {
			$this->migration['down'] = $options['down'];
		}

		$allowed = array('connection', 'callback');
		foreach ($allowed as $variable) {
			if (!empty($options[$variable])) {
				$this->{$variable} = $options[$variable];
			}
		}
	}

/**
 * Run migration
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Status of the process
 * @access public
 */
	function run($direction) {
		if (!in_array($direction, array('up', 'down'))) {
			trigger_error(sprintf(__d('migrations', 'Migration direction (%s) is not one of valid directions.', true), $direction), E_USER_NOTICE);
			return false;
		}
		$this->direction = $direction;

		$this->db =& ConnectionManager::getDataSource($this->connection);
		$this->db->cacheSources = false;
		$this->Schema = new CakeSchema(array('connection' => $this->connection));

		if (!$this->__invokeCallbacks('beforeMigration', $direction)) {
			return false;
		}

		foreach ($this->migration[$direction] as $type => $info) {
			switch ($type) {
				case 'create_table':
					$methodName = '_createTable';
					break;
				case 'drop_table':
					$methodName = '_dropTable';
					break;
				case 'rename_table':
					$methodName = '_renameTable';
					break;
				case 'create_field':
					$type = 'add';
					$methodName = '_alterTable';
					break;
				case 'drop_field':
					$type = 'drop';
					$methodName = '_alterTable';
					break;
				case 'alter_field':
					$type = 'change';
					$methodName = '_alterTable';
					break;
				case 'rename_field':
					$type = 'rename';
					$methodName = '_alterTable';
					break;
				default:
					trigger_error(sprintf(__d('migrations', 'Migration action type (%s) is not one of valid actions type.', true), $type), E_USER_NOTICE);
					continue 2;
			}

			$this->{$methodName}($type, $info);
		}

		$this->__clearCache();
		return $this->__invokeCallbacks('afterMigration', $direction);
	}

/**
 * Create Table method
 *
 * @param string $type Type of operation to be done, in this case 'create_table'
 * @param array $tables List of tables to be created
 * @return boolean Return true in case of success, otherwise false
 * @access protected
 */
	function _createTable($type, $tables) {
		foreach ($tables as $table => $fields) {
			$this->Schema->tables = array($table => $fields);

			$this->__invokeCallbacks('beforeAction', 'create_table', array('table' => $table));
			$this->db->execute($this->db->createSchema($this->Schema));
			$this->__invokeCallbacks('afterAction', 'create_table', array('table' => $table));
		}
		return true;
	}

/**
 * Drop Table method
 *
 * @param string $type Type of operation to be done, in this case 'drop_table'
 * @param array $tables List of tables to be dropped
 * @return boolean Return true in case of success, otherwise false
 * @access protected
 */
	function _dropTable($type, $tables) {
		foreach ($tables as $table) {
			$this->Schema->tables = array($table => array());

			$this->__invokeCallbacks('beforeAction', 'drop_table', array('table' => $table));
			$this->db->execute($this->db->dropSchema($this->Schema));
			$this->__invokeCallbacks('afterAction', 'drop_table', array('table' => $table));
		}
		return true;
	}

/**
 * Rename Table method
 *
 * @param string $type Type of operation to be done, this case 'rename_table'
 * @param array $tables List of tables to be renamed
 * @return boolean Return true in case of success, otherwise false
 * @access protected
 */
	function _renameTable($type, $tables) {
		foreach ($tables as $oldName => $newName) {
			$sql = 'RENAME TABLE ' . $this->db->fullTableName($oldName) . ' TO ' . $this->db->fullTableName($newName) . ';';

			$this->__invokeCallbacks('beforeAction', 'rename_table', array('old_name' => $oldName, 'new_name' => $newName));
			$this->db->execute($sql);
			$this->__invokeCallbacks('afterAction', 'rename_table', array('old_name' => $oldName, 'new_name' => $newName));
		}
		return true;
	}

/**
 * Alter Table method
 *
 * @param string $type Type of operation to be done
 * @param array $tables List of tables and fields
 * @return boolean Return true in case of success, otherwise false
 * @access protected
 */
	function _alterTable($type, $tables) {
		foreach ($tables as $table => $fields) {
			$indexes = array();
			if (isset($fields['indexes'])) {
				$indexes = $fields['indexes'];
				unset($fields['indexes']);
			}

			foreach ($fields as $field => $col) {
				switch ($type) {
					case 'add':
						$sql = $this->db->alterSchema(array(
							$table => array('add' => array($field => $col))
						));
						break;
					case 'drop':
						$field = $col;
						$sql = $this->db->alterSchema(array(
							$table => array('drop' => array($field => array()))
						));
						break;
					case 'change':
						$model = new Model(array('table' => $table, 'ds' => $this->connection));
						$tableFields = $this->db->describe($model);

						$sql = $this->db->alterSchema(array(
							$table => array('change' => array($field => array_merge($tableFields[$field], $col)))
						));
						break;
					case 'rename':
						$model = new Model(array('table' => $table, 'ds' => $this->connection));
						$tableFields = $this->db->describe($model);

						$sql = $this->db->alterSchema(array(
							$table => array('change' => array($field => array_merge($tableFields[$field], array('name' => $col))))
						));
						break;
				}

				if ($type == 'rename') {
					$data = array('table' => $table, 'old_name' => $field, 'new_name' => $col);
				} else {
					$data = array('table' => $table, 'field' => $field);
				}

				$this->__invokeCallbacks('beforeAction', $type . '_field', $data);
				$this->db->execute($sql);
				$this->__invokeCallbacks('afterAction', $type . '_field', $data);
			}

			foreach ($indexes as $key => $index) {
				if (is_numeric($key)) {
					$key = $index;
					$index = array();
				}
				$sql = $this->db->alterSchema(array(
					$table => array($type => array('indexes' => array($key => $index)))
				));

				$this->__invokeCallbacks('beforeAction', $type . '_index', array('table' => $table, 'index' => $key));
				$this->db->execute($sql);
				$this->__invokeCallbacks('afterAction', $type . '_index', array('table' => $table, 'index' => $key));
			}
		}
		return true;
	}

/**
 * This method will invoke the before/afterAction callbacks, it is good when
 * you need track every action.
 *
 * @param string $callback Callback name, beforeMigration, beforeAction, afterAction
 * 		or afterMigration.
 * @param string $type Type of action. i.e: create_table, drop_table, etc.
 * 		Or also can be the direction, for before and after Migration callbacks
 * @param array $data Data to send to the callback
 * @return void
 * @access private
 */
	function __invokeCallbacks($callback, $type, $data = array()) {
		if ($this->callback !== null && method_exists($this->callback, $callback)) {
			if ($callback == 'beforeMigration' || $callback == 'afterMigration') {
				$this->callback->{$callback}($this, $type);
			} else {
				$this->callback->{$callback}($this, $type, $data);
			}
		}
		if ($callback == 'beforeMigration' || $callback == 'afterMigration') {
			$callback = str_replace('Migration', '', $callback);
			return $this->{$callback}($type);
		}
	}

/**
 * Clear all caches present related to models
 *
 * Before the 'after' callback method be called is needed to clear all caches.
 * Without it any model operations will use cached data instead of real/modified
 * data.
 *
 * @return void
 * @access private
 */
	function __clearCache() {
		Cache::clear(false, '_cake_model_');
		ClassRegistry::flush();
	}

/**
 * Generate a instance of model for given options
 *
 * @param string $name Model name to be initialized
 * @param string $table Table name to be initialized
 * @return Model
 * @access public
 */
	function generateModel($name, $table = null, $options = array()) {
		if (empty($table)) {
			$table = Inflector::tableize($name);
		}
		$defaults = array(
			'name' => $name, 'table' => $table, 'ds' => $this->connection
		);
		$options = array_merge($defaults, $options);

		return new AppModel($options);
	}
}
?>