<?php 

abstract class PrecheckBase {

	public function tableExists($table) {
		$this->migration->db->cacheSources = false;
		$tables = $this->migration->db->listSources();
		return in_array($this->migration->db->fullTableName($table, false), $tables);
	}

	public function fieldExists($table, $field) {
		if (!$this->tableExists($table)) {
			return false;
		}
		$fields = $this->migration->db->describe($table);
		return !empty($fields[$field]);
	}

	public function beforeAction($migration, $type, $data) {
		$this->migration = $migration;
		switch ($type) {
			case 'create_table':					
				return $this->checkCreateTable($data['table']);
				break;
			case 'drop_table':
				return $this->checkDropTable($data['table']);
				break;
			case 'rename_table':
				return $this->checkCreateTable($data['new_table']) && $this->checkDropTable($data['old_table']);
				break;
			case 'add_field':
				return $this->checkAddField($data['table'], $data['field']);
				break;
			case 'drop_field':
				return $this->checkDropField($data['table'], $data['field']);
				break;
			case 'change_field':
				return $this->checkAddField($data['table'], $data['field']);
				break;
			case 'rename_field':
				return $this->checkAddField($data['table'], $data['new_name']) && $this->checkDropField($data['table'], $data['old_name']);
				break;
			default:
				throw new MigrationException($this->migration, sprintf(
					__d('migrations', 'Migration action type (%s) is not one of valid actions type.'), $type
				), E_USER_NOTICE);
		}
	}	
	

}

