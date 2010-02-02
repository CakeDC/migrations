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
App::import('Model', 'Migrations.CakeMigration', false);

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
 * @access public
 */
	public $connection = 'default';

/**
 * Instance of SchemaMigrations model
 *
 * @var Model
 * @access public
 */
	public $Version;

/**
 * Mapping cache
 *
 * @var array
 * @access private
 */
	private $__mapping = array();

/**
 * Constructor
 *
 * @param array $options optional load object properties
 */
	public function __construct($options = array()) {
		if (!empty($options['connection'])) {
			$this->connection = $options['connection'];
		}

		$this->__initMigrations();
	}

/**
 * Get last version for given type
 *
 * @param string $type Can be 'app' or a plugin name
 * @return integer Last version migrated
 * @access public
 */
	public function getVersion($type) {
		$version = $this->Version->find('first', array(
			'fields' => array('version'),
			'conditions' => array($this->Version->alias . '.type' => $type),
			'order' => array($this->Version->alias . '.version' => 'DESC'),
			'recursive' => -1,
		));

		if (empty($version)) {
			return 0;
		} else {
			return $version[$this->Version->alias]['version'];
		}
	}

/**
 * Set current version for given type
 *
 * @param integer $version Current version
 * @param string $type Can be 'app' or a plugin name
 * @param boolean $migrated If true, will add the record to the database
 * 		If false, will remove the record from the database
 * @return boolean
 * @access public
 */
	public function setVersion($version, $type, $migrated = true) {
		if ($migrated) {
			$this->Version->create();
			return $this->Version->save(array(
				'version' => $version, 'type' => $type
			));
		} else {
			$conditions = array(
				$this->Version->alias . '.version' => $version,
				$this->Version->alias . '.type' => $type
			);
			return $this->Version->deleteAll($conditions);
		}
	}

/**
 * Get mapping for the given type
 *
 * @param string $type Can be 'app' or a plugin name
 * @return mixed False in case of no file found, array with mapping
 * @access public
 */
	public function getMapping($type) {
		if (!empty($this->__mapping[$type])) {
			return $this->__mapping[$type];
		}

		$mapping = $this->__loadFile('map', $type);
		if ($mapping === false) {
			return false;
		}

		$migrated = $this->Version->find('all', array(
			'fields' => array('version', 'created'),
			'conditions' => array($this->Version->alias . '.type' => $type),
			'order' => array($this->Version->alias . '.version' => 'ASC'),
			'recursive' => -1,
		));
		$migrated = Set::combine($migrated, '/' . $this->Version->alias . '/version', '/' . $this->Version->alias . '/created');

		ksort($mapping);
		foreach ($mapping as $version => $migration) {
			list($name, $class) = each($migration);

			$mapping[$version] = array(
				'version' => $version, 'name' => $name, 'class' => $class,
				'type' => $type, 'migrated' => null
			);
			if (isset($migrated[$version])) {
				$mapping[$version]['migrated'] = $migrated[$version];
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
 * @access public
 */
	public function getMigration($name, $class, $type, $options = array()) {
		if (!class_exists($class) && (!$this->__loadFile($name, $type) || !class_exists($class))) {
			return false;
		}

		$defaults = array(
			'connection' => $this->connection
		);
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
 * @access public
 */
	public function run($options) {
		$targetVersion = $latestVersion = $this->getVersion($options['type']);
		$mapping = $this->getMapping($options['type']);

		// Check direction and targetVersion
		if (isset($options['version'])) {
			$targetVersion = $options['version'];
			$direction = ($targetVersion <= $latestVersion) ? 'down' : 'up';
			if ($direction == 'down') {
				$targetVersion++;
			}
		} else if (!empty($options['direction'])) {
			$direction = $options['direction'];
			if ($direction == 'up') {
				$targetVersion++;
			}
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
				$migration->info = $info;
				$migration->run($direction);

				$this->setVersion($version, $info['type'], ($direction == 'up'));
			}
		}
		return true;
	}

/**
 * Init the migrations schema and keep it up-to-date
 *
 * @return void
 * @access private
 */
	private function __initMigrations() {
		$options = array(
			'class' => 'Migrations.SchemaMigration',
			'ds' => $this->connection
		);

		$db =& ConnectionManager::getDataSource($this->connection);
		if (!in_array($db->fullTableName('schema_migrations', false), $db->listSources())) {
			$map = $this->__loadFile('map', 'migrations');

			list($name, $class) = each($map[1]);
			$migration = $this->getMigration($name, $class, 'migrations');
			$migration->run('up');

			$this->Version =& ClassRegistry::init($options);
			$this->setVersion(1, 'migrations');
		} else {
			$this->Version =& ClassRegistry::init($options);
		}

		$mapping = $this->getMapping('migrations');
		if (count($mapping) > 1) {
			end($mapping);
			$this->run(array('version' => key($mapping)));
		}
	}

/**
 * Load a file based on name and type
 *
 * @param string $name File name to be loaded
 * @param string $type Can be 'app' or a plugin name
 * @return mixed False in case of no file found, array with mapping
 * @access private
 */
	private function __loadFile($name, $type) {
		$path = CONFIGS . 'migrations' . DS;
		if ($type != 'app') {
			$path = App::pluginPath($type) . 'config' . DS . 'migrations' . DS;
		}
		if (file_exists($path . $name . '.php')) {
			include $path . $name . '.php';
			if ($name == 'map') {
				return $map;
			}
			return true;
		}
		return false;
	}
}
?>