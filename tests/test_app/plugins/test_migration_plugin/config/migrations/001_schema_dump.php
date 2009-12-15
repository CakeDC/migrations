<?php
class M4af6d40056b04408808500cb58157726 extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	var $description = 'Version 001 (schema dump) of TestMigrationPlugin';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	var $migration = array(
		'up' => array(),
		'down' => array()
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	function after($direction) {
		return true;
	}
}
?>