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
  const DRUPAL_KEEP_USERS = '0,1,11,12,19,72,73,74,76,109,111';

  /**
   * @const string CIVI_KEEP_CONTACTS Keep all contacts with an id that is in this list, when copytype = basic.
   * Provisional solution that covers the SP installation for now.
   */
  const CIVI_KEEP_CONTACTS = '1,4,6,7,8,34252,37436,462535,762754,807085,807088';

  /**
   * @const string CIVI_KEEP_CONTACT_SUBTYPES Keep all contacts with a contact type that is in this list, when copytype = basic.
   * Provisional solution that covers the SP installation for now.
   */
  const CIVI_KEEP_CONTACT_SUBTYPES = "'SP_Afdeling','SP_Werkgroep','SP_Fractie','SP_Regio','SP_Provincie','SP_Landelijk'";

  /**
   * @const string ENV_BASE_URL Default base url for a test environment.
   */
  const ENV_BASE_URL = 'https://civicrmnew.sp.nl/';

  /**
   * @const string DEFAULT_THEME_PATH Path to default theme relative to Drupal root, without trailing slash.
   */
  const DEFAULT_THEME_PATH = '/sites/default/themes/spruit_spnet';

  /**
   * @const string CRON_USER Drupal user to run cron as (= spwebsite at the SP)
   */
  const CRON_USER = 'spwebsite';

  /**
   * @const string FAKER_LOCALE Faker locale (only tested with nl_NL)
   */
  const FAKER_LOCALE = 'nl_NL';

  /**
   * @var array Drush Commands
   * Syntax: see http://docs.drush.org/en/master/commands/#creating-custom-drush-commands
   */
  private static $drushCommands = [
    'testenv-new'             => [
      'description' => 'Create a new test environment that\'s based on the current installation interactively.',
      'examples'    => ['drush testenv-new' => 'Create a new test environment. You will be prompted for the destination directory, database credentials and other options. This command calls all the other testenv commands.'],
    ],
    'testenv-copy-files'      => [
      'arguments'   => [
        'destination' => 'Destination directory',
      ],
      'description' => 'Copy this site\'s files to a new testing environment',
      'examples'    => ['drush testenv-copy-files /destination' => 'Copy this site\'s files to /destination'],
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
      'description' => 'Modify Drupal / CiviCRM settings files and tables for a newly created environment',
      'examples'    => ['drush testenv-update-settings /destination' => 'Update Drupal / CiviCRM settings for the new test environment copied to /destination'],
    ],
    'testenv-finish-copy'     => [
      'arguments'   => [
        'destination' => 'Destination directory',
      ],
      'description' => 'Perform final actions after creating a new environment, clear all caches and run cron',
      'examples'    => ['drush testenv-finish-copy /destination' => 'Finish up creating a new environment at /destination'],
    ],
    'testenv-faker-create'    => [
      'arguments'   => [
        'destination' => 'Destination directory',
        'count'       => 'Number of new fake contacts',
      ],
      'description' => 'Add fake CiviCRM contacts and members, using the Faker library',
      'examples'    => ['drush testenv-faker-create /destination 1000' => 'Add 1000 contacts to the environment installed at /destination'],
    ],
    'testenv-faker-replace'   => [
      'arguments'   => [
        'destination' => 'Destination directory',
      ],
      'description' => 'Replace all sensitive CiviCRM contact data with random data, using the Faker library',
      'examples'    => ['drush testenv-faker-replace /destination' => 'Replace contact data with fake data for the environment installed at /destination'],
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
