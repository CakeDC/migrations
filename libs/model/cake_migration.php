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
	public $description = '';

/**
 * Migration information
 *
 * This variable will be set while the migration is running and contains:
 * - `name` - File name without extension
 * - `class` - Class name
 * - `version` - What version represent on mapping
 * - `type` - Can be 'app' or a plugin name
 * - `migrated` - Datetime of when it was applied, or null
 *
 * @var array
 * @access public
 */
	public $info = null;

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(),
		'down' => array()
	);

/**
 * Running direction
 *
 * @var string $direction
 * @access public
 */
	public $direction = null;

/**
 * Connection used
 *
 * @var string
 * @access public
 */
	public $connection = 'default';

/**
 * DataSource used
 *
 * @var DataSource
 * @access public
 */
	public $db = null;

/**
 * CakeSchema instance
 *
 * @var CakeSchema
 * @access public
 */
	public $Schema = null;

/**
 * Callback class that will be called before/after every action
 *
 * @var object
 * @access public
 */
	public $callback = null;

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
		return true;
	}

/**
 * Constructor
 *
 * @param array $options optional load object properties
 */
	public function __construct($options = array()) {
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
	public function run($direction) {
		if (!in_array($direction, array('up', 'down'))) {
			throw new MigrationException($this, sprintf(
				__d('migrations', 'Migration direction (%s) is not one of valid directions.', true), $direction
			), E_USER_NOTICE);
		}
		$this->direction = $direction;

		$null = null;
		$this->db =& ConnectionManager::getDataSource($this->connection);
		$this->db->cacheSources = false;
		$this->db->begin($null);
		$this->Schema = new CakeSchema(array('connection' => $this->connection));

		try {
			$this->_invokeCallbacks('beforeMigration', $direction);
			$this->_run();
			$this->_clearCache();
			$this->_invokeCallbacks('afterMigration', $direction);
		} catch (Exception $e) {
			$this->db->rollback($null);
			throw $e;
		}

		return $this->db->commit($null);
	}

/**
 * Run migration commands
 *
 * @return void
 * @access protected
 */
	protected function _run() {
		foreach ($this->migration[$this->direction] as $type => $info) {
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
					throw new MigrationException($this, sprintf(
						__d('migrations', 'Migration action type (%s) is not one of valid actions type.', true), $type
					), E_USER_NOTICE);
			}

			$this->{$methodName}($type, $info);
		}
	}

