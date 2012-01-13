<?php
class SchemaMigrationsFixture extends CakeTestFixture {
/**
 *
 */
	public $name = 'SchemaMigrations';
/**
 *
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'class' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 33),
		'type' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 50),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
	));
/**
 *
 */
	public $records = array(
		array('id' => '1', 'class' => 'M4af6e0f0a1284147a0b100ca58157726', 'type' => 'migrations', 'created' => '2009-11-10 00:55:34'),
		array('id' => '2', 'class' => 'M4ec50d1f7a284842b1b770fdcbdd56cb', 'type' => 'migrations', 'created' => '2011-11-18 13:53:32')
	);
}
?>