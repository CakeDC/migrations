<?php
App::uses('ShellDispatcher', 'Console');
App::uses('MigrationShell', 'Migrations.Console/Command');

/**
 * TestMigrationShell
 *
 * @package       migrations
 * @subpackage    migrations.tests.cases.shells
 */
class TestMigrationShell extends MigrationShell {

/**
 * output property
 *
 * @var string
 */
	public $output = '';

/**
 * out method
 *
 * @param $string
 * @return void
 */
	function out($message = null, $newlines = 1, $level = 1) {
		$this->output .= $message . "\n";
	}

/**
 * fromComparison method
 *
 * @param $migration
 * @param $comparison
 * @param $oldTables
 * @param $currentTables
 * @return void
 */
	public function fromComparison($migration, $comparison, $oldTables, $currentTables) {
		return $this->_fromComparison($migration, $comparison, $oldTables, $currentTables);
	}

/**
 * writeMigration method
 *
 * @param $name
 * @param $class
 * @param $migration
 * @return void
 */
	public function writeMigration($name, $class, $migration) {
		return $this->_writeMigration($name, $class, $migration);
	}

}

/**
 * MigrationShellTest
 *
 * @package       migrations
 * @subpackage    migrations.tests.cases.shells
 */
class MigrationShellTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('plugin.migrations.schema_migrations', 'core.article', 'core.post', 'core.user');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Shell = $this->getMock(
			'TestMigrationShell',
			array('in', 'hr', 'createFile', 'error', 'err', '_stop', '_showInfo', 'dispatchShell'),
			array($out, $out, $in));

		$this->Shell->Version = $this->getMock(
			'MigrationVersion',
			array('getMapping', 'getVersion', 'run'),
			array(array('connection' => 'test')));

		$this->Shell->type = 'TestMigrationPlugin';
		$this->Shell->path = TMP . 'tests' . DS;
		$this->Shell->connection = 'test';

		$plugins = $this->plugins = App::path('plugins');
		$plugins[] = dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'test_app' . DS . 'Plugin' . DS;

		App::build(array('Plugin' => $plugins), true);
		App::objects('plugins', null, false);
		CakePlugin::load('TestMigrationPlugin');
		CakePlugin::load('TestMigrationPlugin2');
		CakePlugin::load('TestMigrationPlugin3');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakePlugin::unload('TestMigrationPlugin');
		CakePlugin::unload('TestMigrationPlugin2');
		CakePlugin::unload('TestMigrationPlugin3');
		App::build(array('Plugin' => $this->plugins), true);
		App::objects('plugins', null, false);
		unset($this->Dispatcher, $this->Shell, $this->plugins);
		foreach (glob(TMP . 'tests' . DS . '*.php') as $f) {
			unlink($f);
		}
	}

/**
 * tables property
 *
 * @var array
 */
	public $tables = array(
		'users' => array(
			'id' => array('type' => 'integer', 'key' => 'primary'),
			'user' => array('type' => 'string', 'null' => false),
			'password' => array('type' => 'string', 'null' => false),
			'created' => 'datetime',
			'updated' => 'datetime'
		),
		'posts' => array(
			'id' => array('type' => 'integer', 'key' => 'primary'),
			'author_id' => array('type' => 'integer', 'null' => false),
			'title' => array('type' => 'string', 'null' => false),
			'body' => 'text',
			'published' => array('type' => 'string', 'length' => 1, 'default' => 'N'),
			'created' => 'datetime',
			'updated' => 'datetime'
		)
	);

/**
 * testStartup method
 *
 * @return void
 */
	public function testStartup() {
		$this->Shell->connection = 'default';
		$this->assertEqual($this->Shell->type, 'TestMigrationPlugin');
		$this->Shell->params = array(
			'connection' => 'test',
			'plugin' => 'Migrations',
			'no-auto-init' => false,
			'dry' => false,
			'precheck' => 'Migrations.PrecheckException'
		);
		$this->Shell->startup();
		$this->assertEqual($this->Shell->connection, 'test');
		$this->assertEqual($this->Shell->type, 'Migrations');
	}

