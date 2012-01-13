<?php 

App::uses('PrecheckBase', 'Migrations.Lib');
class PrecheckException extends PrecheckBase {

	public function checkDropTable($table) {
		if (!$this->tableExists($table)) {
			throw new MigrationException($this->migration,
				__d('migrations', 'Table "%s" does not exists in database.', $this->migration->db->fullTableName($table, false))
			);
		}		
		return true;
	}

	public function checkCreateTable($table) {
		if ($this->tableExists($table)) {
			throw new MigrationException($this->migration,
				__d('migrations', 'Table "%s" already exists in database.', $this->migration->db->fullTableName($table, false))
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

?>