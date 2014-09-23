<?php
/**
 * Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009 - 2014, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
echo "<?php\n";
?>
class <?php echo $class; ?> extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = '';

/**
 * Actions to be performed
 *
 * @var array $migration
 */
	public $migration = array(
<?php echo $migration; ?>
	);

/**
 * Before migration callback
 *
 * @param string $direction "up" or "down" direction of migration process.
 * @return bool Should process continue
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction "up" or "down" direction of migration process.
 * @return bool Should process continue
 */
	public function after($direction) {
		return true;
	}
}