/**
 * testRun method
 *
 * @return void
 */
	public function testRun() {
		$mapping = array();
		for ($i = 1; $i <= 10; $i++) {
			$mapping[$i] = array(
				'version' => $i, 'name' => '001_schema_dump',
				'class' => 'M4af9d151e1484b74ad9d007058157726',
				'type' => $this->Shell->type, 'migrated' => null
			);
		}
		$this->Shell->expects($this->any())->method('_stop')->will($this->returnValue(false));

		// Variable used on expectArgumentsAt method
		$runCount = $versionCount = $inCount = 0;

		// cake migration run - no mapping
		$this->Shell->Version->expects($this->at(0))->method('getMapping')->will($this->returnValue(false));
		$this->Shell->args = array();
		$this->assertFalse($this->Shell->run());

		// cake migration run up
		$this->Shell->Version->expects($this->any())->method('getMapping')->will($this->returnValue($mapping));
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(0));
		$this->Shell->Version->expects($this->at(2))->method('run')->with($this->equalTo(array(
			'type' => 'TestMigrationPlugin',
			'callback' => $this->Shell,
			'direction' => 'up',
			'version' => 1,
			'dry' => false,
			'precheck' => null)));
		$this->Shell->args = array('up');
		$this->assertTrue($this->Shell->run());

		// cake migration run up - on last version == stop
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(10));
		$this->Shell->args = array('up');
		$this->assertFalse($this->Shell->run());

		// cake migration run down - on version 0 == stop
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(0));
		$this->Shell->args = array('down');
		$this->assertFalse($this->Shell->run());

		// cake migration run down
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(1));
		$this->Shell->Version->expects($this->at(2))->method('run')->with($this->equalTo(array(
			'type' => 'TestMigrationPlugin',
			'callback' => $this->Shell,
			'direction' => 'down',
			'version' => 1,
			'dry' => false,
			'precheck' => null)));
		$this->Shell->args = array('down');
		$this->assertTrue($this->Shell->run());

		// cake migration run all
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(1));
		$this->Shell->Version->expects($this->at(2))->method('run')->with($this->equalTo(array(
			'type' => 'TestMigrationPlugin',
			'callback' => $this->Shell,
			'version' => 10,
			'direction' => 'up',
			'dry' => false,
			'precheck' => null)));
		$this->Shell->args = array('all');
		$this->assertTrue($this->Shell->run());

		// cake migration run reset
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(9));
		$this->Shell->Version->expects($this->at(2))->method('run')->with($this->equalTo(array(
			'type' => 'TestMigrationPlugin',
			'callback' => $this->Shell,
			'version' => 0,
			'direction' => 'down',
			'reset' => true,
			'dry' => false,
			'precheck' => null)));
		$this->Shell->args = array('reset');
		$this->assertTrue($this->Shell->run());

		// cake migration run - answers 0, 11, 1
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(0));
		$this->Shell->Version->expects($this->at(2))->method('run')->with($this->equalTo(array(
			'type' => 'TestMigrationPlugin',
			'callback' => $this->Shell,
			'version' => 1,
			'direction' => 'up',
			'dry' => false)));
		$this->Shell->expects($this->at(2))->method('in')->will($this->returnValue(0));
		$this->Shell->expects($this->at(4))->method('in')->will($this->returnValue(11));
		$this->Shell->expects($this->at(6))->method('in')->will($this->returnValue(1));
		$this->Shell->args = array();
		$this->assertTrue($this->Shell->run());

		// cake migration run - answers 10
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(9));
		$this->Shell->Version->expects($this->at(2))->method('run')->with($this->equalTo(array(
			'type' => 'TestMigrationPlugin',
			'callback' => $this->Shell,
			'version' => 10,
			'direction' => 'up',
			'dry' => false)));
		$this->Shell->expects($this->at(2))->method('in')->will($this->returnValue(10));
		$this->Shell->args = array();
		$this->assertTrue($this->Shell->run());

		// cake migration run 1
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(0));
		$this->Shell->Version->expects($this->at(2))->method('run')->with($this->equalTo(array(
			'type' => 'TestMigrationPlugin',
			'callback' => $this->Shell,
			'version' => 1,
			'dry' => false)));
		$this->Shell->args = array('1');
		$this->assertTrue($this->Shell->run());

		// cake migration run 11
		$this->Shell->Version->expects($this->at(1))->method('getVersion')->will($this->returnValue(0));
		$this->Shell->args = array('11');
		$this->assertFalse($this->Shell->run());
	}

