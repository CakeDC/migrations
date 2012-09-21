<?php
App::import('Model', 'Migrations.CakeMigration', false);

/**
 * TestCakeMigration
 *
 * @package       migrations
 * @subpackage    migrations.tests.cases.libs
 */
class TestCakeMigration extends CakeMigration {

/**
 * Connection used
 *
 * @var string
 * @access public
 */
	var $connection = 'test_suite';
}

/**
 * TestCallbackCakeMigration
 *
 * @package       migrations
 * @subpackage    migrations.tests.cases.libs
 */
class TestCallbackCakeMigration {

/**
 * calls property
 *
 * @var array
 * @access public
 */
	var $calls = array();

/**
 * beforeMigration method
 *
 * @access public
 * @return void
 */
	function beforeMigration(&$Migration, $type) {
		$this->calls[$Migration->direction]['beforeMigration'] = $type;
	}

/**
 * afterMigration method
 *
 * @access public
 * @return void
 */
	function afterMigration(&$Migration, $type) {
		$this->calls[$Migration->direction]['afterMigration'] = $type;
	}

/**
 * beforeAction method
 *
 * @access public
 * @return void
 */
	function beforeAction(&$Migration, $type, $data) {
		$this->calls[$Migration->direction]['beforeAction'][] = array('type' => $type, 'data' => $data);
	}

/**
 * afterAction method
 *
 * @access public
 * @return void
 */
	function afterAction(&$Migration, $type, $data) {
		$this->calls[$Migration->direction]['afterAction'][] = array('type' => $type, 'data' => $data);
	}
}

/**
 * CakeMigrationTest
 *
 * @package       migration
 * @subpackage    migration.tests.cases.libs
 */
class CakeMigrationTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array(
		'core.user', 'core.post'
	);

/**
 * autoFixtures property
 *
 * @var array
 * @access public
 */
	var $autoFixtures = false;

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
 * testCreateTable method
 *
 * @access public
 * @return void
 */
	function testCreateDropTable() {
		$migration = new TestCakeMigration(array(
			'up' => array('create_table' => array('migration_posts' => $this->tables['posts'], 'migration_users' => $this->tables['users'])),
			'down' => array('drop_table' => array('migration_posts', 'migration_users'))
		));

		$sources = $this->db->listSources();
		$this->assertFalse(in_array($this->db->fullTableName('migration_user', false), $sources));
		$this->assertFalse(in_array($this->db->fullTableName('migration_posts', false), $sources));

		$this->assertTrue($migration->run('up'));
		$sources = $this->db->listSources();
		$this->assertTrue(in_array($this->db->fullTableName('migration_users', false), $sources));
		$this->assertTrue(in_array($this->db->fullTableName('migration_posts', false), $sources));

		$this->assertTrue($migration->run('down'));
		$sources = $this->db->listSources();
		$this->assertFalse(in_array($this->db->fullTableName('migration_users', false), $sources));
		$this->assertFalse(in_array($this->db->fullTableName('migration_posts', false), $sources));
	}

/**
 * testRenameTable method
 *
 * @access public
 * @return void
 */
	function testRenameTable() {
		$this->loadFixtures('User', 'Post');

		$migration = new TestCakeMigration(array(
			'up' => array('rename_table' => array('posts' => 'renamed_posts')),
			'down' => array('rename_table' => array('renamed_posts' => 'posts'))
		));

		$sources = $this->db->listSources();
		$this->assertTrue(in_array($this->db->fullTableName('posts', false), $sources));
		$this->assertFalse(in_array($this->db->fullTableName('renamed_posts', false), $sources));

		$this->assertTrue($migration->run('up'));
		$sources = $this->db->listSources();
		$this->assertFalse(in_array($this->db->fullTableName('posts', false), $sources));
		$this->assertTrue(in_array($this->db->fullTableName('renamed_posts', false), $sources));

		$this->assertTrue($migration->run('down'));
		$sources = $this->db->listSources();
		$this->assertTrue(in_array($this->db->fullTableName('posts', false), $sources));
		$this->assertFalse(in_array($this->db->fullTableName('renamed_posts', false), $sources));
	}

