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
namespace Migrations\Lib;

use Exception;

/**
 * Exception used when something goes wrong on migrations
 *
 * @package	   migrations
 * @subpackage	migrations.libs.model
 */
class MigrationException extends Exception {

/**
 * Reference to the Migration being processed on time the error ocurred
 * @var CakeMigration
 */
	public $Migration;

/**
 * Constructor
 *
 * @param CakeMigration $Migration Reference to the Migration
 * @param string $message Message explaining the error
 * @param int $code Error code
 * @return \MigrationException
 */
	public function __construct($Migration, $message = '', $code = 0) {
		parent::__construct($message, $code);
		$this->Migration = $Migration;
	}
}
