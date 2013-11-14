<?php
namespace Migrations\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SchemaMigrationsFixture extends TestFixture {

/**
 *
 */
	public $name = 'SchemaMigrations';

/**
 *
 */
	public $fields = array(
		'id' => ['type' => 'integer', 'null' => false, 'default' => null],
		'class' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 33],
		'type' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 50],
		'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 *
 */
	public $records = array(
		array('id' => '1', 'class' => 'InitMigrations', 'type' => 'migrations', 'created' => '2009-11-10 00:55:34'),
		array('id' => '2', 'class' => 'ConvertVersionToClassNames', 'type' => 'migrations', 'created' => '2011-11-18 13:53:32')
	);

}