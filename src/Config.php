<?php

namespace Testenv;

/**
 * Class Config
 * @package Testenv
 */
class Config {

	/**
	 * Some pseudo-static configuration options that currently can't be configured with command arguments or options.
	 * @const string DB_HOST Database server hostname
	 */
	const DB_HOST = 'localhost';

	/**
	 * @const string MYSQLDUMP_LOCATION Full path to 'mysqldump'
	 */
	const MYSQLDUMP_LOCATION = '/usr/bin/mysqldump';

	/**
	 * @const string MYSQL_LOCATION Full path to 'mysql'
	 */
	const MYSQL_LOCATION = '/usr/bin/mysql';

	/**
	 * @const string DRUPAL_KEEP_USERS Delete all users with an id that is not in this list, when copytype = basic.
	 * Provisional solution that covers the SP installation for now.
	 */
	const DRUPAL_KEEP_USERS = '11,12,19,72';

	/**
	 * @const string CIVI_KEEP_CONTACTS Delete all contacts with an id that is not in this list, when copytype = basic.
	 * Provisional solution that covers the SP installation for now.
	 */
	const CIVI_KEEP_CONTACTS = '1,4,6,7,8';

	/**
	 * @const string HOST_SUFFIX String to add by default after a server name.
	 */
	const HOST_SUFFIX = '.sp.nl';

	/**
	 * @var array Drush Commands
	 * Syntax: see http://docs.drush.org/en/master/commands/#creating-custom-drush-commands
	 */
	private static $drushCommands = [
		'testenv-copy-files'      => [
			'arguments'   => [
				'destination' => 'Destination directory',
			],
			'description' => 'Copy this site\'s files to a new testing environment',
			'examples'    => ['drush env-copy-files /destination' => 'Copy this site\'s files to /destination'],
		],
		'testenv-copy-drupaldb'   => [
			'arguments'   => [
				'destination' => 'Destination database',
				'type'        => 'Copy type: \'full\' or \'basic\' (default)',
			],
			'description' => 'Copy this site\'s Drupal database',
			'examples'    => ['drush testenv-copy-drupaldb newenv_drupal' => 'Copy current Drupal database to newenv_drupal'],
		],
		'testenv-copy-cividb'     => [
			'arguments'   => [
				'destination' => 'Destination database',
				'type'        => 'Copy type: \'full\' or \'basic\' (default)',
			],
			'description' => 'Copy this site\'s CiviCRM database',
			'examples'    => ['drush testenv-copy-cividb newenv_cividb' => 'Copy current CiviCRM database to newenv_cividb'],
		],
		'testenv-update-settings' => [
			'arguments'   => [
				'destination' => 'Destination directory',
			],
			'description' => 'Modifies Drupal / CiviCRM settings for a newly created environment',
			'examples'    => ['drush testenv-update-settings /destination' => 'Updates Drupal / CiviCRM settings for the new test environment copied to /destination'],
		],
		'testenv-fakedata'        => [
			'arguments'   => [
				'destination' => 'Destination directory',
				'count'       => 'Number of new fake contacts',
			],
			'description' => 'Adds fake contacts and Drupal users, using the Faker library.',
			'examples'    => ['drush testenv-fakedata /destination 1000' => 'Adds 1000 contacts to the environment installed at /destination'],
		],
	];

	/**
	 * Return Drush commands
	 * @return array Drush commands
	 */
	public static function getDrushCommands() {
		return self::$drushCommands;
	}
}
