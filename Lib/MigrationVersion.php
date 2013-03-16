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

App::uses('CakeMigration', 'Migrations.Lib');
App::uses('ConnectionManager', 'Model');
App::uses('Inflector', 'Utility');
App::uses('Folder', 'Utility');
App::uses('ClassRegistry', 'Utility');

/**
 * Migration version management.
 *
 * @package       migrations
 * @subpackage    migrations.libs
 */
class MigrationVersion {

/**
 * Connection used
 *
 * @var string
 */
	public $connection = 'default';

/**
 * Instance of SchemaMigrations model
 *
 * @var Model
 */
	public $Version;

/**
 * Mapping cache
 *
 * @var array
 */
	private $__mapping = array();

/**
 * Precheck mode
 *
 * @var string
 */
	public $precheck = 'Migrations.PrecheckException';

/**
 * Constructor
 *
 * @param array $options optional load object properties
 */
	public function __construct($options = array()) {
		if (!empty($options['connection'])) {
			$this->connection = $options['connection'];
		}
		if (!empty($options['precheck'])) {
			$this->precheck = $options['precheck'];
		}

		$this->initVersion();

		if (!isset($options['autoinit']) || $options['autoinit'] !== false) {
			$this->__initMigrations();
		}
	}

/**
 * get a new SchemaMigration instance
 *
 * @return void
 */