/**
 * testRunWithFailuresOnce method
 *
 * @return void
 */
	public function testRunWithFailuresOnce() {
		$this->Shell->expects($this->any())->method('_stop')->will($this->returnValue(false));

		$mapping = array(1 => array(
			'version' => 1, 'name' => '001_schema_dump',
			'class' => 'M4af9d151e1484b74ad9d007058157726',
			'type' => $this->Shell->type, 'migrated' => null
		));

		$migration = new CakeMigration();
		$migration->info = $mapping[1];
		$exception = new MigrationException($migration, 'Exception message');

		$this->Shell->Version->expects($this->any())->method('getMapping')->will($this->returnValue($mapping));
		$this->Shell->Version->expects($this->any())->method('getVersion')->will($this->returnValue(0));
		$this->Shell->Version->expects($this->at(2))->method('run')->will($this->throwException($exception));
		$this->Shell->expects($this->at(1))->method('in')->will($this->returnValue('y'));
		$this->Shell->args = array('up');
		$this->assertTrue($this->Shell->run());

		$result = $this->Shell->output;
		$pattern = <<<TEXT
/Running migrations:
An error occurred when processing the migration:
  Migration: 001_schema_dump
  Error: Exception message
All migrations have completed./
TEXT;
		$this->assertPattern(str_replace("\r\n", "\n", $pattern), str_replace("\r\n", "\n", $result));
	}

/**
 * testRunWithFailuresNotOnce method
 *
 * @return void
 */
	public function testRunWithFailuresNotOnce() {
		$this->Shell->expects($this->any())->method('_stop')->will($this->returnValue(false));

		$mapping = array(
			1 => array(
				'version' => 1, 'name' => '001_schema_dump',
				'class' => 'M4af9d151e1484b74ad9d007058157726',
				'type' => $this->Shell->type, 'migrated' => null
			),
		);

		$migration = new CakeMigration();
		$migration->info = $mapping[1];
		$exception = new MigrationException($migration, 'Exception message');

		$this->Shell->Version->expects($this->any())->method('getMapping')->will($this->returnValue($mapping));
		$this->Shell->Version->expects($this->any())->method('getVersion')->will($this->returnValue(0));
		$this->Shell->Version->expects($this->at(2))->method('run')->will($this->throwException($exception));
		$this->Shell->Version->expects($this->at(3))->method('run')->will($this->returnValue(true));
		$this->Shell->expects($this->at(1))->method('in')->will($this->returnValue('y'));
		$this->Shell->args = array('all');
		$this->assertTrue($this->Shell->run());
		$result = $this->Shell->output;
		$pattern = <<<TEXT
/Running migrations:
All migrations have completed./
TEXT;
		$this->assertPattern(str_replace("\r\n", "\n", $pattern), str_replace("\n\n", "\n", $result));
	}

