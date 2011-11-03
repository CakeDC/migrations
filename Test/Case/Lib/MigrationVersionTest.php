<?php
App::uses('CakeMigration', 'Migrations.Lib');
App::uses('MigrationVersion', 'Migrations.Lib');

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
	public function setUp() {
		$this->Version = new MigrationVersion(array(
			'connection' => 'test'));

		App::build(array('plugins' => CakePlugin::path('Migrations') . 'Test' .  DS . 'test_app' . DS . 'Plugin' . DS));
	}

/**
 * tearDown method
 *
 * @return void
 **/
	public function tearDown() {
		//App::build(array('plugins' => $this->plugins), true);
		unset($this->Version, $this->plugins);
	}

/**
 * Test __construct method with no existing migrations table
 *
 * @return void
 */
	public function testInitialTableCreation() {
		$db = ConnectionManager::getDataSource('test');
		$db->cacheSources = false;
		$Schema = new CakeSchema(array('connection' => 'test'));
		$Schema->tables = array('schema_migrations' => array());

		$db->execute($db->dropSchema($Schema));
		$this->assertFalse(in_array($db->fullTableName('schema_migrations', false), $db->listSources()));

		$this->Version = new MigrationVersion(array('connection' => 'test'));
		$this->assertTrue(in_array($db->fullTableName('schema_migrations', false), $db->listSources()));
	}

/**
 * testGetMapping method
 *
 * @return void
 */
	public function testGetMapping() {
		CakePlugin::load('TestMigrationPlugin');
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
 * testGetMapping method on a plugin having an empty map.php file, or not
 * having this file at all
 *
 * @return void
 */
	public function testGetMappingEmptyMap() {
		CakePlugin::load('TestMigrationPlugin2');
		CakePlugin::load('TestMigrationPlugin3');

		try {
			$this->Version->getMapping('test_migration_plugin2');
			$this->fail('No exception triggered');
		} catch (MigrationVersionException $e) {
			$this->assertEqual('File `map.php` not found in the TestMigrationPlugin2 Plugin.', $e->getMessage());
		}

		$result = $this->Version->getMapping('test_migration_plugin3');
		$this->assertIdentical($result, false);
	}

/**
 * testGetMigration method
 *
 * @return void
 */
	public function testGetMigration() {
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
		$this->assertInstanceOf('M4af6d40056b04408808500cb58157726', $result);
		$this->assertEqual($result->description, 'Version 001 (schema dump) of TestMigrationPlugin');

		// Calling twice to check if it will not try to redeclare the class
		$result = $this->Version->getMigration('001_schema_dump', 'M4af6d40056b04408808500cb58157726', 'test_migration_plugin');
		$this->assertInstanceOf('M4af6d40056b04408808500cb58157726', $result);
		$this->assertEqual($result->description, 'Version 001 (schema dump) of TestMigrationPlugin');
	}

/**
 * testSetGetVersion method
 *
 * @return void
 */
	public function testSetGetVersion() {
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 0;
		$this->assertEqual($result, $expected);

		$setResult = $this->Version->setVersion(1, 'inexistent_plugin');
		$this->assertTrue(!empty($setResult));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 1;
		$this->assertEqual($result, $expected);

		$setResult = $this->Version->setVersion(2, 'inexistent_plugin');
		$this->assertTrue(!empty($setResult));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 2;
		$this->assertEqual($result, $expected);

		$setResult = $this->Version->setVersion(2, 'inexistent_plugin', false);
		$this->assertTrue(!empty($setResult));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 1;
		$this->assertEqual($result, $expected);
	}

/**
 * testRun method
 *
 * @return void
 */
	public function testRun() {
		$back = $this->Version;
		$options = array('connection' => 'test');
		$Version = $this->getMock('MigrationVersion', array('getMapping', 'getMigration'), array($options), 'TestMigrationVersionMockMigrationVersion', false); 		
		
		$this->Version = $Version;
		
		$this->Version->expects($this->any())
			->method('getMigration')
			->will($this->returnValue(new CakeMigration($options))); 
			
		$this->Version->Version = ClassRegistry::init(array(
			'class' => 'schema_migrations', 'ds' => 'test'));

		// direction => up
		$this->Version->expects($this->at(0))
			->method('getMapping')
			->will($this->returnValue($this->__mapping()));

		$this->assertEqual($Version->getVersion('mocks'), 0);
		$this->assertTrue($Version->run(array('direction' => 'up', 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), array(1));
		$this->assertEqual($Version->getVersion('mocks'), 1);

		// direction => down
		$this->Version->expects($this->at(0))
			->method('getMapping')
			->will($this->returnValue($this->__mapping(1, 1)));

		$this->assertTrue($Version->run(array('direction' => 'down', 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), array());
		$this->assertEqual($Version->getVersion('mocks'), 0);
		// Set 1, 2, 3 versions applied
		$this->Version->setVersion(1, 'mocks');
		$this->Version->setVersion(2, 'mocks');
		$this->Version->setVersion(3, 'mocks');

		// direction => up
		$this->Version->expects($this->at(0))
			->method('getMapping')
			->will($this->returnValue($this->__mapping(1, 3)));

		$this->assertEqual($Version->getVersion('mocks'), 3);
		$this->assertTrue($Version->run(array('direction' => 'up', 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 4));
		$this->assertEqual($Version->getVersion('mocks'), 4);

		// direction => down
		$this->Version->expects($this->at(0))
			->method('getMapping')
			->will($this->returnValue($this->__mapping(1, 4)));

		$this->assertTrue($Version->run(array('direction' => 'down', 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 3));
		$this->assertEqual($Version->getVersion('mocks'), 3);

		// version => 7
		$this->Version->expects($this->at(0))
			->method('getMapping')
			->will($this->returnValue($this->__mapping(1, 3)));

		$this->assertTrue($Version->run(array('version' => 7, 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 7));
		$this->assertEqual($Version->getVersion('mocks'), 7);

		// version => 3
		$this->Version->expects($this->at(0))
			->method('getMapping')
			->will($this->returnValue($this->__mapping(1, 7)));

		$this->assertTrue($Version->run(array('version' => 3, 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 3));
		$this->assertEqual($Version->getVersion('mocks'), 3);

		// version => 10 (top version)
		$this->Version->expects($this->at(0))
			->method('getMapping')
			->will($this->returnValue($this->__mapping(1, 3)));

		$this->assertTrue($Version->run(array('version' => 10, 'type' => 'mocks')));
		$this->assertEqual($this->__migrated(), range(1, 10));
		$this->assertEqual($Version->getVersion('mocks'), 10);

		// version => 0 (run down all migrations)
		//$Version->setReturnValueAt($mappingCount++, 'getMapping', $this->__mapping(1, 10));
		$this->Version->expects($this->at(0))
			->method('getMapping')
			->will($this->returnValue($this->__mapping(1, 10))); 

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
	private function __mapping($start = 0, $end = 0) {
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