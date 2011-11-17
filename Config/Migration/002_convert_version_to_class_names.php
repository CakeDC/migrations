<?php
class M4ec50d1f7a284842b1b770fdcbdd56cb extends CakeMigration {

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
					'version' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 33)
				)
			)
		),
		'down' => array(
			'alter_field' => array(
				'schema_migrations' => array(
					'version' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11)
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
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		if ($direction == 'down') {
			throw new InternalErrorException(__d('migrations', 'Sorry, I can\'t downgrade. Why would you want that anyways?'));
		}

		$this->records = $this->generateModel('Version', 'schema_migrations')->find('all');

		$this->needsUpgrade();
		$this->checkPlugins();

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
		$Model = $this->generateModel('Version', 'schema_migrations');
		$Version = new MigrationVersion(array(
			'connection' => $this->connection,
			'autoinit' => false
		));
		$Version->Version = $Model;

		$mappings = array();
		foreach ($this->records as $record) {
			$type = $record['Version']['type'];
			if (!isset($mappings[$type])) {
				$mappings[$type] = $Version->getMapping($type);
			}
			$mapping = $mappings[$type];
			$migration = $mapping[$record['Version']['version']];

			$Model->id = $record['Version']['id'];
			$Model->saveField('version', $migration['class']);
		}
		die;

		return true;
	}

/**
 * Check if it needs upgrade or not
 *
 * @return void
 */
	public function needsUpgrade() {
		$schema = $this->generateModel('Version', 'schema_migrations')->schema();

		// Needs upgrade
		if ($schema['version']['type'] === 'integer') {
			return;
		}
		
		// Do not need, 001 already set it as string
		// Unset actions and records, so it wont try again
		$this->migration = array(
			'up' => array(),
			'down' => array()
		);
		$this->records = array();
	}

/**
 * Check if every plugin is loaded/reachable, we need access to them
 *
 * @throws MissingPluginException
 * @return void 
 */
	public function checkPlugins() {
		$types = Set::extract('/Version/type', $this->records);
		$types = array_unique($types);

		// Remove app from it
		$index = array_search('app', $types);
		if ($index !== false) {
			unset($types[$index]);
		}

		// Try to load them
		CakePlugin::load($types);
	}
}
?>