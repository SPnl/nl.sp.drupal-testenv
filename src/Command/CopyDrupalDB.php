<?php
namespace Testenv\Command;

use Testenv\Config;
use Testenv\Database;
use Testenv\Util;

/**
 * Class CopyDrupalDB
 * @package Testenv\Command
 */
class CopyDrupalDB extends Base {

	/**
	 * Copy this site's Drupal database.
	 * @param string $cur_dbname Current database
	 * @param string $new_dbname Destination database
	 * @param string $copytype Copy type: 'basic' or 'full'
	 * @param array|null $params Database credentials and settings
	 * @return mixed Result
	 */
	function run($cur_dbname, $new_dbname, $copytype = 'basic', &$params = NULL) {

		// If run directly instead of through CreateNew, we'll ask for credentials here:
		if (!isset($params->new_drupaldb)) {
			drush_print('Please enter valid database credentials for the NEW database.');
			$params->new_username = drush_prompt('Database username', $new_dbname);
			$params->new_password = drush_prompt('Database password', NULL, TRUE, TRUE);
		}

		// Copy database
		Database::currentInfo($params);
		if (!Database::copy($cur_dbname, $new_dbname, $params)) {
			return Util::log('TESTENV: copying Drupal database failed.', 'error');
		}

		// Clean up Drupal database
		$dbconn = Database::connection($params, $new_dbname);
		$dbconn->beginTransaction();

		try {
			// Truncate cache / session tables
			$dbconn->exec("TRUNCATE TABLE cache");
			$dbconn->exec("TRUNCATE TABLE sessions");
			$dbconn->exec("TRUNCATE TABLE watchlog");

			if ($copytype == 'basic') {
				// Remove non-admin users from system tables -> dit soort SP-specifieke config maken we later nog wel variabel

				// TODO hier mogen wat meer gebruikers bewaard blijven, bijv alle gebruikers die aan een CiviCRM-contact gekoppeld zijn.
				Util::log('TESTENV: Cleaning up Drupal db, and removing most user and profile records. TODO - keep more reserved users.', 'notice');

				$dbconn->exec("DELETE FROM users WHERE uid NOT IN (" . Config::DRUPAL_KEEP_USERS . ")");
				$dbconn->exec("DELETE FROM users_roles WHERE uid NOT IN (" . Config::DRUPAL_KEEP_USERS . ")");
				$dbconn->exec("DELETE FROM profile WHERE uid NOT IN (" . Config::DRUPAL_KEEP_USERS . ")");
				$dbconn->exec("DELETE FROM url_alias WHERE source LIKE 'user/%'");

				// Module tables
				if (module_exists('oauth2_server')) {
					$dbconn->exec("DELETE FROM oauth2_server_authorization_code WHERE uid NOT IN (" . Config::DRUPAL_KEEP_USERS . ")");
				}
				if (module_exists('webform')) {
					$dbconn->exec("TRUNCATE TABLE webform_submissions");
					$dbconn->exec("TRUNCATE TABLE webform_submitted_data");
				}
				if (module_exists('webform_civicrm')) {
					$dbconn->exec("TRUNCATE TABLE webform_civicrm_submissions");
				}

				if (module_exists('field')) {
					$tables = $dbconn->query("SHOW TABLES", \PDO::FETCH_COLUMN, 0);
					$tables->execute();
					foreach ($tables as $table) {
						if (strpos($table, 'field_data_') === 0) {
							$dbconn->query("DELETE FROM {$table} WHERE entity_type = 'user' AND entity_id > " . Config::DEL_USERS_ABOVE);
						}
					}
				}
			}

			Util::log('TESTENV: Committing clean up transaction for Drupal database.', 'ok');
			$dbconn->commit();

		} catch (\Exception $e) {
			$dbconn->rollBack();
			Util::log('An error occurred while cleaning up the new Drupal database. Rolling back. (' . $e->getMessage() . ')');

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Validate arguments
	 * @param string $destination Destination directory
	 * @param string $copytype Copy type
	 * @return bool Is valid
	 */
	public function validate($destination = '', $copytype = 'basic') {
		if (empty($destination)) {
			return drush_set_error('DB_EMPTY', 'TESTENV: No destination database specified.');
		}
		if (!empty($type) && !in_array($type, ['basic', 'full'])) {
			return drush_set_Error('DB_INVALIDTYPE', 'TESTENV: Invalid copy type, should be \'basic\' or \'full\'.');
		}
	}

}