	public function initVersion() {
		$this->Version = ClassRegistry::init(array(
			'class' => 'Migrations.SchemaMigration',
			'ds' => $this->connection
		));
		$this->Version->setDataSource($this->connection);
	}

/**
 * Get last version for given type
 *
 * @param string $type Can be 'app' or a plugin name
 * @return integer Last version migrated
 */
	public function getVersion($type) {
		$mapping = $this->getMapping($type);
		if ($mapping !== false) {
			krsort($mapping);

			foreach ($mapping as $version => $info) {
				if ($info['migrated'] !== null) {
					return $version;
				}
			}
		}
		return 0;
	}

/**
 * Set current version for given type
 *
 * @param integer $version Current version
 * @param string $type Can be 'app' or a plugin name
 * @param boolean $migrated If true, will add the record to the database
 * 		If false, will remove the record from the database
 * @return boolean
 */
	public function setVersion($version, $type, $migrated = true) {
		if ($type !== 'app') {
			$type = Inflector::camelize($type);
		}
		$mapping = $this->getMapping($type);

		// For BC, 002 was not applied yet.
		$bc = ($this->Version->schema('class') === null);
		$field = $bc ? 'version' : 'class';
		$value = $bc ? $version : $mapping[$version]['class'];

		if ($migrated) {
			$this->Version->create();
			$result = $this->Version->save(array(
				$field => $value, 'type' => $type
			));
		} else {
			$conditions = array(
				$this->Version->alias . '.' . $field => $value,
				$this->Version->alias . '.type' => $type
			);
			$result = $this->Version->deleteAll($conditions);
		}

		// Clear mapping cache
		unset($this->__mapping[$type]);

		return $result;
	}

/**
 * Get mapping for the given type
 *
 * @param string $type Can be 'app' or a plugin name
 * @return mixed False in case of no file found or empty mapping, array with mapping
 */
	public function getMapping($type, $cache = true) {
		if ($type !== 'app') {
			$type = Inflector::camelize($type);
		}

		if ($cache && !empty($this->__mapping[$type])) {
			return $this->__mapping[$type];
		}
		$mapping = $this->_enumerateMigrations($type);
		if (empty($mapping)) {
			return false;
		}

		$migrated = $this->Version->find('all', array(
			'conditions' => array(
				'OR' => array(
					array($this->Version->alias . '.type' => Inflector::underscore($type)),
					array($this->Version->alias . '.type' => $type),
				)
			),
			'recursive' => -1,
		));

		// For BC, 002 was not applied yet.
		$bc = ($this->Version->schema('class') === null);
		if ($bc) {
			$migrated = Set::combine($migrated, '/' . $this->Version->alias . '/version', '/' . $this->Version->alias . '/created');
		} else {
			$migrated = Set::combine($migrated, '/' . $this->Version->alias . '/class', '/' . $this->Version->alias . '/created');
		}

		$bcMapping = array();
		if ($type == 'Migrations') {
			$bcMapping = array(
				'InitMigrations' => 'M4af6e0f0a1284147a0b100ca58157726',
				'ConvertVersionToClassNames' => 'M4ec50d1f7a284842b1b770fdcbdd56cb',
			);
		}

		ksort($mapping);
		foreach ($mapping as $version => $migration) {
			list($name, $class) = each($migration);

			$mapping[$version] = array(
				'version' => $version, 'name' => $name, 'class' => $class,
				'type' => $type, 'migrated' => null
			);

			if ($bc) {
				if (isset($migrated[$version])) {
					$mapping[$version]['migrated'] = $migrated[$version];
				}
			} else {
				if (isset($migrated[$class])) {
					$mapping[$version]['migrated'] = $migrated[$class];
				} elseif (isset($bcMapping[$class]) && !empty($migrated[$bcMapping[$class]])) {
					$mapping[$version]['migrated'] = $migrated[$bcMapping[$class]];
				}
			}
		}

		$this->__mapping[$type] = $mapping;
		return $mapping;
	}

/**
 * Load and make a instance of the migration
 *
 * @param string $name File name where migration resides
 * @param string $class Migration class name
 * @param string $type Can be 'app' or a plugin name
 * @param array $options Extra options to send to CakeMigration class
 * @return boolean|CakeMigration False in case of no file found, instance of the migration
 * @throws MigrationVersionException
 */
	public function getMigration($name, $class, $type, $options = array()) {
		if (!class_exists($class) && (!$this->__loadFile($name, $type) || !class_exists($class))) {
			throw new MigrationVersionException(sprintf(
				__d('migrations', 'Class `%1$s` not found on file `%2$s` for %3$s.'),
				$class, $name . '.php', (($type == 'app') ? 'Application' : Inflector::camelize($type) . ' Plugin')
			));
		}

		$defaults = array(
			'connection' => $this->connection,
			'precheck' => $this->precheck);
		$options = array_merge($defaults, $options);
		return new $class($options);
	}

/**
 * Run the migrations
 *
 * Options:
 * - `direction` - Direction to run
 * - `version` - Until what version want migrate to
 *
 * @param array $options An array with options.
 * @return boolean
 * @throws Exception
 */
	public function run($options) {
		$targetVersion = $latestVersion = $this->getVersion($options['type']);
		$mapping = $this->getMapping($options['type'], false);
		$direction = 'up';
		if (!empty($options['direction'])) {
			$direction = $options['direction'];
		}
		// Check direction and targetVersion
		if (isset($options['version'])) {
			$targetVersion = $options['version'];
			$direction = ($targetVersion < $latestVersion) ? 'down' : $direction;
			if (isset($mapping[$targetVersion]) && empty($mapping[$targetVersion]['migrated'])) {
				$direction = 'up';
			}
		}

		if ($direction === 'up' && !isset($options['version'])) {
			$keys = array_keys($mapping);
			$flipped = array_flip($keys);
			$next = $flipped[$targetVersion + 1];
			$targetVersion = $keys[$next];
		}

		if ($direction == 'down') {
			krsort($mapping);
		}

		foreach ($mapping as $version => $info) {
			if (($direction == 'up' && $version > $targetVersion)
				|| ($direction == 'down' && $version < $targetVersion)) {
				break;
			} else if (($direction == 'up' && $info['migrated'] === null)
				|| ($direction == 'down' && $info['migrated'] !== null)) {

				$migration = $this->getMigration($info['name'], $info['class'], $info['type'], $options);
				$migration->Version = $this;
				$migration->info = $info;

				try {
					$result = $migration->run($direction, $options);
				} catch (Exception $exception){
					$mapping = $this->getMapping($options['type']);
					$latestVersionName = '#' . number_format($mapping[$latestVersion]['version'] / 100, 2, '', '') . ' ' . $mapping[$latestVersion]['name'];
					$errorMessage = __d('migrations', sprintf("There was an error during a migration. \n The error was: '%s' \n You must resolve the issue manually and try again.", $exception->getMessage(), $latestVersionName));
					return $errorMessage;
				}

				$this->setVersion($version, $info['type'], ($direction == 'up'));
			}
		}

		if (isset($result)) {
			return $result;
		}

		return true;
	}

/**
 * Resets the migration to 0.
 * @param $type string type of migration being ran
 * @return void
 */
	protected function resetMigration($type) {
		$options['type'] = $type;
		$options['version'] = 0;
		$options['reset'] = true;
		$options['direction'] = 'down';
		$this->run($options);
	}

/**
 * Runs migration to the last well known version defined by $toVersion.
 * @param $toVersion string name of the version where the migration will run up to.
 * @param $type string type of migration being ran.
 * @return void
 */
	protected function restoreMigration($toVersion, $type) {
		$options['type'] = $type;
		$options['direction'] = 'up';
		$options['version'] = $toVersion;
		$this->run($options);
	}

/**
 * Initialize the migrations schema and keep it up-to-date
 *
 * @return void
 */
	private function __initMigrations() {
		$db = ConnectionManager::getDataSource($this->connection);
		if (!in_array($db->fullTableName('schema_migrations', false, false), $db->listSources())) {
			$map = $this->_enumerateMigrations('migrations');

			list($name, $class) = each($map[1]);
			$migration = $this->getMigration($name, $class, 'Migrations');
			$migration->run('up');
			$this->setVersion(1, 'Migrations');
		}

		$mapping = $this->getMapping('Migrations');
		if (count($mapping) > 1) {
			end($mapping);
			$this->run(array(
				'version' => key($mapping),
				'type' => 'Migrations'
			));
		}
	}

/**
 * Load a file based on name and type
 *
 * @param string $name File name to be loaded
 * @param string $type Can be 'app' or a plugin name
 * @return mixed Throw an exception in case of no file found, array with mapping
 * @throws MigrationVersionException
 */
	private function __loadFile($name, $type) {
		$path = APP . 'Config' . DS . 'Migration' . DS;
		if ($type != 'app') {
			$path = CakePlugin::path(Inflector::camelize($type)) . 'Config' . DS . 'Migration' . DS;
		}

		if (!file_exists($path . $name . '.php')) {
			throw new MigrationVersionException(sprintf(
				__d('migrations', 'File `%1$s` not found in the %2$s.'),
				$name . '.php', (($type == 'app') ? 'Application' : Inflector::camelize($type) . ' Plugin')
			));
		}

		include $path . $name . '.php';
		return true;
	}

/**
 * Returns a map of all available migrations for a type (app or plugin)
 *
 * @param string $type Can be 'app' or a plugin name
 * @return array containing a list of migration versions ordered by filename
 */
	protected function _enumerateMigrations($type) {
		$map = $this->_enumerateNewMigrations($type);
		$oldMap = $this->_enumerateOldMigrations($type);
		foreach ($oldMap as $k => $v) {
			$map[$k] = $oldMap[$k];
		}
		return $map;
	}

/**
 * Returns a map of all available migrations for a type (app or plugin) using inflection
 *
 * @param string $type Can be 'app' or a plugin name
 * @return array containing a list of migration versions ordered by filename
 */
	protected function _enumerateNewMigrations($type) {
		$mapping = array();
		$path = APP . 'Config' . DS . 'Migration' . DS;
		if ($type != 'app') {
			$path = CakePlugin::path(Inflector::camelize($type)) . 'Config' . DS . 'Migration' . DS;
		}
		if (!file_exists($path)) {
			return $mapping;
		}
		$folder = new Folder($path);
		foreach ($folder->find('.*?\.php', true) as $file) {
			$parts = explode('_', $file);
			$version = array_shift($parts);
			$className = implode('_', $parts);
			if ($version > 0 && strlen($className) > 0) {
				$mapping[(int)$version] = array(substr($file, 0, -4) => Inflector::camelize(substr($className, 0, -4)));
			}
		}
		return $mapping;
	}

/**
 * Returns a map of all available migrations for a type (app or plugin) using regular expressions
 *
 * @param string $type Can be 'app' or a plugin name
 * @return array containing a list of migration versions ordered by filename
 */
	protected function _enumerateOldMigrations($type) {
		$mapping = array();
		$path = APP . 'Config' . DS . 'Migration' . DS;
		if ($type != 'app') {
			$path = CakePlugin::path(Inflector::camelize($type)) . 'Config' . DS . 'Migration' . DS;
		}
		if (!file_exists($path)) {
			return $mapping;
		}
		$folder = new Folder($path);
		foreach ($folder->find('.*?\.php', true) as $file) {
			$parts = explode('_', $file);
			$version = array_shift($parts);
			$className = implode('_', $parts);
			if ($version > 0 && strlen($className) > 0) {
				$contents = file_get_contents($path . $file);
				if (preg_match("/class\s([\w]+)\sextends/", $contents, $matches)) {
					$mapping[(int)$version] = array(substr($file, 0, -4) => $matches[1]);
				}
			}
		}
		return $mapping;
	}
}

/**
 * Usually used when migrations file/class or map files are not found
 *
 * @package       migrations
 * @subpackage    migrations.libs
 */
class MigrationVersionException extends Exception {

}

