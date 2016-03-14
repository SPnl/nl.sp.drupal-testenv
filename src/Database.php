<?php

namespace Testenv;

/**
 * Class Database
 * @package Testenv
 */
class Database {

	/**
	 * @var \PDO $conn PDO database connection for internal usage
	 */
	protected static $conn;

	/**
	 * Opens a PDO connection (ja - heb je daar problemen mee?) to execute queries directly.
	 * @param object $params Database credentials and parameters
	 * @param string $database Use this database
	 * @return \PDO PDO object
	 */
	public static function connection(&$params, $database = NULL) {

		if (empty(static::$conn)) {
			$dsn = 'mysql:host=' . Config::DB_HOST . ($database ? ';dbname=' . $database : '');
			static::$conn = new \PDO($dsn, $params->new_username, $params->new_password);
		}
		if ($database) {
			static::$conn->query('USE ' . $database)->execute();
		}

		return static::$conn;
	}

	/**
	 * Shared function to copy an entire database using mysqldump.
	 * @param string $cur_dbname Current database
	 * @param string $new_dbname Destination database
	 * @param object $params Database credentials and settings
	 * @return bool Result
	 */
	public static function copy($cur_dbname, $new_dbname, &$params) {

		if (!Util::isDrush()) {
			return Util::log('TESTENV: _sptestenv_copy_database can currently only be called through Drush.', 'error');
		}

		// Dump current database to system temp directory. Dump options currently hardcoded. Probably should be escaped.
		$dumpfile = Util::getTempDir() . DIRECTORY_SEPARATOR . 'sptestenv_copy_' . time() . '.sql';
		$dumpcmd  = Config::MYSQLDUMP_LOCATION . " -u {$params['cur_dbuser']} -p{$params['cur_dbpass']} -f --create-options --routines --triggers --skip-events --single-transaction --max-allowed-packet=32M {$cur_dbname} > {$dumpfile}";

		$dumpres = drush_shell_exec($dumpcmd, TRUE);
		if (!$dumpres || !file_exists($dumpfile)) {
			return Util::log("TESTENV: could not dump current database '{$cur_dbname}'.", 'error');
		}

		// Try to create new database, if it doesn't exist
		$dbconn = self::connection($params);
		$dbconn->query("CREATE DATABASE IF NOT EXISTS ?")->execute([1 => $new_dbname]);

		// Try to read dumpfile
		$readcmd = Config::MYSQL_LOCATION . "-D {$new_dbname} -u {$params['cur_dbuser']} -p{$params['cur_dbpass']} < {$dumpfile}";
		$readres = drush_shell_exec($readcmd, TRUE);

		// Remove dump file
		unlink($dumpfile);

		if (!$readres) {
			return Util::log("TESTENV: could not read dump file for database '{$new_dbname}'.", 'error');
		}

		return TRUE;
	}

	/**
	 * Get current Drupal and CiviCRM database names and credentials. Hostname is currently assumed to be localhost.
	 * @param object $params Referenced object that contains database credentials and settings
	 * @return bool Success
	 */
	public static function currentInfo(&$params) {

		if(empty($params)) {
			$params = new \StdClass;
		}

		global $databases;
		$conf   = array_shift($databases);
		$thisdb = array_shift($conf);
		if (empty($thisdb) || empty($thisdb['username']) || empty($thisdb['password']) || empty($thisdb['database']) || empty($thisdb['prefix']['civicrm_contact'])) {
			return FALSE;
		}

		$params->cur_username = $thisdb['username'];
		$params->cur_password = $thisdb['password'];
		$params->cur_drupal   = $thisdb['database'];
		$params->cur_civicrm  = $thisdb['prefix']['civicrm_contact'];

		return TRUE;
	}

}