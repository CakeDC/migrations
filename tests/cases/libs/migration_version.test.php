<?php
App::import('Lib', 'Migrations.MigrationVersion');

Mock::generatePartial(
	'MigrationVersion', 'TestMigrationVersionMockMigrationVersion',
	array('getMapping', 'getMigration')
);

class MigrationVersionTest extends CakeTestCase {

/**
 * Fixtures property
 *
 * @var array
 */
	public $fixtures = array('plugin.migrations.schema_migrations');

/**
 * MigrationVersion instance
 *
 * @var MigrationVersion
 */
	public $Version;

/**
 * start test
 *
 * @return void
 **/
	function startTest() {
		$this->Version =& new MigrationVersion(array(
			'connection' => 'test_suite'
		));

		$plugins = $this->plugins = App::path('plugins');
		$plugins[] = dirname(dirname(dirname(__FILE__))) . DS . 'test_app' . DS . 'plugins' . DS;
		App::build(array('plugins' => $plugins), true);
	}

/**
 * endTest method
 *
 * @return void
 **/
	function endTest() {
		App::build(array('plugins' => $this->plugins), true);
		unset($this->Version, $this->plugins);
	}

/**
 * Test __construct method with no existing migrations table
 *
 * @return void
 */
	function testInitialTableCreation() {
		$db =& ConnectionManager::getDataSource('test_suite');
		$Schema =& new CakeSchema(array('connection' => 'test_suite'));
		$Schema->tables = array('schema_migrations' => array());
		$db->execute($db->dropSchema($Schema));
		$this->assertFalse(in_array($db->fullTableName('schema_migrations', false), $db->listSources()));

		$this->Version =& new MigrationVersion(array(
			'connection' => 'test_suite'
		));
		$this->assertTrue(in_array($db->fullTableName('schema_migrations', false), $db->listSources()));
	}

/**
 * testGetMapping method
 *
 * @return void
 */
	function testGetMapping() {
		try {
			$this->Version->getMapping('inexistent_plugin');
			$this->fail('No exception triggered');
		} catch (MigrationVersionException $e) {
			$this->assertEqual('File `map.php` not found in the InexistentPlugin Plugin.', $e->getMessage());
		}

		$result = $this->Version->getMapping('test_migration_plugin');
		$expected = array(
			1 => array(
				'version' => 1,
				'name' => '001_schema_dump',
				'class' => 'M4af6d40056b04408808500cb58157726',
				'type' => 'test_migration_plugin',
				'migrated' => null
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Version->getMapping('migrations');
		$expected = array(
			1 => array(
				'version' => 1,
				'name' => '001_init_migrations',
				'class' => 'M4af6e0f0a1284147a0b100ca58157726',
				'type' => 'migrations',
				'migrated' => '2009-11-10 00:55:34'
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testGetMigration method
 *
 * @return void
 */
	function testGetMigration() {
		try {
			$this->Version->getMigration('inexistent_migration', 'InexistentMigration', 'test_migration_plugin');
			$this->fail('No exception triggered');
		} catch (MigrationVersionException $e) {
			$this->assertEqual('File `inexistent_migration.php` not found in the TestMigrationPlugin Plugin.', $e->getMessage());
		}

		try {
			$this->Version->getMigration('blank_file', 'BlankFile', 'test_migration_plugin');
			$this->fail('No exception triggered');
		} catch (MigrationVersionException $e) {
			$this->assertEqual('Class `BlankFile` not found on file `blank_file.php` for TestMigrationPlugin Plugin.', $e->getMessage());
		}

		$result = $this->Version->getMigration('001_schema_dump', 'M4af6d40056b04408808500cb58157726', 'test_migration_plugin');
		$this->assertIsA($result, 'M4af6d40056b04408808500cb58157726');
		$this->assertEqual($result->description, 'Version 001 (schema dump) of TestMigrationPlugin');

		// Calling twice to check if it will not try to redeclare the class
		$result = $this->Version->getMigration('001_schema_dump', 'M4af6d40056b04408808500cb58157726', 'test_migration_plugin');
		$this->assertIsA($result, 'M4af6d40056b04408808500cb58157726');
		$this->assertEqual($result->description, 'Version 001 (schema dump) of TestMigrationPlugin');
	}

/**
 * testSetGetVersion method
 *
 * @return void
 */
	function testSetGetVersion() {
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 0;
		$this->assertEqual($result, $expected);

		$this->assertTrue($this->Version->setVersion(1, 'inexistent_plugin'));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 1;
		$this->assertEqual($result, $expected);

		$this->assertTrue($this->Version->setVersion(2, 'inexistent_plugin'));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 2;
		$this->assertEqual($result, $expected);

		$this->assertTrue($this->Version->setVersion(2, 'inexistent_plugin', false));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 1;
		$this->assertEqual($result, $expected);
	}

/**
 * testRun method
 *
 * @return void
 */
	function testRun() {
		$back = $this->Version;
		$options = array('connection' => 'test_suite');

		$Version =& new TestMigrationVersionMockMigrationVersion($options);
		$this->Version = $Version;
		$this->Version->setReturnValue('getMigration', new CakeMigration($options));
		$this->Version->Version =& ClassRegistry::init(array(
			'class' => 'schema_migrations', 'ds' => 'test_suite'));

		// Variable used on setReturValueAt method
		$mappingCount = 0;

		// direction => up
		$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping());

		$this->assertEqual($Version->getVersion('mocks'), 0);
		$this->assertTrue($Version->run(array('direction' => 'up', 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), array(1));
		$this->assertEqual($Version->getVersion('mocks'), 1);

		// direction => down
		$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping(1, 1));

		$this->assertEqual($Version->getVersion('mocks'), 1);
		$this->assertTrue($Version->run(array('direction' => 'down', 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), array());
		$this->assertEqual($Version->getVersion('mocks'), 0);

		// Set 1, 2, 3 versions applied
		$this->Version->setVersion(1, 'mocks');
		$this->Version->setVersion(2, 'mocks');
		$this->Version->setVersion(3, 'mocks');

		// direction => up
		$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping(1, 3));

		$this->assertEqual($Version->getVersion('mocks'), 3);
		$this->assertTrue($Version->run(array('direction' => 'up', 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 4));
		$this->assertEqual($Version->getVersion('mocks'), 4);

		// direction => down
		$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping(1, 4));

		$this->assertEqual($Version->getVersion('mocks'), 4);
		$this->assertTrue($Version->run(array('direction' => 'down', 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 3));
		$this->assertEqual($Version->getVersion('mocks'), 3);

		// version => 7
		$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping(1, 3));

		$this->assertEqual($Version->getVersion('mocks'), 3);
		$this->assertTrue($Version->run(array('version' => 7, 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 7));
		$this->assertEqual($Version->getVersion('mocks'), 7);

		// version => 3
		$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping(1, 7));

		$this->assertEqual($Version->getVersion('mocks'), 7);
		$this->assertTrue($Version->run(array('version' => 3, 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 3));
		$this->assertEqual($Version->getVersion('mocks'), 3);

		// version => 10 (top version)
		$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping(1, 3));

		$this->assertEqual($Version->getVersion('mocks'), 3);
		$this->assertTrue($Version->run(array('version' => 10, 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 10));
		$this->assertEqual($Version->getVersion('mocks'), 10);

		// version => 0 (run down all migrations)
		$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping(1, 10));

		$this->assertEqual($Version->getVersion('mocks'), 10);
		$this->assertTrue($Version->run(array('version' => 0, 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), array());
		$this->assertEqual($Version->getVersion('mocks'), 0);

		// Changing values back
		$this->Version = $back;
		unset($back);
	}

/**
 * __mapping method
 *
 * @param int $start
 * @param int $end
 * @return array
 */
	function __mapping($start = 0, $end = 0) {
		$mapping = array();
		for ($i = 1; $i <= 10; $i++) {
			$mapping[$i] = array(
				'version' => $i, 'name' => '001_schema_dump',
				'class' => 'M4af9d151e1484b74ad9d007058157726',
				'type' => 'mocks', 'migrated' => null
			);
			if ($i >= $start && $i <= $end) {
				$mapping[$i]['migrated'] = date('r');
			}
		}
		return $mapping;
	}

/**
 * __migrated method
 *
 * @return array
 */
	function __migrated() {
		$alias = $this->Version->Version->alias;
		$migrated = $this->Version->Version->find('all', array(
			'fields' => array('version'),
			'conditions' => array($alias . '.type' => 'mocks')
		));
		$migrated = Set::extract('/' . $alias . '/version', $migrated);

		sort($migrated);
		return $migrated;
	}
}