<?php
class ConvertVersionToClassNames extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Convert version to classNames';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'alter_field' => array(
				'schema_migrations' => array(
					'version' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 33, 'name' => 'class')
				)
			)
		),
		'down' => array(
			'alter_field' => array(
				'schema_migrations' => array(
					'class' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'name' => 'version')
				)
			)
		)
	);

/**
 * Records to be migrated
 *
 * @var array
 * @access public
 */
	public $records = array();

/**
 * Mappings to the records
 *
 * @var array
 * @access public
 */
	public $mappings = array();

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 * @throws InternalErrorException
 */
	public function before($direction) {
		if ($direction == 'down') {
			throw new InternalErrorException(__d('migrations', 'Sorry, I can\'t downgrade. Why would you want that anyways?'));
		}

		$this->records = $this->Version->Version->find('all');

		$this->needsUpgrade();
		$this->checkPlugins();
		$this->checkRecords();

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
		$this->upgradeRecords();

		return true;
	}

/**
 * Check if it needs upgrade or not
 *
 * @return void
 */
	public function needsUpgrade() {
		$schema = $this->Version->Version->schema();

		// Needs upgrade
		if (isset($schema['version'])) {
			return;
		}

		// Do not need, 001 already set it as string
		// Unset actions, records and mappings, so it wont try again
		$this->migration = array(
			'up' => array(),
			'down' => array()
		);
		$this->records = array();
		$this->mappings = array();
	}

/**
 * Check if every plugin is loaded/reachable, we need access to them
 *
 * @throws MissingPluginException
 * @return void 
 */
	public function checkPlugins() {
		$types = Set::extract('/' . $this->Version->Version->alias . '/type', $this->records);
		$types = $plugins = array_unique($types);

		// Remove app from it
		$index = array_search('app', $plugins);
		if ($index !== false) {
			unset($plugins[$index]);
		}

		// Try to load them
		CakePlugin::load($plugins);
	}

/**
 * Check if the version is present in the mappings
 *
 * @throws RuntimeException MigrationVersionException
 * @return void
 */
	public function checkRecords() {
		foreach ($this->records as $record) {
			$type = $record[$this->Version->Version->alias]['type'];
			$version = $record[$this->Version->Version->alias]['version'];

			if (!isset($this->mappings[$type])) {
				$this->mappings[$type] = $this->Version->getMapping($type);
			}

			// Existing mapping
			if (empty($this->mappings[$type][$version])) {
				throw new RuntimeException(sprintf(
					'Not able to match version `%d` in `%s`. Make sure every migration applied to the database is present on the filesystem.',
					$version, $type
				));
			}

			// Migration file and class present? If not, will throw an exception
			$info = $this->mappings[$type][$version];
			$migration = $this->Version->getMigration($info['name'], $info['class'], $type);
			unset($migration);
		}
	}

/**
 * Upgrade records, setting version as class name
 *
 * @return void
 */
	public function upgradeRecords() {
		foreach ($this->records as $record) {
			$type = $record[$this->Version->Version->alias]['type'];
			$version = $record[$this->Version->Version->alias]['version'];

			$mapping = $this->mappings[$type];
			$migration = $mapping[$version];

			$this->Version->Version->id = $record[$this->Version->Version->alias]['id'];
			$this->Version->Version->saveField('class', $migration['class']);
		}
	}

}