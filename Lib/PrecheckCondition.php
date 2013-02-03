<?php 
App::uses('PrecheckBase', 'Migrations.Lib');
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
class PrecheckCondition extends PrecheckBase {

	public function checkDropTable($table) {
		return $this->tableExists($table);
	}

	public function checkCreateTable($table) {
		return !$this->tableExists($table);
	}

	public function checkDropField($table, $field) {
		return $this->tableExists($table);
	}

	public function checkAddField($table, $field) {
		return !$this->fieldExists($table, $field);
	}

}

