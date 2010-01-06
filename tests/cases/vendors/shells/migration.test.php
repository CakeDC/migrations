<?php
App::import('Shell', 'Shell', false);
App::import('Vendor', 'Migrations.MigrationShell', array('file' => 'shells' . DS . 'migration.php'));

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

Mock::generatePartial(
	'ShellDispatcher', 'TestMigrationShellMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'MigrationShell', 'TestMigrationShellMockMigrationShell',
	array('in', 'out', 'err', 'hr', '_welcome', '_stop', '_showInfo')
);
Mock::generatePartial(
	'MigrationVersion', 'TestMigrationShellMockMigrationVersion',
	array('getMapping', 'getVersion', 'run')
);

/**
 * Custom class to test expectation
 *
 * @package       migrations
 * @subpackage    migrations.tests.cases.shells
 */
class MigrationShellExpectation extends SimpleExpectation {
/**
 * _expected property
 *
 * @var array
 */
	var $_expected = array();

/**
 * __construct method
 *
 * @param string $key
 * @param string $value
 */
	function __construct($key, $value) {
		parent::SimpleExpectation('%s');
		$this->_expected = compact('key', 'value');
	}

/**
 * test method
 *
 * @param array $compare
 * @return boolean
 */
	function test($compare) {
		extract($this->_expected);
		return $compare[$key] === $value;
	}
}

/**
 * TestMigrationShell
 *
 * @package       migrations
 * @subpackage    migrations.tests.cases.shells
 */
