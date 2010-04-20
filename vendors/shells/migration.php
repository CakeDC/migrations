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
App::import('Lib', 'Migrations.MigrationVersion');

/**
 * Migration shell.
 *
 * @package       migrations
 * @subpackage    migrations.vendors.shells
 */
class MigrationShell extends Shell {

/**
 * Connection used
 *
 * @var string
 * @access public
 */
	public $connection = 'default';

/**
 * Current path to load and save migrations
 *
 * @var string
 * @access public
 */
	public $path;

/**
 * Type of migration, can be 'app' or a plugin name
 *
 * @var string
 * @access public
 */
	public $type = 'app';

/**
 * MigrationVersion instance
 *
 * @var MigrationVersion
 * @access public
 */
	public $Version;

/**
 * Messages used to display action being performed
 *
 * @var array
 * @access private
 */
	private $__messages = array();

/**
 * Override startup
 *
 * @return void
 * @access public
 */
	public function startup() {
		$this->_welcome();
		$this->out(__d('migrations', 'Cake Migration Shell', true));
		$this->hr();

		if (!empty($this->params['connection'])) {
			$this->connection = $this->params['connection'];
		}
		if (!empty($this->params['plugin'])) {
			$this->type = $this->params['plugin'];
		}
		$this->path = $this->__getPath() . 'config' . DS . 'migrations' . DS;

		$this->Version =& new MigrationVersion(array(
			'connection' => $this->connection
		));

		$this->__messages = array(
			'create_table' => __d('migrations', 'Creating table :table.', true),
			'drop_table' => __d('migrations', 'Dropping table :table.', true),
			'rename_table' => __d('migrations', 'Renaming table :old_name to :new_name.', true),
			'add_field' => __d('migrations', 'Adding field :field to :table.', true),
			'drop_field' => __d('migrations', 'Dropping field :field from :table.', true),
			'change_field' => __d('migrations', 'Changing field :field from :table.', true),
			'rename_field' => __d('migrations', 'Renaming field :old_name to :new_name on :table.', true),
			'add_index' => __d('migrations', 'Adding index :index to :table.', true),
			'drop_index' => __d('migrations', 'Dropping index :index from :table.', true),
		);
	}

/**
 * Override main
 *
 * @return void
 * @access public
 */
	public function main() {
		$this->run();
	}

/**
 * Run the migrations
 *
 * @return void
 * @access public
 */
	public function run() {
		try {
			$mapping = $this->Version->getMapping($this->type);
		} catch (MigrationVersionException $e) {
			$this->err($e->getMessage());
			return false;
		}

		if ($mapping === false) {
			$this->out(__d('migrations', 'No migrations available.', true));
			return $this->_stop();
		}
		$latestVersion = $this->Version->getVersion($this->type);

		$options = array(
			'type' => $this->type,
			'callback' => &$this
		);
		if (isset($this->args[0]) && in_array($this->args[0], array('up', 'down'))) {
			$options['direction'] = $this->args[0];

			if ($options['direction'] == 'up') {
				$latestVersion++;
			}
			if (!isset($mapping[$latestVersion])) {
				$this->out(__d('migrations', 'Not a valid migration version.', true));
				return $this->_stop();
			}
		} else if (isset($this->args[0]) && $this->args[0] == 'all') {
			end($mapping);
			$options['version'] = key($mapping);
		} else if (isset($this->args[0]) && $this->args[0] == 'reset') {
			$options['version'] = 0;
		} else {
			if (isset($this->args[0]) && is_numeric($this->args[0])) {
				$options['version'] = (int) $this->args[0];

				$valid = isset($mapping[$options['version']]) || ($options['version'] === 0 && $latestVersion > 0);
				if (!$valid) {
					$this->out(__d('migrations', 'Not a valid migration version.', true));
					return $this->_stop();
				}
			} else {
				$this->_showInfo($mapping, $this->type);
				$this->hr();

				while (true) {
					$response = $this->in(__d('migrations', 'Please, choose what version you want to migrate to. [q]uit or [c]lean.', true));
					if (strtolower($response) === 'q') {
						return $this->_stop();
					} else if (strtolower($response) === 'c') {
						$this->_clear();
						continue;
					}

					$valid = is_numeric($response) &&
						(isset($mapping[(int) $response]) || ((int) $response === 0 && $latestVersion > 0));
					if ($valid) {
						$options['version'] = (int) $response;
						break;
					} else {
						$this->out(__d('migrations', 'Not a valid migration version.', true));
					}
				}
				$this->hr();
			}
		}

		$this->out(__d('migrations', 'Running migrations:', true));
		try {
			$this->Version->run($options);
		} catch (MigrationException $e) {
			$this->out(__d('migrations', 'An error occurred when processing the migration:', true));
			$this->out('  ' . sprintf(__d('migrations', 'Migration: %s', true), $e->Migration->info['name']));
			$this->out('  ' . sprintf(__d('migrations', 'Error: %s', true), $e->getMessage()));

			$this->out('');
			return false;
		}

		$this->out(__d('migrations', 'All migrations have completed.', true));
		$this->out('');
		return true;
	}

/**
 * Generate a new migration file
 *
 * @return void
 * @access public
 */
	public function generate() {
		while (true) {
			$name = $this->in(__d('migrations', 'Please enter the descriptive name of the migration to generate:', true));
			if (!preg_match('/^([a-z0-9_]+|\s)+$/', $name)) {
				$this->out('');
				$this->err(sprintf(__d('migrations', 'Migration name (%s) is invalid. It must only contain alphanumeric characters.', true), $name));
			} else {
				$name = str_replace(' ', '_', trim($name));
				break;
			}
		}

		$fromSchema = false;
		$this->Schema = $this->_getSchema();
		$migration = array('up' => array(), 'down' => array());

		$oldSchema = $this->_getSchema($this->type);
		if ($oldSchema !== false) {
			$response = $this->in(__d('migrations', 'Do you want compare the schema.php file to the database?', true), array('y', 'n'), 'y');
			if (strtolower($response) === 'y') {
				$this->hr();
				$this->out(__d('migrations', 'Comparing schema.php to the database...', true));

				if ($this->type !== 'migrations') {
					unset($oldSchema->tables['schema_migrations']);
				}
				$newSchema = $this->_readSchema();
				$comparison = $this->Schema->compare($oldSchema, $newSchema);
				$migration = $this->_fromComparison($migration, $comparison, $oldSchema->tables, $newSchema['tables']);
				$fromSchema = true;
			}
		} else {
			$response = $this->in(__d('migrations', 'Do you want generate a dump from current database?', true), array('y', 'n'), 'y');
			if (strtolower($response) === 'y') {
				$this->hr();
				$this->out(__d('migrations', 'Generating dump from current database...', true));

				$dump = $this->_readSchema();
				$dump = $dump['tables'];
				unset($dump['missing']);

				if (!empty($dump)) {
					$migration['up']['create_table'] = $dump;
					$migration['down']['drop_table'] = array_keys($dump);
				}
				$fromSchema = true;
			}
		}

		$this->out(__d('migrations', 'Generating Migration...', true));
		$class = 'M' . str_replace('-', '', String::uuid());
		$this->_writeMigration($name, $class, $migration);

		$version = 1;
		$map = array();
		if (file_exists($this->path . 'map.php')) {
			include $this->path . 'map.php';
			ksort($map);
			end($map);

			list($version) = each($map);
			$version++;
		}
		$map[$version] = array($name => $class);

		$this->out(__d('migrations', 'Mapping Migrations...', true));
		$this->_writeMap($map);

		if ($fromSchema) {
			$this->Version->setVersion($version, $this->type);
		}

		$this->out('');
		$this->out(__d('migrations', 'Done.', true));
	}

/**
 * Generate a new migration file
 *
 * @see generate
 * @access public
 */
	public function add() {
		return $this->generate();
	}

/**
 * Displays a status of all plugin and app migrations
 *
 * @access public
 * @return void
 */
	public function status() {
		$types = App::objects('plugin');
		ksort($types);
		array_unshift($types, 'App');

		$outdated = (isset($this->args[0]) && $this->args[0] == 'outdated');
		foreach ($types as $name) {
			try {
				$type = Inflector::underscore($name);
				$mapping = $this->Version->getMapping($type);
				$version = $this->Version->getVersion($type);
				$latest = end($mapping);
				if ($outdated && $latest['version'] == $version) {
					continue;
				}

				$this->out(($type == 'app') ? 'Application' : $name . ' Plugin');
				$this->out('');
				$this->out(__d('migrations', 'Current version:', true));
				if ($version != 0) {
					$info = $mapping[$version];
					$this->out('  #' . number_format($info['version'] / 100, 2, '', '') . ' ' . $info['name']);
				} else {
					$this->out('  ' . __d('migrations', 'None applied.', true));
				}

				$this->out(__d('migrations', 'Latest version:', true));
				$this->out('  #' . number_format($latest['version'] / 100, 2, '', '') . ' ' . $latest['name']);
				$this->hr();
			} catch (MigrationVersionException $e) {
				continue;
			}
		}
	}

/**
 * Displays help contents
 *
 * @return void
 * @access public
 */
	public function help() {
		$help = <<<TEXT
The Migration database management for CakePHP
---------------------------------------------------------------
Usage: cake migration <command> <param1> <param2>...
---------------------------------------------------------------
Params:
	-connection <config>
		Set db config <config>. Uses 'default' if none is specified.

	-plugin
		Plugin name to be used

	-f
		Force 'generate' to compare all tables.

Commands:
	migration help
		Shows this help message.

	migration run <up|down|all|reset|version>
		Run a migration to given direction or version.
		Provide a version number to get directly to the version.
		You can also use all to apply all migrations or reset to unapply all.

	migration <generate|add>
		Generates a migration file.
		To force generation of all tables when making a comparison/dump, use the -f param.

	migration status <outdated>
		Displays a status of all plugin and app migrations.
TEXT;

		$this->out($help);
		$this->_stop();
	}

/**
 * Shows a list of available migrations
 *
 * @param array $mapping Migration mapping
 * @param string $type Can be 'app' or a plugin name
 * @return void
 * @access protected
 */
	protected function _showInfo($mapping, $type = null) {
		if ($type === null) {
			$type = $this->type;
		}

		$version = $this->Version->getVersion($type);
		if ($version != 0) {
			$info = $mapping[$version];
			$this->out(__d('migrations', 'Current migration version:', true));
			$this->out('  #' . number_format($version / 100, 2, '', '') . '  ' . $info['name']);
			$this->hr();
		}

		$this->out(__d('migrations', 'Available migrations:', true));
		foreach ($mapping as $version => $info) {
			$this->out('  [' . number_format($version / 100, 2, '', '') . '] ' . $info['name']);

			$this->out('        ', false);
			if ($info['migrated'] !== null) {
				$this->out(__d('migrations', 'applied', true) . ' ' . date('r', strtotime($info['migrated'])));
			} else {
				$this->out(__d('migrations', 'not applied', true));
			}
		}
	}

/**
 * Clear the console
 *
 * @return void
 * @access public
 */
	protected function _clear() {
		$this->Dispatch->clear();
	}

/**
 * Generate a migration string using comparison
 *
 * @param array $migration Migration instructions array
 * @param array $comparison Result from CakeSchema::compare()
 * @param array $oldTables List of tables on schema.php file
 * @param array $currentTables List of current tables on database
 * @return array
 * @access protected
 */
	protected function _fromComparison($migration, $comparison, $oldTables, $currentTables) {
		foreach ($comparison as $table => $actions) {
			if (!isset($oldTables[$table])) {
				$migration['up']['create_table'][$table] = $actions['add'];
				$migration['down']['drop_table'][] = $table;
				continue;
			}

			foreach ($actions as $type => $fields) {
				$indexes = array();
				if (!empty($fields['indexes'])) {
					$indexes = array('indexes' => $fields['indexes']);
					unset($fields['indexes']);
				}

				if ($type == 'add') {
					$migration['up']['create_field'][$table] = array_merge($fields, $indexes);

					$migration['down']['drop_field'][$table] = array_keys($fields);
					if (!empty($indexes['indexes'])) {
						$migration['down']['drop_field'][$table]['indexes'] = array_keys($indexes['indexes']);
					}
				} else if ($type == 'change') {
					$migration['up']['alter_field'][$table] = $fields;
					$migration['down']['alter_field'][$table] = array_intersect_key($oldTables[$table], $fields);
				} else {
					$migration['up']['drop_field'][$table] = array_keys($fields);
					if (!empty($indexes['indexes'])) {
						$migration['up']['drop_field'][$table]['indexes'] = array_keys($indexes['indexes']);
					}

					$migration['down']['create_field'][$table] = array_merge($fields, $indexes);
				}
			}
		}

		foreach ($oldTables as $table => $fields) {
			if (!isset($currentTables[$table])) {
				$migration['up']['drop_table'][] = $table;
				$migration['down']['create_table'][$table] = $fields;
			}
		}
		return $migration;
	}

/**
 * Load and construct the schema class if exists
 *
 * @param string $type Can be 'app' or a plugin name
 * @return mixed False in case of no file found, schema object
 * @access protected
 */
	protected function _getSchema($type = null) {
		if ($type === null) {
			$plugin = ($this->type === 'app') ? null : $this->type;
			return new CakeSchema(array('connection' => $this->connection, 'plugin' => $plugin));
		}
		$file = $this->__getPath($type) . 'config' . DS . 'schema' . DS . 'schema.php';
		if (!file_exists($file)) {
			return false;
		}
		require_once $file;

		$name = Inflector::camelize($type) . 'Schema';
		if ($type == 'app' && !class_exists($name)) {
			$name = Inflector::camelize($this->params['app']) . 'Schema';
		}

		$plugin = ($type === 'app') ? null : $type;
		$schema = new $name(array('connection' => $this->connection, 'plugin' => $plugin));
		return $schema;
	}

/**
 * Reads the schema data
 *
 * @return array
 * @access protected
 */
	protected function _readSchema() {
		$read = $this->Schema->read(array('models' => !isset($this->params['f'])));
		if ($this->type !== 'migrations') {
			unset($read['tables']['schema_migrations']);
		}
		return $read;
	}

/**
 * Generate and write a migration with given name
 *
 * @param string $name Name of migration
 * @param string $class Class name of migration
 * @param array $migration Migration instructions array
 * @return boolean
 * @access protected
 */
	protected function _writeMigration($name, $class, $migration) {
		$content = '';
		foreach ($migration as $direction => $actions) {
			$content .= "\t\t'" . $direction . "' => array(\n";
			foreach ($actions as $type => $tables) {
				$content .= "\t\t\t'" . $type . "' => array(\n";
				if ($type == 'create_table' || $type == 'create_field' || $type == 'alter_field') {
					foreach ($tables as $table => $fields) {
						$content .= "\t\t\t\t'" . $table . "' => array(\n";
						foreach ($fields as $field => $col) {
							if ($field == 'indexes') {
								$content .= "\t\t\t\t\t'indexes' => array(\n";
								foreach ($col as $index => $key) {
									$content .= "\t\t\t\t\t\t'" . $index . "' => array(" . implode(', ',  $this->__values($key)) . "),\n";
								}
								$content .= "\t\t\t\t\t),\n";
							} else {
								$content .= "\t\t\t\t\t'" . $field . "' => ";
								if (is_string($col)) {
									$content .= "'" . $col . "',\n";
								} else {
									$content .= 'array(' . implode(', ',  $this->__values($col)) . "),\n";
								}
							}
						}
						$content .= "\t\t\t\t),\n";
					}
				} else if ($type == 'drop_table') {
					$content .= "\t\t\t\t'" . implode("', '", $tables) . "'\n";
				} else if ($type == 'drop_field') {
					foreach ($tables as $table => $fields) {
						$indexes = array();
						if (!empty($fields['indexes'])) {
							$indexes = $fields['indexes'];
						}
						unset($fields['indexes']);

						$content .= "\t\t\t\t'" . $table . "' => array('" . implode("', '", $fields) . "',";
						if (!empty($indexes)) {
							$content .= " 'indexes' => array('" . implode("', '", $indexes) . "')";
						}
						$content .= "),\n";
					}
				}
				$content .= "\t\t\t),\n";
			}
			$content .= "\t\t),\n";
		}
		$content = $this->__generateTemplate('migration', array('name' => $name, 'class' => $class, 'migration' => $content));

		$File = new File($this->path . $name . '.php', true);
		return $File->write($content);
	}

/**
 * Generate and write the map file
 *
 * @param array $map List of migrations
 * @return boolean
 * @access protected
 */
	protected function _writeMap($map) {
		$content = "<?php\n";
		$content .= "\$map = array(\n";
		foreach ($map as $version => $info) {
			list($name, $class) = each($info);
			$content .= "\t" . $version . " => array(\n";
			$content .= "\t\t'" . $name . "' => '" . $class . "'),\n";
		}
		$content .= ");\n";
		$content .= "?>";

		$File = new File($this->path . 'map.php', true);
		return $File->write($content);
	}

/**
 * Format a array/string into a one-line syntax
 *
 * @param array $values Array to be converted
 * @return string
 * @access private
 */
	private function __values($values) {
		$_values = array();
		if (is_array($values)) {
			foreach ($values as $key => $value) {
				if (is_array($value)) {
					$_values[] = "'" . $key . "' => array('" . implode("', '",  $value) . "')";
				} else if (!is_numeric($key)) {
					$value = var_export($value, true);
					$_values[] = "'" . $key . "' => " . $value;
				}
			}
		}
		return $_values;
	}

/**
 * Include and generate a template string based on a template file
 *
 * @param string $template Template file name
 * @param array $vars List of variables to be used on tempalte
 * @return string
 * @access private
 */
	private function __generateTemplate($template, $vars) {
		extract($vars);
		ob_start();
		ob_implicit_flush(0);
		include(dirname(__FILE__) . DS . 'templates' . DS . $template . '.ctp');
		$content = ob_get_clean();

		return $content;
	}

/**
 * Return the path used
 *
 * @param string $type Can be 'app' or a plugin name
 * @return string Path used
 * @access private
 */
	private function __getPath($type = null) {
		if ($type === null) {
			$type = $this->type;
		}
		if ($type != 'app') {
			return App::pluginPath($type);
		}
		return APP;
	}

/**
 * Callback used to display what migration is being runned
 *
 * @param CakeMigration $Migration Migration being performed
 * @param string $direction Direction being runned
 * @return void
 * @access public
 */
	public function beforeMigration(&$Migration, $direction) {
		$this->out('  [' . number_format($Migration->info['version'] / 100, 2, '', '') . '] ' . $Migration->info['name']);
	}

/**
 * Callback used to create a new line after the migration
 *
 * @param CakeMigration $Migration Migration being performed
 * @param string $direction Direction being runned
 * @return void
 * @access public
 */
	public function afterMigration(&$Migration, $direction) {
		$this->out('');
	}

/**
 * Callback used to display actions being performed
 *
 * @param CakeMigration $Migration Migration being performed
 * @param string $type Type of action. i.e: create_table, drop_table, etc.
 * @param array $data Data to send to the callback
 * @return void
 * @access public
 */
	public function beforeAction(&$Migration, $type, $data) {
		if (isset($this->__messages[$type])) {
			$message = String::insert($this->__messages[$type], $data);
			$this->out('      > ' . $message);
		}
	}
}
?>