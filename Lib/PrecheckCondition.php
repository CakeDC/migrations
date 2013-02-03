<?php 

App::uses('PrecheckBase', 'Migrations.Lib');
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

