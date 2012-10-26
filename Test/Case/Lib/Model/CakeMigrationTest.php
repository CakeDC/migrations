<?php

App::uses('CakeMigration', 'Migrations.Lib');
App::uses('MigrationVersion', 'Migrations.Lib');

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
 */
	public $connection = 'test';
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
 */
	public $calls = array();

/**
 * beforeMigration method
 *
 * @return void
 */
	public function beforeMigration(&$Migration, $type) {
		$this->calls[$Migration->direction]['beforeMigration'] = $type;
	}

/**
 * afterMigration method
 *
 * @return void
 */
	public function afterMigration(&$Migration, $type) {
		$this->calls[$Migration->direction]['afterMigration'] = $type;
	}

/**
 * beforeAction method
 *
 * @return void
 */
	public function beforeAction(&$Migration, $type, $data) {
		$this->calls[$Migration->direction]['beforeAction'][] = array('type' => $type, 'data' => $data);
	}

/**
 * afterAction method
 *
 * @return void
 */
	public function afterAction(&$Migration, $type, $data) {
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
 */
	public $fixtures = array(
		'core.user', 'core.post');

/**
 * autoFixtures property
 *
 * @var array
 */
	public $autoFixtures = false;

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
 * testCreateTable method
 *
 * @return void
 */
	public function testCreateDropTable() {
		$migration = new TestCakeMigration(array(
			'up' => array('create_table' => array('migration_posts' => $this->tables['posts'], 'migration_users' => $this->tables['users'])),
			'down' => array('drop_table' => array('migration_posts', 'migration_users'))
		));

		$sources = $this->db->listSources();
		$this->assertFalse(in_array($this->db->fullTableName('migration_user', false, false), $sources));
		$this->assertFalse(in_array($this->db->fullTableName('migration_posts', false, false), $sources));
		$this->assertTrue($migration->run('up'));
		$sources = $this->db->listSources();
		$this->assertTrue(in_array($this->db->fullTableName('migration_users', false, false), $sources));
		$this->assertTrue(in_array($this->db->fullTableName('migration_posts', false, false), $sources));

		try {
			$migration->run('up');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertEqual('Table "' . $this->db->fullTableName('migration_posts', false, false) . '" already exists in database.', $e->getMessage());
		}

		$this->assertTrue($migration->run('down'));
		$sources = $this->db->listSources();
		$this->assertFalse(in_array($this->db->fullTableName('migration_users', false, false), $sources));
		$this->assertFalse(in_array($this->db->fullTableName('migration_posts', false, false), $sources));
	}

/**
 * testRenameTable method
 *
 * @return void
 */
	public function testRenameTable() {
		$this->loadFixtures('User', 'Post');

		$migration = new TestCakeMigration(array(
			'up' => array('rename_table' => array('posts' => 'renamed_posts')),
			'down' => array('rename_table' => array('renamed_posts' => 'posts'))
		));

		$sources = $this->db->listSources();
		$this->assertTrue(in_array($this->db->fullTableName('posts', false, false), $sources));
		$this->assertFalse(in_array($this->db->fullTableName('renamed_posts', false, false), $sources));

		$this->assertTrue($migration->run('up'));
		$sources = $this->db->listSources();
		$this->assertFalse(in_array($this->db->fullTableName('posts', false, false), $sources));
		$this->assertTrue(in_array($this->db->fullTableName('renamed_posts', false, false), $sources));

		try {
			$migration->run('up');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertEqual($e->getMessage(), 'Table "' . $this->db->fullTableName('posts', false, false) . '" does not exists in database.');
		}

		$this->assertTrue($migration->run('down'));
		$sources = $this->db->listSources();
		$this->assertTrue(in_array($this->db->fullTableName('posts', false, false), $sources));
		$this->assertFalse(in_array($this->db->fullTableName('renamed_posts', false, false), $sources));
	}

/**
 * testCreateDropField method
 *
 * @return void
 */
	public function testCreateDropField() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test'));

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

		try {
			$migration->run('up');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertEqual('Field "views" already exists in "posts".', $e->getMessage());
		}

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertFalse(isset($fields['views']));

		try {
			$migration->run('down');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertEqual('Field "views" does not exists in "posts".', $e->getMessage());
		}
	}

/**
 * testCreateDropIndex method
 *
 * @return void
 */
	public function testCreateDropIndex() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test'));

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
		$indexes = $this->db->index($model);
		$this->assertTrue(!empty($indexes));
		$uniqueAuthorTitle = array('column' => array(0 => 'author_id', 1 => 'title'), 'unique' => 1);
		$viewCount = array('column' => 'views', 'unique' => 0);
		$this->assertTrue(isset($indexes['UNIQUE_AUTHOR_TITLE']));
		$this->assertTrue(isset($indexes['VIEW_COUNT']));
		$this->assertIdentical($indexes['UNIQUE_AUTHOR_TITLE'], $uniqueAuthorTitle);
		$this->assertIdentical($indexes['VIEW_COUNT'], $viewCount);
		$this->assertTrue(isset($fields['views']));
		$this->assertEqual($fields['views']['key'], 'index');

		try {
			$migration->run('up');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			//$this->pass('Exception caught');
		}

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$indexes = $this->db->index($model);
		$this->assertFalse(isset($indexes['UNIQUE_AUTHOR_TITLE']));
		$this->assertFalse(isset($indexes['VIEW_COUNT']));
		$this->assertFalse(isset($fields['views']));
	}

