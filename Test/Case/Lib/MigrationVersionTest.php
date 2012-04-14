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
			'connection' => 'test',
			'autoinit' => false
		));

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
		$this->assertFalse(in_array($db->fullTableName('schema_migrations', false, false), $db->listSources()));

		$this->Version = new MigrationVersion(array('connection' => 'test'));
		$this->assertTrue(in_array($db->fullTableName('schema_migrations', false, false), $db->listSources()));
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
				'type' => 'TestMigrationPlugin',
				'migrated' => null
			),
			2 => array(
				'version' => 2,
				'name' => '002_another_migration_plugin_test_migration',
				'class' => 'AnotherMigrationPluginTestMigration',
				'type' => 'TestMigrationPlugin',
				'migrated' => null
			)
		);
		$this->assertEqual($result, $expected);

		$result = $this->Version->getMapping('migrations');
		$expected = array(
			1 => array(
				'version' => 1,
				'name' => '001_init_migrations',
				'class' => 'InitMigrations',
				'type' => 'Migrations',
				'migrated' => '2009-11-10 00:55:34'
			),
			2 => array(
				'version' => 2,
				'name' => '002_convert_version_to_class_names',
				'class' => 'ConvertVersionToClassNames',
				'type' => 'Migrations',
				'migrated' => '2011-11-18 13:53:32'
			)
		);
		$this->assertEqual($result, $expected);
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
		$this->Version = $this->getMock('MigrationVersion', array('getMapping'), array(array('connection' => 'test')));

		// Checking current
		$this->Version->expects($this->at(0))->method('getMapping')->will($this->returnValue($this->__mapping()));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 0;
		$this->assertEqual($result, $expected);

		// Setting as 1
		$this->Version->expects($this->at(0))->method('getMapping')->will($this->returnValue($this->__mapping()));
		$this->Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 1)));
		$setResult = $this->Version->setVersion(1, 'inexistent_plugin');
		$this->assertTrue(!empty($setResult));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 1;
		$this->assertEqual($result, $expected);

		// Setting as 2
		$this->Version->expects($this->at(0))->method('getMapping')->will($this->returnValue($this->__mapping(1, 1)));
		$this->Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 2)));
		$setResult = $this->Version->setVersion(2, 'inexistent_plugin');
		$this->assertTrue(!empty($setResult));
		$result = $this->Version->getVersion('inexistent_plugin');
		$expected = 2;
		$this->assertEqual($result, $expected);

		// Setting as 1
		$this->Version->expects($this->at(0))->method('getMapping')->will($this->returnValue($this->__mapping(1, 2)));
		$this->Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 1)));
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
		$Version = $this->getMock('MigrationVersion', array('getMapping', 'getMigration', 'getVersion', 'setVersion'), array($options), 'TestMigrationVersionMockMigrationVersion', false); 		
		
		$Version->expects($this->any())
			->method('getMigration')
			->will($this->returnValue(new CakeMigration($options))); 
			
		$Version->Version = ClassRegistry::init(array(
			'class' => 'schema_migrations', 'ds' => 'test'));

		// direction => up
		$Version->expects($this->at(0))->method('getVersion')->will($this->returnValue(0));
		$Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping()));
		$Version->expects($this->at(3))->method('setVersion')->with(1, 'mocks', true);

		$this->assertTrue($Version->run(array('direction' => 'up', 'type' => 'mocks')));

		// direction => down
		$Version->expects($this->at(0))->method('getVersion')->will($this->returnValue(1));
		$Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 1)));
		$Version->expects($this->at(3))->method('setVersion')->with(1, 'mocks', false);

		$this->assertTrue($Version->run(array('direction' => 'down', 'type' => 'mocks')));

		// direction => up
		$Version->expects($this->at(0))->method('getVersion')->will($this->returnValue(3));
		$Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 3)));
		$Version->expects($this->at(3))->method('setVersion')->with(4, 'mocks', true);

		$this->assertTrue($Version->run(array('direction' => 'up', 'type' => 'mocks')));
		return;
		// direction => down
		$Version->expects($this->at(0))->method('getVersion')->will($this->returnValue(4));
		$Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 4)));
		$Version->expects($this->at(3))->method('setVersion')->with(4, 'mocks', false);

		$this->assertTrue($Version->run(array('direction' => 'down', 'type' => 'mocks')));

		// version => 7
		$Version->expects($this->at(0))->method('getVersion')->will($this->returnValue(3));
		$Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 3)));
		$Version->expects($this->at(3))->method('setVersion')->with(4, 'mocks', true);
		$Version->expects($this->at(5))->method('setVersion')->with(5, 'mocks', true);
		$Version->expects($this->at(7))->method('setVersion')->with(6, 'mocks', true);
		$Version->expects($this->at(9))->method('setVersion')->with(7, 'mocks', true);

		$this->assertTrue($Version->run(array('version' => 7, 'type' => 'mocks')));

		// version => 3
		$Version->expects($this->at(0))->method('getVersion')->will($this->returnValue(7));
		$Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 7)));
		$Version->expects($this->at(3))->method('setVersion')->with(7, 'mocks', false);
		$Version->expects($this->at(5))->method('setVersion')->with(6, 'mocks', false);
		$Version->expects($this->at(7))->method('setVersion')->with(5, 'mocks', false);
		$Version->expects($this->at(9))->method('setVersion')->with(4, 'mocks', false);

		$this->assertTrue($Version->run(array('version' => 3, 'type' => 'mocks')));

		// version => 10 (top version)
		$Version->expects($this->at(0))->method('getVersion')->will($this->returnValue(3));
		$Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 3)));
		$Version->expects($this->at(3))->method('setVersion')->with(4, 'mocks', true);
		$Version->expects($this->at(5))->method('setVersion')->with(5, 'mocks', true);
		$Version->expects($this->at(7))->method('setVersion')->with(6, 'mocks', true);
		$Version->expects($this->at(9))->method('setVersion')->with(7, 'mocks', true);
		$Version->expects($this->at(11))->method('setVersion')->with(8, 'mocks', true);
		$Version->expects($this->at(13))->method('setVersion')->with(9, 'mocks', true);
		$Version->expects($this->at(15))->method('setVersion')->with(10, 'mocks', true);

		$this->assertTrue($Version->run(array('version' => 10, 'type' => 'mocks')));

		// version => 0 (run down all migrations)
		$Version->expects($this->at(0))->method('getVersion')->will($this->returnValue(10));
		$Version->expects($this->at(1))->method('getMapping')->will($this->returnValue($this->__mapping(1, 10)));
		$Version->expects($this->at(3))->method('setVersion')->with(10, 'mocks', false);
		$Version->expects($this->at(5))->method('setVersion')->with(9, 'mocks', false);
		$Version->expects($this->at(7))->method('setVersion')->with(8, 'mocks', false);
		$Version->expects($this->at(9))->method('setVersion')->with(7, 'mocks', false);
		$Version->expects($this->at(11))->method('setVersion')->with(6, 'mocks', false);
		$Version->expects($this->at(13))->method('setVersion')->with(5, 'mocks', false);
		$Version->expects($this->at(15))->method('setVersion')->with(4, 'mocks', false);
		$Version->expects($this->at(17))->method('setVersion')->with(3, 'mocks', false);
		$Version->expects($this->at(19))->method('setVersion')->with(2, 'mocks', false);
		$Version->expects($this->at(21))->method('setVersion')->with(1, 'mocks', false);

		$this->assertTrue($Version->run(array('version' => 0, 'type' => 'mocks')));
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
}