/**
 * testFromComparisonTableActions method
 *
 * @return void
 */
	public function testFromComparisonTableActions() {
		$comparison = array(
			'users' => array('add' => $this->tables['users']),
			'posts' => array('add' => $this->tables['posts'])
		);
		$oldTables = array();
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $this->tables);
		$expected = array(
			'up' => array('create_table' => $this->tables),
			'down' => array('drop_table' => array('users', 'posts'))
		);
		$this->assertEqual($result, $expected);

		$comparison = array('posts' => array('add' => $this->tables['posts']));
		$oldTables = array('users' => $this->tables['users']);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $this->tables);
		$expected = array(
			'up' => array(
				'create_table' => array('posts' => $this->tables['posts'])
			),
			'down' => array(
				'drop_table' => array('posts')
			)
		);
		$this->assertEqual($result, $expected);

		$comparison = array();
		$oldTables = array('posts' => $this->tables['posts'], 'users' => $this->tables['users']);
		$currentTables = array('users' => $this->tables['users']);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $currentTables);
		$expected = array(
			'up' => array(
				'drop_table' => array('posts')
			),
			'down' => array(
				'create_table' => array('posts' => $this->tables['posts'])
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testFromComparisonFieldActions method
 *
 * @return void
 */
	public function testFromComparisonFieldActions() {
		// Add field/index
		$oldTables = array('posts' => $this->tables['posts']);
		$newTables = array('posts' => array());

		$comparison = array(
			'posts' => array('add' => array(
				'views' => array('type' => 'integer', 'null' => false)
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $newTables);
		$expected = array(
			'up' => array(
				'create_field' => array(
					'posts' => array('views' => array('type' => 'integer', 'null' => false))
				)
			),
			'down' => array(
				'drop_field' => array(
					'posts' => array('views')
				)
			)
		);
		$this->assertEqual($result, $expected);

		$comparison = array(
			'posts' => array('add' => array(
				'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $newTables);
		$expected = array(
			'up' => array(
				'create_field' => array(
					'posts' => array(
						'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
					)
				)
			),
			'down' => array(
				'drop_field' => array(
					'posts' => array('indexes' => array('VIEW_COUNT'))
				)
			)
		);
		$this->assertEqual($result, $expected);

		$comparison = array(
			'posts' => array('add' => array(
				'views' => array('type' => 'integer', 'null' => false),
				'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $newTables);
		$expected = array(
			'up' => array(
				'create_field' => array(
					'posts' => array(
						'views' => array('type' => 'integer', 'null' => false),
						'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
					)
				)
			),
			'down' => array(
				'drop_field' => array(
					'posts' => array('views', 'indexes' => array('VIEW_COUNT'))
				)
			)
		);
		$this->assertEqual($result, $expected);

		// Drop field/index
		$oldTables['posts']['views'] = array('type' => 'integer', 'null' => false);
		$oldTables['posts']['indexes'] = array('VIEW_COUNT' => array('column' => 'views', 'unique' => false));

		$comparison = array(
			'posts' => array('drop' => array(
				'views' => array('type' => 'integer', 'null' => false)
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $newTables);
		$expected = array(
			'up' => array(
				'drop_field' => array(
					'posts' => array('views')
				)
			),
			'down' => array(
				'create_field' => array(
					'posts' => array('views' => array('type' => 'integer', 'null' => false))
				)
			)
		);
		$this->assertEqual($result, $expected);

		$comparison = array(
			'posts' => array('drop' => array(
				'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $newTables);
		$expected = array(
			'up' => array(
				'drop_field' => array(
					'posts' => array('indexes' => array('VIEW_COUNT'))
				)
			),
			'down' => array(
				'create_field' => array(
					'posts' => array('indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false)))
				)
			)
		);
		$this->assertEqual($result, $expected);

		$comparison = array(
			'posts' => array('drop' => array(
				'views' => array('type' => 'integer', 'null' => false),
				'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $newTables);
		$expected = array(
			'up' => array(
				'drop_field' => array(
					'posts' => array('views', 'indexes' => array('VIEW_COUNT'))
				)
			),
			'down' => array(
				'create_field' => array(
					'posts' => array(
						'views' => array('type' => 'integer', 'null' => false),
						'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		// Change field
		$comparison = array(
			'posts' => array('change' => array(
				'views' => array('type' => 'integer', 'null' => false, 'length' => 2),
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $newTables);
		$expected = array(
			'up' => array(
				'alter_field' => array(
					'posts' => array(
						'views' => array('type' => 'integer', 'null' => false, 'length' => 2)
					)
				)
			),
			'down' => array(
				'alter_field' => array(
					'posts' => array(
						'views' => array('type' => 'integer', 'null' => false)
					)
				)
			)
		);
		$this->assertEqual($result, $expected);

		// Change field with/out length
		$oldTables = array('users' => $this->tables['users']);
		$newTables = array('users' => array());
		$oldTables['users']['last_login'] = array('type' => 'integer', 'null' => false, 'length' => 11);

		$comparison = array(
			'users' => array('change' => array(
				'last_login' => array('type' => 'datetime', 'null' => false),
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables, $newTables);
		$expected = array(
			'up' => array(
				'alter_field' => array(
					'users' => array(
						'last_login' => array('type' => 'datetime', 'null' => false, 'length' => null)
					)
				)
			),
			'down' => array(
				'alter_field' => array(
					'users' => array(
						'last_login' => array('type' => 'integer', 'null' => false, 'length' => 11)
					)
				)
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testWriteMigration method
 *
 * @return void
 */
	public function testWriteMigration() {
		// Remove if exists
		$this->__unlink('12345_migration_test_file.php');

		$users = $this->tables['users'];
		$users['indexes'] = array('UNIQUE_USER' => array('column' => 'user', 'unique' => true));

		$migration = array(
			'up' => array(
				'create_table' => array('users' => $users),
				'create_field' => array(
					'posts' => array(
						'views' => array('type' => 'integer', 'null' => false),
						'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
					)
				)
			),
			'down' => array(
				'drop_table' => array('users'),
				'drop_field' => array(
					'posts' => array('views', 'indexes' => array('VIEW_COUNT'))
				)
			)
		);
		$this->assertFalse(file_exists(TMP . 'tests' . DS . '12345_migration_test_file.php'));
		$this->assertTrue($this->Shell->writeMigration('migration_test_file', 12345, $migration));
		$this->assertTrue(file_exists(TMP . 'tests' . DS . '12345_migration_test_file.php'));

		$result = $this->__getMigrationVariable(TMP . 'tests' . DS . '12345_migration_test_file.php');
		$expected = <<<TEXT
	public \$migration = array(
		'up' => array(
			'create_table' => array(
				'users' => array(
					'id' => array('type' => 'integer', 'key' => 'primary'),
					'user' => array('type' => 'string', 'null' => false),
					'password' => array('type' => 'string', 'null' => false),
					'created' => 'datetime',
					'updated' => 'datetime',
					'indexes' => array(
						'UNIQUE_USER' => array('column' => 'user', 'unique' => true),
					),
				),
			),
			'create_field' => array(
				'posts' => array(
					'views' => array('type' => 'integer', 'null' => false),
					'indexes' => array(
						'VIEW_COUNT' => array('column' => 'views', 'unique' => false),
					),
				),
			),
		),
		'down' => array(
			'drop_table' => array(
				'users'
			),
			'drop_field' => array(
				'posts' => array('views', 'indexes' => array('VIEW_COUNT')),
			),
		),
	);
TEXT;
		$this->assertEqual($result, str_replace("\r\n", "\n", $expected));
		$this->__unlink('12345_migration_test_file.php');
	}

/**
 * testGenerate method
 *
 * @return void
 */
	public function testGenerate() {
		$this->Shell->expects($this->at(0))->method('in')->will($this->returnValue('n'));
		$this->Shell->expects($this->at(1))->method('in')->will($this->returnValue('n'));
		$this->Shell->expects($this->at(2))->method('in')->will($this->returnValue('Initial Schema'));

		$this->Shell->generate();
		$files = glob(TMP . 'tests' . DS . '*initial_schema.php');
		foreach ($files as $f) {
			unlink($f);
		}
		$this->assertNotEmpty(preg_grep('/([0-9])+_initial_schema\.php$/i', $files));
	}

/**
 * testGenerate2 method
 *
 * @return void
 */
	public function testGenerate2() {
		$this->Shell->expects($this->atLeastOnce())->method('err');
		$this->Shell->expects($this->at(0))->method('in')->will($this->returnValue('n'));
		$this->Shell->expects($this->at(1))->method('in')->will($this->returnValue('n'));
		$this->Shell->expects($this->at(2))->method('in')->will($this->returnValue('002 invalid name'));
		$this->Shell->expects($this->at(4))->method('in')->will($this->returnValue('invalid-name'));
		$this->Shell->expects($this->at(6))->method('in')->will($this->returnValue('create some sample_data'));

		$this->Shell->generate();
		$files = glob(TMP . 'tests' . DS . '*create_some_sample_data.php');
		foreach ($files as $f) {
			unlink($f);
		}
		$this->assertNotEmpty(preg_grep('/([0-9])+_create_some_sample_data\.php$/i', $files));
	}

/**
 * testGenerateComparison method
 *
 * @return void
 */
	public function testGenerateComparison() {
		$this->Shell->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Shell->expects($this->at(2))->method('in')->will($this->returnValue('n'));
		$this->Shell->expects($this->at(3))->method('in')->will($this->returnValue('drop slug field'));
		$this->Shell->expects($this->at(4))->method('in')->will($this->returnValue('y'));
		$this->Shell->expects($this->at(5))->method('dispatchShell')->with('schema generate --connection test --force');

		$this->Shell->Version->expects($this->any())->method('getMapping')->will($this->returnCallback(array($this, 'returnMapping')));

		$this->assertEmpty(glob(TMP . 'tests' . DS . '*drop_slug_field.php'));
		$this->Shell->params['force'] = true;
		$this->Shell->generate();
		$files = glob(TMP . 'tests' . DS . '*drop_slug_field.php');
		$this->assertNotEmpty($files);

		$result = $this->__getMigrationVariable(current($files));
		foreach ($files as $f) {
			unlink($f);
		}
		$this->assertNoPattern('/\'schema_migrations\'/', $result);

		$pattern = <<<TEXT
/			'drop_field' => array\(
				'articles' => array\('slug',\),
			\),/
TEXT;
		$this->assertPattern(str_replace("\r\n", "\n", $pattern), $result);

		$pattern = <<<TEXT
/			'create_field' => array\(
				'articles' => array\(
					'slug' => array\('type' => 'string', 'null' => false\),
				\),
			\),/
TEXT;
		$this->assertPattern(str_replace("\r\n", "\n", $pattern), $result);
	}

	public function returnMapping() {
		return array(
			gmdate('U') => array('class' => 'M4af9d15154844819b7a0007058157726')
		);
	}

/**
 * testGenerateDump method
 *
 * @return void
 */
	public function testGenerateDump() {
		$this->Shell->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Shell->expects($this->at(2))->method('in')->will($this->returnValue('n'));
		$this->Shell->expects($this->at(3))->method('in')->will($this->returnValue('schema dump'));

		$mapping = array(
			gmdate('U') => array('class' => 'M4af9d15154844819b7a0007058157726')
		);
		$this->Shell->Version->expects($this->any())->method('getMapping')->will($this->returnCallback(array($this, 'returnMapping')));

		$this->assertEmpty(glob(TMP . 'tests' . DS . '*schema_dump.php'));
		$this->Shell->type = 'TestMigrationPlugin2';
		$this->Shell->params['force'] = true;
		$this->Shell->params['dry'] = false;
		$this->Shell->params['precheck'] = 'Migrations.PrecheckException';
		$this->Shell->generate();
		$files = glob(TMP . 'tests' . DS . '*schema_dump.php');
		$this->assertNotEmpty($files);

		$result = $this->__getMigrationVariable(current($files));
		foreach ($files as $f) {
			unlink($f);
		}
		$expected = file_get_contents(CakePlugin::path('Migrations') . '/Test/Fixture/test_migration.txt');
		$this->assertEquals($expected, $result);
	}

/**
 * testStatus method
 *
 * @return void
 */
	public function testStatus() {
		$this->Shell->Version = new MigrationVersion(array('connection' => 'test'));
		$this->Shell->status();
		$result = $this->Shell->output;
		$pattern = <<<TEXT
/Migrations Plugin

Current version:
  #003 003_increase_class_name_length
Latest version:
  #003 003_increase_class_name_length/
TEXT;
		$this->assertPattern(str_replace("\r\n", "\n", $pattern), $result);

		$this->Shell->output = '';
		$this->Shell->args = array('outdated');
		$this->Shell->status();
		$result = $this->Shell->output;
		$this->assertNoPattern(str_replace("\r\n", "\n", $pattern), $result);

		$this->Shell->Version->setVersion(3, 'migrations', false);
		$this->Shell->output = '';
		$this->Shell->args = array('outdated');
		$this->Shell->status();
		$result = $this->Shell->output;
		$pattern = <<<TEXT
/Migrations Plugin

Current version:
  #002 002_convert_version_to_class_names
Latest version:
  #003 003_increase_class_name_length/
TEXT;
		$this->assertPattern(str_replace("\r\n", "\n", $pattern), $result);
		$this->Shell->Version->setVersion(1, 'migrations');
	}

/**
 * Strip all the content surrounding the $migration variable
 *
 * @param string $file
 * @return string
 */
	private function __getMigrationVariable($file) {
		$result = array();
		$array = explode("\n", str_replace("\r\n", "\n", file_get_contents($file)));
		foreach ($array as $line) {
			if ($line == "\tpublic \$migration = array(") {
				$result[] = $line;
			} else if (!empty($result) && $line == "\t);") {
				$result[] = $line;
				break;
			} else if (!empty($result)) {
				$result[] = $line;
			}
		}
		return implode("\n", $result);
	}

/**
 * Unlink test files from filesystem
 *
 * @param mixed files
 * @return void
 */
	private function __unlink() {
		$files = func_get_args();
		foreach ($files as $file) {
			@unlink(TMP . 'tests' . DS . $file);
		}
	}
}