class TestMigrationShell extends TestMigrationShellMockMigrationShell {

/**
 * fromComparison method
 *
 * @param $migration
 * @param $comparison
 * @param $oldTables
 * @return void
 */
	function fromComparison($migration, $comparison, $oldTables) {
		return $this->_fromComparison($migration, $comparison, $oldTables);
	}

/**
 * writeMigration method
 *
 * @param $name
 * @param $class
 * @param $migration
 * @return void
 */
	function writeMigration($name, $class, $migration) {
		return $this->_writeMigration($name, $class, $migration);
	}

/**
 * writeMap method
 *
 * @param $map
 * @return void
 */
	function writeMap($map) {
		return $this->_writeMap($map);
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
 * @access public
 */
	var $fixtures = array('plugin.migrations.schema_migrations');

/**
 * tables property
 *
 * @var array
 * @access public
 */
	var $tables = array(
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
 * start test
 *
 * @return void
 **/
	function startTest() {
		$this->Dispatcher =& new TestMigrationShellMockShellDispatcher();
		$this->Shell =& new TestMigrationShell($this->Dispatcher);
		$this->Shell->type = 'test_migration_plugin';
		$this->Shell->path = TMP . 'tests' . DS;

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
		unset($this->Dispatcher, $this->Shell, $this->plugins);
	}

/**
 * testStartup method
 *
 * @return void
 **/
	function testStartup() {
		$Shell =& new TestMigrationShell($this->Dispatcher);
		$Shell->startup();
		$this->assertEqual($Shell->connection, 'default');
		$this->assertEqual($Shell->type, 'app');
		
		$Shell->params = array(
			'connection' => 'test_suite',
			'plugin' => 'migrations'
		);
		$Shell->startup();
		$this->assertEqual($Shell->connection, 'test_suite');
		$this->assertEqual($Shell->type, 'migrations');
	}

/**
 * testRun method
 *
 * @return void
 **/
	function testRun() {
		$back = $this->Shell->Version;

		$Version =& new TestMigrationShellMockMigrationVersion(array('connection' => 'test_suite'));
		$this->Shell->Version = $Version;
		$this->Shell->setReturnValue('_stop', false);

		$mapping = array();
		for ($i = 1; $i <= 10; $i++) {
			$mapping[$i] = array(
				'version' => $i, 'name' => '001_schema_dump',
				'class' => 'M4af9d151e1484b74ad9d007058157726',
				'type' => $this->Shell->type, 'migrated' => null
			);
		}
		$Version->setReturnValue('getMapping', $mapping);

		// Variable used on expectArgumentsAt method
		$runCount = $versionCount = $inCount = 0;

		// cake migration run - no mapping
		$Version->setReturnValueAt(0, 'getMapping', false);
		$this->Shell->args = array();
		$this->assertFalse($this->Shell->run());

		// cake migration run up
		$Version->setReturnValueAt($versionCount++, 'getVersion', 0);
		$Version->expectArgumentsAt($runCount++, 'run', array(new MigrationShellExpectation('direction', 'up')));
		$this->Shell->args = array('up');
		$this->assertTrue($this->Shell->run());

		// cake migration run up - on last version == stop
		$Version->setReturnValueAt($versionCount++, 'getVersion', 10);
		$this->Shell->args = array('up');
		$this->assertFalse($this->Shell->run());

		// cake migration run down - on version 0 == stop
		$Version->setReturnValueAt($versionCount++, 'getVersion', 0);
		$this->Shell->args = array('down');
		$this->assertFalse($this->Shell->run());

		// cake migration run down
		$Version->setReturnValueAt($versionCount++, 'getVersion', 1);
		$Version->expectArgumentsAt($runCount++, 'run', array(new MigrationShellExpectation('direction', 'down')));
		$this->Shell->args = array('down');
		$this->assertTrue($this->Shell->run());

		// cake migration run all
		$Version->setReturnValueAt($versionCount++, 'getVersion', 1);
		$Version->expectArgumentsAt($runCount++, 'run', array(new MigrationShellExpectation('version', 10)));
		$this->Shell->args = array('all');
		$this->assertTrue($this->Shell->run());

		// cake migration run reset
		$Version->setReturnValueAt($versionCount++, 'getVersion', 9);
		$Version->expectArgumentsAt($runCount++, 'run', array(new MigrationShellExpectation('version', 0)));
		$this->Shell->args = array('reset');
		$this->assertTrue($this->Shell->run());

		// cake migration run - answers 0, 11, 1
		$Version->setReturnValueAt($versionCount++, 'getVersion', 0);
		$Version->expectArgumentsAt($runCount++, 'run', array(new MigrationShellExpectation('version', 1)));
		$this->Shell->setReturnValueAt($inCount++, 'in', 0);
		$this->Shell->setReturnValueAt($inCount++, 'in', 11);
		$this->Shell->setReturnValueAt($inCount++, 'in', 1);
		$this->Shell->args = array();
		$this->assertTrue($this->Shell->run());

		// cake migration run - answers 0
		$Version->setReturnValueAt($versionCount++, 'getVersion', 1);
		$Version->expectArgumentsAt($runCount++, 'run', array(new MigrationShellExpectation('version', 0)));
		$this->Shell->setReturnValueAt($inCount++, 'in', 0);
		$this->Shell->args = array();
		$this->assertTrue($this->Shell->run());

		// cake migration run - answers 10
		$Version->setReturnValueAt($versionCount++, 'getVersion', 9);
		$Version->expectArgumentsAt($runCount++, 'run', array(new MigrationShellExpectation('version', 10)));
		$this->Shell->setReturnValueAt($inCount++, 'in', 10);
		$this->Shell->args = array();
		$this->assertTrue($this->Shell->run());

		// cake migration run 0 - on version 0 == stop
		$Version->setReturnValueAt($versionCount++, 'getVersion', 0);
		$this->Shell->args = array('0');
		$this->assertFalse($this->Shell->run());

		// cake migration run 1
		$Version->setReturnValueAt($versionCount++, 'getVersion', 0);
		$Version->expectArgumentsAt($runCount++, 'run', array(new MigrationShellExpectation('version', 1)));
		$this->Shell->args = array('1');
		$this->assertTrue($this->Shell->run());

		// cake migration run 11
		$Version->setReturnValueAt($versionCount++, 'getVersion', 0);
		$this->Shell->args = array('11');
		$this->assertFalse($this->Shell->run());

		// Changing values back
		$this->Shell->Version = $back;
		unset($back);
	}

/**
 * testFromComparisonTableActions method
 *
 * @return void
 **/
	function testFromComparisonTableActions() {
		$comparison = array(
			'users' => array('add' => $this->tables['users']),
			'posts' => array('add' => $this->tables['posts'])
		);
		$oldTables = array();
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables);
		$expected = array(
			'up' => array('create_table' => $this->tables),
			'down' => array('drop_table' => array('users', 'posts'))
		);
		$this->assertEqual($result, $expected);

		$comparison = array('posts' => array('add' => $this->tables['posts']));
		$oldTables = array('users' => $this->tables['users']);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables);
		$expected = array(
			'up' => array(
				'create_table' => array('posts' => $this->tables['posts']),
				'drop_table' => array('users')
			),
			'down' => array(
				'drop_table' => array('posts'),
				'create_table' => array('users' => $this->tables['users'])
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testFromComparisonFieldActions method
 *
 * @return void
 **/
	function testFromComparisonFieldActions() {
		// Add field/index
		$oldTables = array('posts' => $this->tables['posts']);

		$comparison = array(
			'posts' => array('add' => array(
				'views' => array('type' => 'integer', 'null' => false)
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables);
		$expected = array(
			'up' => array(
				'add_field' => array(
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
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables);
		$expected = array(
			'up' => array(
				'add_field' => array(
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
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables);
		$expected = array(
			'up' => array(
				'add_field' => array(
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
		$oldTables = array('posts' => $this->tables['posts']);
		$oldTables['posts']['views'] = array('type' => 'integer', 'null' => false);
		$oldTables['posts']['indexes'] = array('VIEW_COUNT' => array('column' => 'views', 'unique' => false));

		$comparison = array(
			'posts' => array('drop' => array(
				'views' => array('type' => 'integer', 'null' => false)
			))
		);
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables);
		$expected = array(
			'up' => array(
				'drop_field' => array(
					'posts' => array('views')
				)
			),
			'down' => array(
				'add_field' => array(
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
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables);
		$expected = array(
			'up' => array(
				'drop_field' => array(
					'posts' => array('indexes' => array('VIEW_COUNT'))
				)
			),
			'down' => array(
				'add_field' => array(
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
		$result = $this->Shell->fromComparison(array(), $comparison, $oldTables);
		$expected = array(
			'up' => array(
				'drop_field' => array(
					'posts' => array('views', 'indexes' => array('VIEW_COUNT'))
				)
			),
			'down' => array(
				'add_field' => array(
					'posts' => array(
						'views' => array('type' => 'integer', 'null' => false),
						'indexes' => array('VIEW_COUNT' => array('column' => 'views', 'unique' => false))
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
 **/
	function testWriteMigration() {
		$users = $this->tables['users'];
		$users['indexes'] = array('UNIQUE_USER' => array('column' => 'user', 'unique' => true));

		$migration = array(
			'up' => array(
				'create_table' => array('users' => $users),
				'add_field' => array(
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
		$this->assertFalse(file_exists(TMP . 'tests' . DS . 'migration_test_file.php'));
		$this->assertTrue($this->Shell->writeMigration('migration_test_file', 'M' . str_replace('-', '', String::uuid()), $migration));
		$this->assertTrue(file_exists(TMP . 'tests' . DS . 'migration_test_file.php'));

		$result = array();
		$array = explode("\n", file_get_contents(TMP . 'tests' . DS . 'migration_test_file.php'));
		foreach ($array as $line) {
			if ($line == "\tvar \$migration = array(") {
				$result[] = $line;
			} else if (!empty($result) && $line == "\t);") {
				$result[] = $line;
				break;
			} else if (!empty($result)) {
				$result[] = $line;
			}
		}
		$result = implode("\n", $result);

		$expected = <<<TEXT
	var \$migration = array(
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
			'add_field' => array(
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
		$this->assertEqual($result, $expected);
		@unlink(TMP . 'tests' . DS . 'migration_test_file.php');
	}

/**
 * testWriteMap method
 *
 * @return void
 **/
	function testWriteMap() {
		$map = array(
			1 => array('001_schema_dump' => 'M4af9d151e1484b74ad9d007058157726'),
			2 => array('002_create_some_sample_data' => 'M4af9d15154844819b7a0007058157726'),
			3 => array('003_add_xyz_support' => 'M4af9d151bf8c4ce5a25e007058157726')
		);
		$this->assertFalse(file_exists(TMP . 'tests' . DS . 'map.php'));
		$this->assertTrue($this->Shell->writeMap($map));
		$this->assertTrue(file_exists(TMP . 'tests' . DS . 'map.php'));

		$result = file_get_contents(TMP . 'tests' . DS . 'map.php');
		$expected = <<<TEXT
<?php
\$map = array(
	1 => array(
		'001_schema_dump' => 'M4af9d151e1484b74ad9d007058157726'),
	2 => array(
		'002_create_some_sample_data' => 'M4af9d15154844819b7a0007058157726'),
	3 => array(
		'003_add_xyz_support' => 'M4af9d151bf8c4ce5a25e007058157726'),
);
?>
TEXT;
		$this->assertEqual($result, $expected);
		@unlink(TMP . 'tests' . DS . 'map.php');
	}

/**
 * testGenerate method
 *
 * @return void
 */
	function testGenerate() {
		$this->Shell->setReturnValueAt(0, 'in', '001 schema dump');
		$this->Shell->setReturnValueAt(1, 'in', 'n');

		$this->assertFalse(file_exists(TMP . 'tests' . DS . '001_schema_dump.php'));
		$this->assertFalse(file_exists(TMP . 'tests' . DS . 'map.php'));
		$this->Shell->generate();
		$this->assertTrue(file_exists(TMP . 'tests' . DS . '001_schema_dump.php'));
		$this->assertTrue(file_exists(TMP . 'tests' . DS . 'map.php'));

		$result = file_get_contents(TMP . 'tests' . DS . 'map.php');
		$pattern = <<<TEXT
/^<\?php
\\\$map = array\(
	1 => array\(
		'001_schema_dump' => 'M([a-zA-Z0-9]+)'\),
\);
\?>$/
TEXT;
		$this->assertPattern($pattern, $result);

		// Adding other migration to it
		$this->Shell->expectCallCount('err', 1);
		$this->Shell->setReturnValueAt(2, 'in', '002_invalid_name');
		$this->Shell->setReturnValueAt(3, 'in', '002 create some sample data');
		$this->Shell->setReturnValueAt(4, 'in', 'n');

		$this->assertFalse(file_exists(TMP . 'tests' . DS . '002_create_some_sample_data.php'));
		$this->Shell->generate();
		$this->assertTrue(file_exists(TMP . 'tests' . DS . '002_create_some_sample_data.php'));

		$result = file_get_contents(TMP . 'tests' . DS . 'map.php');
		$pattern = <<<TEXT
/^<\?php
\\\$map = array\(
	1 => array\(
		'001_schema_dump' => 'M([a-zA-Z0-9]+)'\),
	2 => array\(
		'002_create_some_sample_data' => 'M([a-zA-Z0-9]+)'\),
\);
\?>$/
TEXT;
		$this->assertPattern($pattern, $result);

		// Remove created files
		@unlink(TMP . 'tests' . DS . '001_schema_dump.php');
		@unlink(TMP . 'tests' . DS . '002_create_some_sample_data.php');
		@unlink(TMP . 'tests' . DS . 'map.php');
	}
}
?>