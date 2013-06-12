<?php
/**
 * CakePHP Migrations
 *
 * Copyright 2009 - 2013, Cake Development Corporation
 *                        1785 E. Sahara Avenue, Suite 490-423
 *                        Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2009 - 2013, Cake Development Corporation
 * @link      http://codaset.com/cakedc/migrations/
 * @package   plugns.migrations
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CakeSchema', 'Model');
App::uses('MigrationVersion', 'Migrations.Lib');
App::uses('String', 'Utility');
App::uses('ClassRegistry', 'Utility');

/**
 * Migration shell.
 *
 * @package       migrations
 * @subpackage    migrations.vendors.shells
 */
class MigrationShell extends AppShell {

/**
 * Connection used
 *
 * @var string
 */
	public $connection = 'default';

/**
 * Current path to load and save migrations
 *
 * @var string
 */
	public $path;

/**
 * Type of migration, can be 'app' or a plugin name
 *
 * @var string
 */
	public $type = 'app';

/**
 * MigrationVersion instance
 *
 * @var MigrationVersion
 */
	public $Version;

/**
 * Messages used to display action being performed
 *
 * @var array
 */
	private $__messages = array();

/**
 * Override startup
 *
 * @return void
 */
	public function startup() {
		$this->out(__d('migrations', 'Cake Migration Shell'));
		$this->hr();

		if (!empty($this->params['connection'])) {
			$this->connection = $this->params['connection'];
		}

		if (!empty($this->params['plugin'])) {
			$this->type = $this->params['plugin'];
		}

		$this->path = $this->_getPath() . 'Config' . DS . 'Migration' . DS;

		$this->Version = new MigrationVersion(array(
			'precheck' => $this->params['precheck'],
			'connection' => $this->connection,
			'autoinit' => !$this->params['no-auto-init']));

		$this->__messages = array(
			'create_table' => __d('migrations', 'Creating table :table.'),
			'drop_table' => __d('migrations', 'Dropping table :table.'),
			'rename_table' => __d('migrations', 'Renaming table :old_name to :new_name.'),
			'add_field' => __d('migrations', 'Adding field :field to :table.'),
			'drop_field' => __d('migrations', 'Dropping field :field from :table.'),
			'change_field' => __d('migrations', 'Changing field :field from :table.'),
			'rename_field' => __d('migrations', 'Renaming field :old_name to :new_name on :table.'),
			'add_index' => __d('migrations', 'Adding index :index to :table.'),
			'drop_index' => __d('migrations', 'Dropping index :index from :table.'),
		);
	}

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
				'The Migration shell.' .
				'')
			->addOption('plugin', array(
				'short' => 'p',
				'help' => __('Plugin name to be used')))
			->addOption('precheck', array(
				'short' => 'm',
				'default' => 'Migrations.PrecheckException',
				'help' => __('Precheck migrations')))
			->addOption('force', array(
				'short' => 'f',
				'boolean' => true,
				'help' => __('Force \'generate\' to compare all tables.')))
			->addOption('connection', array(
				'short' => 'c',
				'default' => 'default',
				'help' => __('Set db config <config>. Uses \'default\' if none is specified.')))
			->addOption('no-auto-init', array(
				'short' => 'n',
				'boolean' => true,
				'default' => false,
				'help' => __('Disables automatic creation of migrations table and running any internal plugin migrations')))
			->addSubcommand('status', array(
				'help' => __('Displays a status of all plugin and app migrations.')))
			->addSubcommand('run', array(
				'help' => __('Run a migration to given direction or version.')))
			->addSubcommand('generate', array(
				'help' => __('Generates a migration file.')));
	}

/**
 * Override main
 *
 * @return void
 */
	public function main() {
		$this->out($this->getOptionParser()->help());
	}

