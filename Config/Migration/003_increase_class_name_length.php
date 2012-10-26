<?php
class IncreaseClassNameLength extends CakeMigration {

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
			'alter_field' => array(
				'schema_migrations' => array(
					'class' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 255, 'name' => 'class')
				)
			)
		),
		'down' => array(
			'alter_field' => array(
				'schema_migrations' => array(
					'class' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 33, 'name' => 'class')
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