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
class PrecheckException extends PrecheckBase {

	public function checkDropTable($table) {
		if (!$this->tableExists($table)) {
			throw new MigrationException($this->migration,
				__d('migrations', 'Table "%s" does not exists in database.', $this->migration->db->fullTableName($table, false, false))
			);
		}		
		return true;
	}

	public function checkCreateTable($table) {
		if ($this->tableExists($table)) {
			throw new MigrationException($this->migration,
				__d('migrations', 'Table "%s" already exists in database.', $this->migration->db->fullTableName($table, false, false))
			);
		}		
		return true;
	}

	public function checkDropField($table, $field) {
		if (!$this->fieldExists($table, $field)) {
			throw new MigrationException($this->migration, sprintf(
				__d('migrations', 'Field "%s" does not exists in "%s".'), $field, $table
			));	
		}
		return true;
	}

	public function checkAddField($table, $field) {
		if ($this->fieldExists($table, $field)) {
			throw new MigrationException($this->migration, sprintf(
				__d('migrations', 'Field "%s" already exists in "%s".'), $field, $table
			));	
		}
		return true;
	}

}