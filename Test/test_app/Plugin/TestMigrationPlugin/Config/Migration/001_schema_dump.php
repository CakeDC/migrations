<?php
class M4af6d40056b04408808500cb58157726 extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = 'Version 001 (schema dump) of TestMigrationPlugin';

/**
 * Actions to be performed
 *
 * @var array $migration
 */
	public $migration = array(
		'up' => array(),
		'down' => array()
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 */
	public function after($direction) {
		return true;
	}

}