/**
 * Run the migrations
 *
 * @return void
 */
	public function run() {
		try {
			$mapping = $this->Version->getMapping($this->type);
		} catch (MigrationVersionException $e) {
			$this->err($e->getMessage());
			return false;
		}

		if ($mapping === false) {
			$this->out(__d('migrations', 'No migrations available.'));
			return $this->_stop(1);
		}
		$latestVersion = $this->Version->getVersion($this->type);

		$options = array(
			'precheck' => isset($this->params['precheck']) ? $this->params['precheck'] : null,
			'type' => $this->type,
			'callback' => &$this);

		$once = false; //In case of exception run shell again (all, reset, migration number)
		if (isset($this->args[0]) && in_array($this->args[0], array('up', 'down'))) {
			$once = true;
			$options = $this->_singleStepOptions($mapping, $latestVersion, $options);
		} else if (isset($this->args[0]) && $this->args[0] == 'all') {
			end($mapping);
			$options['version'] = key($mapping);
			$options['direction'] = 'up';
		} else if (isset($this->args[0]) && $this->args[0] == 'reset') {
			$options['version'] = 0;
			$options['reset'] = true;
			$options['direction'] = 'down';
		} else {
			$options = $this->_promptVersionOptions($mapping, $latestVersion);
		}

		$this->out(__d('migrations', 'Running migrations:'));
		if ($options === false) {
			return false;
		}
		$options += array(
			'type' => $this->type,
			'callback' => &$this
		);
		$result = $this->_execute($options, $once);
		if ($result !== true) {
			$this->out(__d('migrations', $result));
		}

		$this->out(__d('migrations', 'All migrations have completed.'));
		$this->out('');
		return true;
	}

	protected function _execute($options, $once) {
		$result = true;
		try {
			$result = $this->Version->run($options);
		} catch (MigrationException $e) {
			$this->out(__d('migrations', 'An error occurred when processing the migration:'));
			$this->out('  ' . sprintf(__d('migrations', 'Migration: %s'), $e->Migration->info['name']));
			$this->out('  ' . sprintf(__d('migrations', 'Error: %s'), $e->getMessage()));

			$this->hr();

			$response = $this->in(__d('migrations', 'Do you want to mark the migration as successful?. [y]es or [a]bort.'), array('y', 'a'));

			if (strtolower($response) === 'y') {
				$this->Version->setVersion($e->Migration->info['version'], $this->type, $options['direction'] === 'up');
				if (!$once) {
					return $this->run();
				}
			} else if (strtolower($response) === 'a') {
				return $this->_stop();
			}
			$this->hr();
		}
		return $result;
	}

	protected function _singleStepOptions($mapping, $latestVersion, $default = array()) {
		$options = $default;
		$versions = array_keys($mapping);
		$flipped = array_flip($versions);
		$versionNumber = isset($flipped[$latestVersion]) ? $flipped[$latestVersion] : -1;
		$options['direction'] = $this->args[0];

		if ($options['direction'] == 'up') {
			$latestVersion = isset($versions[$versionNumber + 1]) ? $versions[$versionNumber + 1] : -1;
		}
		if (!isset($mapping[$latestVersion])) {
			$this->out(__d('migrations', 'Not a valid migration version.'));
			return $this->_stop(2);
		}
		$options['version'] = $mapping[$latestVersion]['version'];
		return $options;
	}

	protected function _promptVersionOptions($mapping, $latestVersion) {
		if (isset($this->args[0]) && is_numeric($this->args[0])) {
			$options['version'] = (int)$this->args[0];

			$valid = isset($mapping[$options['version']]) || ($options['version'] === 0 && $latestVersion > 0);
			if (!$valid) {
				$this->out(__d('migrations', 'Not a valid migration version.'));
				return $this->_stop();
			}
		} else {
			$this->_showInfo($mapping, $this->type);
			$this->hr();

			while (true) {
				$response = $this->in(__d('migrations', 'Please, choose what version you want to migrate to. [q]uit or [c]lean.'));
				if (strtolower($response) === 'q') {
					return $this->_stop();
				} else if (strtolower($response) === 'c') {
					$this->_clear();
					continue;
				}

				$valid = is_numeric($response) && isset($mapping[(int)$response]);
				if ($valid) {
					$options['version'] = (int)$response;
					$direction = 'up';
					if (empty($mapping[(int)$response]['migrated'])) {
						$direction = 'up';
					} else if ((int)$response <= $latestVersion) {
						$direction = 'down';
					}
					break;
				} else {
					$this->out(__d('migrations', 'Not a valid migration version.'));
				}
			}
			$this->hr();
		}
		return compact('direction') + $options;
	}

