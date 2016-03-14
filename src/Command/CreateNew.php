<?php
namespace Testenv\Command;

use Testenv\Config;
use Testenv\Database;
use Testenv\Util;

/**
 * Class CreateNew
 * @package Testenv\Command
 */
class CreateNew extends Base {

	/**
	 * Create a new test environment interactively from the command line.
	 * This command first gathers data, then calls all the other commands in the correct order.
	 * @return mixed Result
	 */
	public function run() {

		// This command can only be called from the cli because we use drush_prompt (/ drush_choice / drush_confirm).s
		if (!Util::isDrush()) {
			return Util::log('TESTENV: invalid call to sptestenv_new, command can only be called from Drush CLI.', 'error');
		}

		// Prompt user for parameters
		$params = $this->getParameters();

		// Check existing database credentials;
		if (!Database::currentInfo($params)) {
			return Util::log('TESTENV: could not find existing database credentials.', 'error');
		}

		// Copy files
		if (CopyFiles::get()->run($params->destination) === FALSE) {
			return FALSE;
		}

		// Copy Drupal database
		if (CopyDrupalDB::get()->run($params->cur_drupaldb, $params->new_drupaldb, $params->copytype, $params) === FALSE) {
			return FALSE;
		}

		// Copy CiviCRM database
		if (CopyCiviDB::get()->run($params->cur_cividb, $params->new_cividb, $params->copytype, $params) === FALSE) {
			return FALSE;
		}

		// Update settings files
		if (UpdateSettings::get()->run($params->destination, $params) === FALSE) {
			return FALSE;
		}

		// Add fake data
		if (FakerData::get()->run($params->destination, $params->count) === FALSE) {
			return FALSE;
		}

		return Util::log('TESTENV-CREATE: finished creating test environment!');
	}

	/**
	 * Function to get parameters interactively. We'll do them in one go at the start,
	 * so the rest should be able to run without user intervention.
	 * @return array|false Result or error
	 */
	public function getParameters() {

		$params = new \StdClass;

		drush_print('TESTENV: Welcome to the SP Test Environments script.');
		drush_print('TESTENV: We\'re about to create a new test environment. - This script does not change the web server configuration. If necessary, please add a virtual host or alias first so the destination directory is mapped to an URL.');

		// Ask for destination path
		$params->destination = drush_prompt('Copy files to directory (enter full path)');
		if (empty($params->destination)) {
			return Util::log('TESTENV: you did not enter a directory path.', 'error');
		} elseif (file_exists($params->destination) && !is_dir($params->destination)) {
			return Util::log('TESTENV: ' . $params->destination . ' is not a directory.', 'error');
		} elseif (file_exists($params->destination) && !is_writable($params->destination)) {
			return Util::log('TESTENV: directory ' . $params->destination . ' is not writable.', 'error');
		} elseif (!mkdir($params->destination, 0777, TRUE)) {
			return Util::log('TESTENV: could not create directory ' . $params->destination . '.', 'error');
		}

		// Website URL
		$params->url = drush_prompt('Website URL (must be accessible to this script, no trailing slash!)', 'https://' . basename($params->destination) . Config::HOST_SUFFIX);
		if (empty($params->url) || filter_var($params->url, FILTER_VALIDATE_URL) === false) {
			return Util::log('TESTENV: you did not enter a valid URL.', 'error');
		}

		// Ask for database credentials
		drush_print('You\'ll now be asked for database names and credentials for the NEW databases.');
		$params->new_drupaldb = drush_prompt('Destination DRUPAL database name', basename($params->destination) . '_drupal');
		$params->new_cividb   = drush_prompt('Destination CIVICRM database name', basename($params->destination) . '_civicrm');
		$params->new_username = drush_prompt('Database username', basename($params->destination));
		$params->new_password = drush_prompt('Database password', null, true, true);
		$params->copytype     = drush_choice([
			'Full (including all data)'                  => 'full',
			'Basic (users and contact data are removed)' => 'basic',
		], 'Copy type');

		// Ask about Faker data
		drush_print('After the test environment has been created, this script can automatically create fake sample data in your locale. Type \'0\' to create no records, or a higher number - e.g. \'1000\' - to create 1000 fake contact records.');
		$params->faker_count = drush_prompt('Nunmber of fake contacts to create', 0);
		if(!is_numeric($params->faker_count)) {
			return Util::log('TESTENV: faker count is not a number (' . $params->faker_count . ').');
		}

		return $params;
	}
}