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
		'version' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'type' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 50),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
	));
/**
 *
 */
	public $records = array(
		array('id' => '1', 'version' => '1', 'type' => 'migrations', 'created' => '2009-11-10 00:55:34')
	);
}
?>