/**
 * Generate a new migration file
 *
 * @return void
 */
	public function generate() {
		$fromSchema = false;
		$this->Schema = $this->_getSchema();
		$migration = array('up' => array(), 'down' => array());

		$oldSchema = $this->_getSchema($this->type);
		if ($oldSchema !== false) {
			$response = $this->in(__d('migrations', 'Do you want compare the schema.php file to the database?'), array('y', 'n'), 'y');
			if (strtolower($response) === 'y') {
				$this->hr();
				$this->out(__d('migrations', 'Comparing schema.php to the database...'));

				if ($this->type !== 'migrations') {
					unset($oldSchema->tables['schema_migrations']);
				}
				$newSchema = $this->_readSchema();
				$comparison = $this->Schema->compare($oldSchema, $newSchema);
				$migration = $this->_fromComparison($migration, $comparison, $oldSchema->tables, $newSchema['tables']);

				$fromSchema = true;
			}
		} else {
			$response = $this->in(__d('migrations', 'Do you want generate a dump from current database?'), array('y', 'n'), 'y');
			if (strtolower($response) === 'y') {
				$this->hr();
				$this->out(__d('migrations', 'Generating dump from current database...'));

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

		$response = $this->in(__d('migrations', 'Do you want to preview the file before generation?'), array('y', 'n'), 'y');
		if (strtolower($response) === 'y') {
			$this->out($this->_generateMigration('', 'PreviewMigration', $migration));
		}

		while (true) {
			$name = $this->in(__d('migrations', 'Please enter the descriptive name of the migration to generate:'));
			if (!preg_match('/^([A-Za-z0-9_]+|\s)+$/', $name) || is_numeric($name[0])) {
				$this->out('');
				$this->err(__d('migrations', 'Migration name (%s) is invalid. It must only contain alphanumeric characters and start with a letter.', $name));
			} elseif (strlen($name) > 255) {
				$this->out('');
				$this->err(__d('migrations', 'Migration name (%s) is invalid. It cannot be longer than 255 characters.', $name));
			} else {
				$name = str_replace(' ', '_', trim($name));
				break;
			}
		}

		$this->out(__d('migrations', 'Generating Migration...'));
		$time = gmdate('U');
		$this->_writeMigration($name, $time, $migration);

		if ($fromSchema) {
			$this->Version->setVersion($time, $this->type);
		}

		$this->out('');
		$this->out(__d('migrations', 'Done.'));

		if ($fromSchema && isset($comparison)) {
			$response = $this->in(__d('migrations', 'Do you want update the schema.php file?'), array('y', 'n'), 'y');
			if (strtolower($response) === 'y') {
				$this->_updateSchema();
			}
		}
	}

/**
 * Displays a status of all plugin and app migrations
 *
 * @return void
 */
	public function status() {
		$types = CakePlugin::loaded();
		ksort($types);
		array_unshift($types, 'App');

		$outdated = (isset($this->args[0]) && $this->args[0] == 'outdated');
		foreach ($types as $name) {
			try {
				$type = Inflector::underscore($name);
				$mapping = $this->Version->getMapping($type);
				if ($mapping === false) {
					continue;
				}

				$version = $this->Version->getVersion($type);
				$latest = end($mapping);
				if ($outdated && $latest['version'] == $version) {
					continue;
				}

				$this->out(($type == 'app') ? 'Application' : $name . ' Plugin');
				$this->out('');
				$this->out(__d('migrations', 'Current version:'));
				if ($version != 0) {
					$info = $mapping[$version];
					$this->out('  #' . number_format($info['version'] / 100, 2, '', '') . ' ' . $info['name']);
				} else {
					$this->out('  ' . __d('migrations', 'None applied.'));
				}

				$this->out(__d('migrations', 'Latest version:'));
				$this->out('  #' . number_format($latest['version'] / 100, 2, '', '') . ' ' . $latest['name']);
				$this->hr();
			} catch (MigrationVersionException $e) {
				continue;
			}
		}
	}

/**
 * Shows a list of available migrations
 *
 * @param array $mapping Migration mapping
 * @param string $type Can be 'app' or a plugin name
 * @return void
 */
	protected function _showInfo($mapping, $type = null) {
		if ($type === null) {
			$type = $this->type;
		}

		$version = $this->Version->getVersion($type);
		if ($version != 0) {
			$info = $mapping[$version];
			$this->out(__d('migrations', 'Current migration version:'));
			$this->out('  #' . number_format($version / 100, 2, '', '') . '  ' . $info['name']);
			$this->hr();
		}

		$this->out(__d('migrations', 'Available migrations:'));
		foreach ($mapping as $version => $info) {
			$this->out('  [' . number_format($version / 100, 2, '', '') . '] ' . $info['name']);

			$this->out('        ', false);
			if ($info['migrated'] !== null) {
				$this->out(__d('migrations', 'applied') . ' ' . date('r', strtotime($info['migrated'])));
			} else {
				$this->out(__d('migrations', 'not applied'));
			}
		}
	}

/**
 * Clear the console
 *
 * @return void
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
					foreach ($fields as $name => $col) {
						if (!empty($oldTables[$table][$name]['length']) && substr($col['type'], 0, 4) == 'date') {
							$fields[$name]['length'] = null;
						}
					}
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
 */
	protected function _getSchema($type = null) {
		if ($type === null) {
			$plugin = ($this->type === 'app') ? null : $this->type;
			return new CakeSchema(array('connection' => $this->connection, 'plugin' => $plugin));
		}
		$file = $this->_getPath($type) . 'Config' . DS . 'Schema' . DS . 'schema.php';
		if (!file_exists($file)) {
			return false;
		}
		require_once $file;

		$name = Inflector::camelize($type) . 'Schema';

		if ($type == 'app' && !class_exists($name)) {
			$appDir = str_replace('-', '', APP_DIR);
			$name = Inflector::camelize($appDir) . 'Schema';
		}

		$plugin = ($type === 'app') ? null : $type;
		$schema = new $name(array('connection' => $this->connection, 'plugin' => $plugin));
		return $schema;
	}

/**
 * Reads the schema data
 *
 * @return array
 */
	protected function _readSchema() {
		$read = $this->Schema->read(array('models' => !$this->params['force']));
		if ($this->type !== 'migrations') {
			unset($read['tables']['schema_migrations']);
		}
		return $read;
	}

/**
 * Update the schema, making a call to schema shell
 *
 * @return void
 */
	protected function _updateSchema() {
		$command = 'schema generate --connection ' . $this->connection;
		if (!empty($this->params['plugin'])) {
			$command .= ' --plugin ' . $this->params['plugin'];
		}
		if ($this->params['force']) {
			$command .= ' --force';
		}
		$this->dispatchShell($command);
	}

/**
 * Generate a migration
 *
 * @param string $name Name of migration
 * @param string $class Class name of migration
 * @param array $migration Migration instructions array
 * @return string
 */
	protected function _generateMigration($name, $class, $migration) {
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
		return $content;
	}

/**
 * Write a migration with given name
 *
 * @param string $name Name of migration
 * @param int the version number (timestamp)
 * @param array $migration Migration instructions array
 * @return boolean
 */
	protected function _writeMigration($name, $version, $migration) {
		$content = '';
		$content = $this->_generateMigration($name, Inflector::camelize($name), $migration);
		$File = new File($this->path . $version . '_' . strtolower($name) . '.php', true);
		return $File->write($content);
	}

/**
 * Format a array/string into a one-line syntax
 *
 * @param array $values Array to be converted
 * @return string
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
 */
	private function __generateTemplate($template, $vars) {
		extract($vars);
		ob_start();
		ob_implicit_flush(0);
		include (dirname(__FILE__) . DS . 'Templates' . DS . $template . '.ctp');
		$content = ob_get_clean();

		return $content;
	}

/**
 * Return the path used
 *
 * @param string $type Can be 'app' or a plugin name
 * @return string Path used
 */
	protected function _getPath($type = null) {
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
 */
	public function beforeAction(&$Migration, $type, $data) {
		if (isset($this->__messages[$type])) {
			$message = String::insert($this->__messages[$type], $data);
			$this->out('      > ' . $message);
		}
	}

}
