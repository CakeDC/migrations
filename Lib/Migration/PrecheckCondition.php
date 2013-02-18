<?php 
/**
 * CakePHP Migrations
 *
 * Copyright 2009 - 2013, Cake Development Corporation
 *						1785 E. Sahara Avenue, Suite 490-423
 *						Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2009 - 2013, Cake Development Corporation
 * @link	  http://codaset.com/cakedc/migrations/
 * @package   plugns.migrations
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('PrecheckBase', 'Migrations.Lib/Migration');

class PrecheckCondition extends PrecheckBase {

/**
 * Perform check before table drop.
 *
 * @param string $table
 * @return bool
 */
	public function checkDropTable($table) {
		return $this->tableExists($table);
	}

/**
 * Perform check before table create.
 *
 * @param string $table
 * @return bool
 */
	public function checkCreateTable($table) {
		return !$this->tableExists($table);
	}

/**
 * Perform check before field drop.
 *
 * @param string $table
 * @param string $field
 * @return bool
 */
	public function checkDropField($table, $field) {
		return $this->tableExists($table) && $this->fieldExists($table, $field);
	}

/**
 * Perform check before field create.
 *
 * @param string $table
 * @param string $field
 * @return bool
 */
	public function checkAddField($table, $field) {
		return $this->tableExists($table) && !$this->fieldExists($table, $field);
	}

}

