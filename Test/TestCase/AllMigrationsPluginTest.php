<?php
use Cake\Core\Plugin;

class AllMigrationsPluginTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Migrations Plugin Tests');

		$basePath = Plugin::path('Migrations') . DS . 'Test' . DS . 'TestCase' . DS;

		// Libs
		$suite->addTestFile($basePath . 'Lib' . DS . 'MigrationVersionTest.php');
		$suite->addTestFile($basePath . 'Lib' . DS . 'Model' . DS . 'CakeMigrationTest.php');
		$suite->addTestFile($basePath . 'Lib' . DS . 'Migration' . DS . 'PrecheckConditionTest.php');

		// Console
		$suite->addTestFile($basePath . 'Console' . DS . 'Command' . DS . 'MigrationShellTest.php');

		return $suite;
	}

}