<?php
class AddChangeSetHash extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Increase the maximum length of class names.';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_field' => array(
				'schema_migrations' => array(
					'hash' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40)
				)
			)
		),
		'down' => array(
			'drop_field' => array(
				'schema_migrations' => array(
					'hash'
				)
			)
		)
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
		return true;
	}

}