/**
 * Test alter index (changing column of an index). Issue #26
 * @return void
 */
	public function testAlterIndex() {
		$this->loadFixtures('Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test'));
		$fields = $this->db->describe($model);
		$indexes = $this->db->index($model);
		$this->assertTrue(!empty($indexes) && is_array($indexes));
		$this->assertEqual($indexes['PRIMARY']['column'], 'id');
		$this->assertFalse(array_key_exists('key', $fields['published']));
		$this->assertFalse(array_key_exists('key', $fields['title']));
		$migration = new TestCakeMigration(array(
			'up' => array(
				'drop_field' => array(
					'posts' => array('title')
				),
				'create_field' => array(
					'posts' => array(
						'title' => array('type' => 'string', 'null' => false, 'length' => 255),
						'indexes' => array('NEW_INDEX' => array('column' => 'title', 'unique' => false))
					)
				)
			),
			'down' => array(
				'drop_field' => array(
					'posts' => array('title', 'indexes' => array('NEW_INDEX'))
				),
				'create_field' => array(
					'posts' => array(
						'title' => array('type' => 'string', 'null' => false, 'length' => 255, 'after' => 'author_id'),
					)
				)
				)
			)
		);

		$this->assertTrue($migration->run('up'));
		$indexes = $this->db->index($model);
		$this->assertArrayHasKey('NEW_INDEX', $indexes);
		$this->assertEqual($indexes['NEW_INDEX']['column'], 'title');
		$this->assertEqual($indexes['NEW_INDEX']['unique'], 0);

		$migration2 = new TestCakeMigration(array(
			'up' => array(
				'drop_field' => array(
					'posts' => array('published', 'title', 'indexes' => array('NEW_INDEX'))
				),
				'create_field' => array(
					'posts' => array(
						'published' => array('type' => 'string', 'null' => true, 'length' => 1, 'default' => 'N'),
						'title' => array('type' => 'string', 'null' => false, 'length' => 255),
						'indexes' => array('NEW_INDEX' => array('column' => 'published', 'unique' => false))
					)
				)
			),
			'down' => array(
				'drop_field' => array(
					'posts' => array('published', 'title', 'indexes' => array('NEW_INDEX'))
				),
				'create_field' => array(
					'posts' => array(
						'title' => array('type' => 'string', 'null' => false, 'length' => 255),
						'published' => array('type' => 'string', 'null' => true, 'length' => 1, 'default' => 'N', 'after' => 'body'),
						'indexes' => array('NEW_INDEX' => array('column' => 'title', 'unique' => false))
					)
				)
				)
			)
		);

		$this->assertTrue($migration2->run('up'));
		$indexes = $this->db->index($model);
		$fields = $this->db->describe($model);
		$this->assertFalse(array_key_exists('key', $fields['title']));
		$this->assertTrue(array_key_exists('key', $fields['published']));
		$this->assertEquals($indexes['NEW_INDEX']['column'], 'published');
		$this->assertEqual($indexes['NEW_INDEX']['unique'], 0);

		$this->assertTrue($migration2->run('down'));
		$indexes = $this->db->index($model);
		$fields = $this->db->describe($model);
		$this->assertTrue(array_key_exists('key', $fields['title']));
		$this->assertFalse(array_key_exists('key', $fields['published']));
		$this->assertEquals($indexes['NEW_INDEX']['column'], 'title');
		$this->assertEqual($indexes['NEW_INDEX']['unique'], 0);

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$indexes = $this->db->index($model);
		$this->assertTrue(!empty($indexes) && is_array($indexes));
		$this->assertEqual($indexes['PRIMARY']['column'], 'id');
		$this->assertFalse(array_key_exists('key', $fields['published']));
		$this->assertFalse(array_key_exists('key', $fields['title']));
	}

/**
 * testAlterField method
 *
 * @return void
 */
	public function testAlterField() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test'));

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

		try {
			$migration->migration['up']['alter_field']['posts']['inexistent'] = array('default' => 'N');
			$migration->run('up');
			$this->fail('No expection triggered');
		} catch (MigrationException $e) {
			$this->assertEqual('Field "inexistent" does not exists in "posts".', $e->getMessage());
		}

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertEqual($fields['published']['default'], 'N');
	}

