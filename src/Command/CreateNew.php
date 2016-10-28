<?php
namespace Testenv\Command;

use Testenv\Config;
use Testenv\Database;
use Testenv\Util;

/**
 * Class CreateNew
 * @package Testenv\Command
 */
class CreateNew extends BaseCommand {

  /**
   * @var CreateNew $instance Command instance
   */
  protected static $instance;

  /**
   * Create a new test environment interactively from the command line.
   * This command first gathers data, then calls all the other commands in the correct order.
   * @return mixed Result
   */
  public function run() {

    // This command can only be called from the cli because we use drush_prompt (/ drush_choice / drush_confirm).
    if (!Util::isDrush()) {
      return Util::log('TESTENV: invalid call to sptestenv_new, command can only be called from Drush CLI.', 'error');
    }

    // Prompt user for parameters
    $params = $this->getParameters();
    chdir(DRUPAL_ROOT);

    // Check existing database credentials;
    if (!Database::currentInfo($params)) {
      return Util::log('TESTENV: could not get existing database credentials.', 'error');
    }

    // Copy files
    if (CopyFiles::get()->run($params->destination) === FALSE) {
      return FALSE;
    }

    // Copy Drupal database
    if (CopyDrupalDB::get()->run($params->new_drupaldb, $params->copytype, $params) === FALSE) {
      return FALSE;
    }

    // Copy CiviCRM database
    if (CopyCiviDB::get()->run($params->new_cividb, $params->copytype, $params) === FALSE) {
      return FALSE;
    }

    // Update settings files
    if (UpdateSettings::get()->run($params->destination, $params) === FALSE) {
      return FALSE;
    }

    // Finish up
    if (FinishCopy::get()->run($params->destination, $params) === FALSE) {
      return FALSE;
    }

    return Util::log('TESTENV-CREATE: finished creating test environment!');
  }

  /**
   * Function to get parameters interactively. We'll do them in one go at the start,
   * so the rest should be able to run without user intervention.
   * @return object|false Parameters object, or false on error
   */
  public function getParameters() {

    $params = new \stdClass;

    drush_print("TESTENV: Welcome to the SP Test Environments script. We're about to create a new test environment.\nThis script does not change the web server configuration. If necessary, please add a virtual host or alias first so the destination directory is mapped to a URL.\n");

    // Ask for destination path
    $params->destination = drush_prompt('Copy files to directory (enter full path)');
    if (empty($params->destination)) {
      return Util::log('TESTENV: you did not enter a directory path.', 'error');
    } elseif (file_exists($params->destination) && !is_dir($params->destination)) {
      return Util::log('TESTENV: ' . $params->destination . ' is not a directory.', 'error');
    } elseif (file_exists($params->destination) && !is_writable($params->destination)) {
      return Util::log('TESTENV: directory ' . $params->destination . ' is not writable.', 'error');
    } elseif (!file_exists($params->destination) && !mkdir($params->destination, 0777, TRUE)) {
      return Util::log('TESTENV: could not create directory ' . $params->destination . '.', 'error');
    }

    // Site name
    $params->sitename = drush_prompt('Environment name (will be used as site name and in content)', ucfirst(basename($params->destination)));
    if (empty($params->sitename)) {
      return Util::log('TESTENV: you did not enter a valid environment name.', 'error');
    }

    // Website URL
    $params->url = drush_prompt('Website URL (must be accessible to this script, no trailing slash!)', Config::ENV_BASE_URL . basename($params->destination));
    if (strrpos($params->url, '/') === 1) {
      return Util::log('TESTENV: your URL ends with a trailing slash. I could of course remove it myself, but I can\'t possibly condone such defiance. Exiting.', 'error');
    } elseif (empty($params->url) || filter_var($params->url, FILTER_VALIDATE_URL) === FALSE) {
      return Util::log('TESTENV: you did not enter a valid URL.', 'error');
    }

    // Ask for database credentials
    drush_print("\nYou'll now be asked for database names and credentials for the NEW databases.\nThe username and password you enter must already exist. If the destination databases don't exist yet, this user must have the CREATE privilege. The user may temporarily need the SUPER privilege to copy CiviCRM procedures and triggers.\n");
    $params->new_drupaldb = drush_prompt('Destination DRUPAL database name', basename($params->destination) . '_drupal');
    $params->new_cividb = drush_prompt('Destination CIVICRM database name', basename($params->destination) . '_civicrm');
    $params->new_username = drush_prompt('Database username', basename($params->destination));
    $params->new_password = drush_prompt('Database password', NULL, TRUE, TRUE);
    $params->copytype = drush_choice([
      'full'    => 'Full (including all data)',
      'basic'   => 'Basic (users and contact data are removed)',
      'replace' => 'Replace (contact data is replaced by random data)',
    ], 'Copy type');

    // Ask about Faker data
    $params->faker_count = 0;
    if ($params->copytype == 'basic') {
      drush_print("After the test environment has been created, this script can automatically create fake sample data in your locale.\nType the number of fake contact records you wish to create, or '0' to add no fake data.");
      $params->faker_count = drush_prompt('Number of fake contacts to create', 0, FALSE, FALSE);
      if (!is_numeric($params->faker_count)) {
        return Util::log('TESTENV: faker count is not a number (' . $params->faker_count . ').', 'error');
      }
    }

    drush_print("\n");
    return $params;
  }

  public function validate() {
    return TRUE;
  }
}