/**
 * testCreateDropField method
 *
 * @access public
 * @return void
 */
	function testCreateDropField() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test_suite'));

		$migration = new TestCakeMigration(array(
			'up' => array(
				'create_field' => array(
					'posts' => array('views' => array('type' => 'integer', 'null' => false))
				)
			),
			'down' => array(
				'drop_field' => array('posts' => array('views'))
			)
		));

		$fields = $this->db->describe($model);
		$this->assertFalse(isset($fields['views']));

		$this->assertTrue($migration->run('up'));
		$fields = $this->db->describe($model);
		$this->assertTrue(isset($fields['views']));

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertFalse(isset($fields['views']));

		// Indexes
		$migration = new TestCakeMigration(array(
			'up' => array(
				'create_field' => array(
					'posts' => array(
						'views' => array('type' => 'integer', 'null' => false),
						'indexes' => array(
							'VIEW_COUNT' => array('column' => 'views', 'unique' => false),
							'UNIQUE_AUTHOR_TITLE' => array('column' => array('author_id', 'title'), 'unique' => true)
						)
					)
				)
			),
			'down' => array(
				'drop_field' => array('posts' => array('views', 'indexes' => array('UNIQUE_AUTHOR_TITLE')))
			)
		));

		$fields = $this->db->describe($model);
		$this->assertFalse(isset($fields['views']));

		$this->assertTrue($migration->run('up'));
		$fields = $this->db->describe($model);
		$this->assertTrue(isset($fields['views']));
		$this->assertEqual($fields['views']['key'], 'index');

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertFalse(isset($fields['views']));
	}

/**
 * testAlterField method
 *
 * @access public
 * @return void
 */
	function testAlterField() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test_suite'));

		$migration = new TestCakeMigration(array(
			'up' => array(
				'alter_field' => array(
					'posts' => array('published' => array('default' => 'Y'))
				)
			),
			'down' => array(
				'alter_field' => array(
					'posts' => array('published' => array('default' => 'N'))
				)
			)
		));

		$fields = $this->db->describe($model);
		$this->assertEqual($fields['published']['default'], 'N');

		$this->assertTrue($migration->run('up'));
		$fields = $this->db->describe($model);
		$this->assertEqual($fields['published']['default'], 'Y');

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertEqual($fields['published']['default'], 'N');

		// Alter and rename field
		$migration = new TestCakeMigration(array(
			'up' => array(
				'alter_field' => array(
					'posts' => array('published' => array('name' => 'renamed_published', 'default' => 'Y'))
				)
			),
			'down' => array(
				'alter_field' => array(
					'posts' => array('renamed_published' => array('name' => 'published', 'default' => 'N'))
				)
			)
		));

		$fields = $this->db->describe($model);
		$this->assertTrue(isset($fields['published']));
		$this->assertFalse(isset($fields['renamed_published']));
		$this->assertEqual($fields['published']['default'], 'N');

		$this->assertTrue($migration->run('up'));
		$fields = $this->db->describe($model);
		$this->assertFalse(isset($fields['published']));
		$this->assertTrue(isset($fields['renamed_published']));
		$this->assertEqual($fields['renamed_published']['default'], 'Y');

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertTrue(isset($fields['published']));
		$this->assertFalse(isset($fields['renamed_published']));
		$this->assertEqual($fields['published']['default'], 'N');

		// Test changing from string to integer
		$migration = new TestCakeMigration(array(
			'up' => array(
				'alter_field' => array(
					'posts' => array('published' => array('type' => 'integer'))
				)
			),
			'down' => array(
				'alter_field' => array(
					'posts' => array('published' => array('type' => 'string'))
				)
			)
		));

		$fields = $this->db->describe($model);
		$this->assertEqual($fields['published']['type'], 'string');

		$this->assertTrue($migration->run('up'));
		$fields = $this->db->describe($model);
		$this->assertEqual($fields['published']['type'], 'integer');

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertEqual($fields['published']['type'], 'string');

	}