/**
 * Create Table method
 *
 * @param string $type Type of operation to be done, in this case 'create_table'
 * @param array $tables List of tables to be created
 * @return boolean Return true in case of success, otherwise false
 * @access protected
 */
	protected function _createTable($type, $tables) {
		foreach ($tables as $table => $fields) {
			if (in_array($table, $this->db->listSources())) {
				throw new MigrationException($this, sprintf(
					__d('migrations', 'Table "%s" already exists in database.', true), $table
				));
			}
			$this->Schema->tables = array($table => $fields);

			$this->_invokeCallbacks('beforeAction', 'create_table', array('table' => $table));
			if (@$this->db->execute($this->db->createSchema($this->Schema)) === false) {
				throw new MigrationException($this, sprintf(__d('migrations', 'SQL Error: %s', true), $this->db->error));
			}
			$this->_invokeCallbacks('afterAction', 'create_table', array('table' => $table));
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
	protected function _dropTable($type, $tables) {
		foreach ($tables as $table) {
			if (!in_array($table, $this->db->listSources())) {
				throw new MigrationException($this, sprintf(
					__d('migrations', 'Table "%s" does not exists in database.', true), $table
				));
			}
			$this->Schema->tables = array($table => array());

			$this->_invokeCallbacks('beforeAction', 'drop_table', array('table' => $table));
			if (@$this->db->execute($this->db->dropSchema($this->Schema)) === false) {
				throw new MigrationException($this, sprintf(__d('migrations', 'SQL Error: %s', true), $this->db->error));
			}
			$this->_invokeCallbacks('afterAction', 'drop_table', array('table' => $table));
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
	protected function _renameTable($type, $tables) {
		foreach ($tables as $oldName => $newName) {
			$sources = $this->db->listSources();
			if (!in_array($oldName, $sources)) {
				throw new MigrationException($this, sprintf(
					__d('migrations', 'Table "%s" does not exists in database.', true), $oldName
				));
			} else if (in_array($newName, $sources)) {
				throw new MigrationException($this, sprintf(
					__d('migrations', 'Table "%s" already exists in database.', true), $newName
				));
			}
			$sql = 'RENAME TABLE ' . $this->db->fullTableName($oldName) . ' TO ' . $this->db->fullTableName($newName) . ';';

			$this->_invokeCallbacks('beforeAction', 'rename_table', array('old_name' => $oldName, 'new_name' => $newName));
			if (@$this->db->execute($sql) === false) {
				throw new MigrationException($this, sprintf(__d('migrations', 'SQL Error: %s', true), $this->db->error));
			}
			$this->_invokeCallbacks('afterAction', 'rename_table', array('old_name' => $oldName, 'new_name' => $newName));
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
	protected function _alterTable($type, $tables) {
		foreach ($tables as $table => $fields) {
			$indexes = array();
			if (isset($fields['indexes'])) {
				$indexes = $fields['indexes'];
				unset($fields['indexes']);
			}

			foreach ($fields as $field => $col) {
				$model = new Model(array('table' => $table, 'ds' => $this->connection));
				$tableFields = $this->db->describe($model);

				if ($type === 'drop') {
					$field = $col;
				}
				if ($type !== 'add' && !isset($tableFields[$field])) {
					throw new MigrationException($this, sprintf(
						__d('migrations', 'Field "%s" does not exists in "%s".', true), $field, $table
					));
				}

				switch ($type) {
					case 'add':
						if (isset($tableFields[$field])) {
							throw new MigrationException($this, sprintf(
								__d('migrations', 'Field "%s" already exists in "%s".', true), $field, $table
							));
						}
						$sql = $this->db->alterSchema(array(
							$table => array('add' => array($field => $col))
						));
						break;
					case 'drop':
						$sql = $this->db->alterSchema(array(
							$table => array('drop' => array($field => array()))
						));
						break;
					case 'change':
						$sql = $this->db->alterSchema(array(
							$table => array('change' => array($field => array_merge($tableFields[$field], $col)))
						));
						break;
					case 'rename':
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

				$this->_invokeCallbacks('beforeAction', $type . '_field', $data);
				if (@$this->db->execute($sql) === false) {
					throw new MigrationException($this, sprintf(__d('migrations', 'SQL Error: %s', true), $this->db->error));
				}
				$this->_invokeCallbacks('afterAction', $type . '_field', $data);
			}

			foreach ($indexes as $key => $index) {
				if (is_numeric($key)) {
					$key = $index;
					$index = array();
				}
				$sql = $this->db->alterSchema(array(
					$table => array($type => array('indexes' => array($key => $index)))
				));

				$this->_invokeCallbacks('beforeAction', $type . '_index', array('table' => $table, 'index' => $key));
				if (@$this->db->execute($sql) === false) {
					throw new MigrationException($this, sprintf(__d('migrations', 'SQL Error: %s', true), $this->db->error));
				}
				$this->_invokeCallbacks('afterAction', $type . '_index', array('table' => $table, 'index' => $key));
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
 * @access protected
 */
	protected function _invokeCallbacks($callback, $type, $data = array()) {
		if ($this->callback !== null && method_exists($this->callback, $callback)) {
			if ($callback == 'beforeMigration' || $callback == 'afterMigration') {
				$this->callback->{$callback}($this, $type);
			} else {
				$this->callback->{$callback}($this, $type, $data);
			}
		}
		if ($callback == 'beforeMigration' || $callback == 'afterMigration') {
			$callback = str_replace('Migration', '', $callback);
			if ($this->{$callback}($type)) {
				return true;
			}

			throw new MigrationException($this, sprintf(
				__d('migrations', 'Interrupted when running "%s" callback.', true), $callback
			), E_USER_NOTICE);
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
 * @access protected
 */
	protected function _clearCache() {
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
	public function generateModel($name, $table = null, $options = array()) {
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

/**
 * Exception used when something goes wrong on migrations
 *
 * @package       migrations
 * @subpackage    migrations.libs.model
 */
class MigrationException extends Exception {

/**
 * Reference to the Migration being processed on time the error ocurred

 * @var CakeMigration
 */
	public $Migration;

/**
 * Constructor
 *
 * @param CakeMigration $Migration Reference to the Migration
 * @param string $message Message explaining the error
 * @param int $code Error code
 * @return void
 */
	public function __construct(&$Migration, $message = '', $code = 0) {
		parent::__construct($message, $code);
		$this->Migration = $Migration;
	}
}

?>