/**
 * testAlterFieldLength method
 *
 * @access public
 * @return void
 */
	public function testAlterFieldLength() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test'));

		$migration = new TestCakeMigration(array(
			'up' => array(
				'alter_field' => array(
					'posts' => array('created' => array('type' => 'integer', 'length' => 11))
				)
			),
			'down' => array(
				'alter_field' => array(
					'posts' => array('created' => array('type' => 'datetime'))
				)
			)
		));

		$fields = $this->db->describe($model);
		$this->assertEqual($fields['created']['type'], 'datetime');
		$this->assertNull($fields['created']['length']);

		$this->assertTrue($migration->run('up'));
		$fields = $this->db->describe($model);
		$this->assertEqual($fields['created']['type'], 'integer');
		$this->assertEqual($fields['created']['length'], 11);

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertEqual($fields['created']['type'], 'datetime');
		$this->assertNull($fields['created']['length']);
	}

/**
 * testAlterAndRenameField method
 *
 * @return void
 */
	public function testAlterAndRenameField() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test'));

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
	}

/**
 * testRenameField method
 *
 * @return void
 */
	public function testRenameField() {
		$this->loadFixtures('User', 'Post');
		$model = new Model(array('table' => 'posts', 'ds' => 'test'));

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

		try {
			$migration->run('up');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertEqual('Field "updated" does not exists in "posts".', $e->getMessage());
		}

		$this->assertTrue($migration->run('down'));
		$fields = $this->db->describe($model);
		$this->assertTrue(isset($fields['updated']));
		$this->assertFalse(isset($fields['renamed_updated']));

		try {
			$migration->run('down');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertEqual('Field "renamed_updated" does not exists in "posts".', $e->getMessage());
		}
	}

/**
 * testCallbacks method
 *
 * @return void
 */
	public function testCallbacks() {
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
			array('type' => 'add_index', 'data' => array('table' => 'users', 'index' => 'UNIQUE_USER')));

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
			array('type' => 'drop_index', 'data' => array('table' => 'users', 'index' => 'UNIQUE_USER')),
			array('type' => 'drop_field', 'data' => array('table' => 'users', 'field' => 'email')));

		$this->assertEqual($result['afterMigration'], 'down');
		$this->assertEqual($result['beforeMigration'], 'down');
		$this->assertEqual($result['afterAction'], $expected);
		$this->assertEqual($result['beforeAction'], $expected);
		$this->assertEqual(array_keys($result), array('beforeMigration', 'beforeAction', 'afterAction', 'afterMigration'));
	}

	public function testTruncateLongIndexKey() {
		$migration = new TestCakeMigration(array(
			'up' => array(
				'create_table' => array(
					'migration_categories' => array(
						'id' => array(
							'type'    => 'string',
							'length ' => 36,
							'null'    => false,
							'key'     => 'primary'),
						'description' => array(
							'type'    => 'string',
							'null'    => false,
							'length' => 256,
							'default' => null),
						'info' => array(
							'type'    => 'string',
							'length' => 256,
							'null'    => false,
							'default' => null),
						'indexes' => array(
							'TESTING_INDEX' => array(
								'column' => array('description', 'info'),
								'unique' => 1)
						)
					)
				)
			),
			'down' => array(
				'drop_table' => array('migration_categories')
			)
		));
		$sources = $this->db->listSources();
		$this->assertFalse(in_array($this->db->fullTableName('migration_categories', false, false), $sources));
		try {
			$migration->run('up');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertPattern('/SQL Error/', $e->getMessage());
		}
		$this->assertFalse(in_array($this->db->fullTableName('migration_categories', false, false), $sources));
	}

/**
 * testGenerateModel method
 *
 * @return void
 */
	public function testGenerateModel() {
		$migration = new TestCakeMigration();

		$return = $migration->generateModel('Post');
		$this->assertInstanceOf('AppModel', $return);
		$this->assertEqual($return->name, 'Post');
		$this->assertEqual($return->table, 'posts');

		$return = $migration->generateModel('Post', 'users');
		$this->assertInstanceOf('AppModel', $return);
		$this->assertEqual($return->name, 'Post');
		$this->assertEqual($return->table, 'users');
	}

/**
 * Test run method with invalid syntaxes
 *
 * @return void
 */
	public function testRunInvalidSyntaxes() {
		$migration = new TestCakeMigration(array(
			'up' => array('do_something' => array('posts' => array('updated' => 'renamed_updated'))),
			'down' => array('undo_something' => array('posts' => array('renamed_updated' => 'updated'))),
		));

		try {
			$migration->run('last');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertEqual('Migration direction (last) is not one of valid directions.', $e->getMessage());
		}

		try {
			$migration->run('up');
			$this->fail('No exception triggered');
		} catch (MigrationException $e) {
			$this->assertEqual('Migration action type (do_something) is not one of valid actions type.', $e->getMessage());
		}
	}
}