/**
 * testRenameField method
 *
 * @access public
 * @return void
 */
	function testRenameField() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test_suite'));

		$migration = new TestCakeMigration(array(
			'up' => array('rename_field' => array('posts' => array('updated' => 'renamed_updated'))),
			'down' => array('rename_field' => array('posts' => array('renamed_updated' => 'updated'))),
		));

		$fields = $this->db->describe($model);
		$this->assertTrue(isset($fields['updated']));
		$this->assertFalse(isset($fields['renamed_updated']));

		$this->assertTrue($migration->run('up'));
		$fields = $this->db->describe($model);
		$this->assertFalse(isset($fields['updated']));
		$this->assertTrue(isset($fields['renamed_updated']));

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertTrue(isset($fields['updated']));
		$this->assertFalse(isset($fields['renamed_updated']));
	}

/**
 * testCallbacks method
 *
 * @access public
 * @return void
 */
	function testCallbacks() {
		$this->loadFixtures('User');

		$callback = new TestCallbackCakeMigration();
		$migration = new TestCakeMigration(array(
			'up' => array(
				'create_table' => array('migration_posts' => $this->tables['posts']),
				'create_field' => array(
					'users' => array(
						'email' => array('type' => 'string', 'null' => false),
						'indexes' => array('UNIQUE_USER' => array('column' => 'user', 'unique' => true))
					)
				),
			),
			'down' => array(
				'drop_table' => array('migration_posts'),
				'drop_field' => array('users' => array('email', 'indexes' => array('UNIQUE_USER')))
			),
			'callback' => $callback
		));

		$this->assertTrue($migration->run('up'));
		$this->assertTrue(isset($callback->calls['up']));
		$result = $callback->calls['up'];
		$expected = array(
			array('type' => 'create_table', 'data' => array('table' => 'migration_posts')),
			array('type' => 'add_field', 'data' => array('table' => 'users', 'field' => 'email')),
			array('type' => 'add_index', 'data' => array('table' => 'users', 'index' => 'UNIQUE_USER'))
		);
		$this->assertEqual($result['afterMigration'], 'up');
		$this->assertEqual($result['beforeMigration'], 'up');
		$this->assertEqual($result['afterAction'], $expected);
		$this->assertEqual($result['beforeAction'], $expected);
		$this->assertEqual(array_keys($result), array('beforeMigration', 'beforeAction', 'afterAction', 'afterMigration'));

		$this->assertTrue($migration->run('down'));
		$this->assertTrue(isset($callback->calls['down']));
		$result = $callback->calls['down'];
		$expected = array(
			array('type' => 'drop_table', 'data' => array('table' => 'migration_posts')),
			array('type' => 'drop_field', 'data' => array('table' => 'users', 'field' => 'email')),
			array('type' => 'drop_index', 'data' => array('table' => 'users', 'index' => 'UNIQUE_USER'))
		);
		$this->assertEqual($result['afterMigration'], 'down');
		$this->assertEqual($result['beforeMigration'], 'down');
		$this->assertEqual($result['afterAction'], $expected);
		$this->assertEqual($result['beforeAction'], $expected);
		$this->assertEqual(array_keys($result), array('beforeMigration', 'beforeAction', 'afterAction', 'afterMigration'));
	}

/**
 * testGenerateModel method
 *
 * @access public
 * @return void
 */
	function testGenerateModel() {
		$migration = new TestCakeMigration();

		$return = $migration->generateModel('Post');
		$this->assertIsA($return, 'AppModel');
		$this->assertEqual($return->name, 'Post');
		$this->assertEqual($return->table, 'posts');

		$return = $migration->generateModel('Post', 'users');
		$this->assertIsA($return, 'AppModel');
		$this->assertEqual($return->name, 'Post');
		$this->assertEqual($return->table, 'users');
	}
	
/**
 * Test run method with invalid syntaxes
 * 
 * @access public
 * @return void
 */
	function testRunInvalidSyntaxes() {
		$migration = new TestCakeMigration(array(
			'up' => array('do_something' => array('posts' => array('updated' => 'renamed_updated'))),
			'down' => array('undo_something' => array('posts' => array('renamed_updated' => 'updated'))),
		));

		$this->expectError('Migration direction (last) is not one of valid directions.');
		$this->assertFalse($migration->run('last'));
		
		$this->expectError('Migration action type (do_something) is not one of valid actions type.');
		$this->assertTrue($migration->run('up'));
	}
}
?>
