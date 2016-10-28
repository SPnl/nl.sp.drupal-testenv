<?php

namespace Testenv;

/**
 * Class Database
 * @package Testenv
 */
class Database {

  /**
   * @var array $conn Array of PDO database connections for internal usage
   */
  protected static $conn = [];

  /**
   * Opens a PDO connection (ja, PDO, problemen mee?) to execute queries directly.
   * @param string $username MySQL username
   * @param string $password MySQL password
   * @param string $database Database name
   * @return \PDO PDO object
   */
  public static function connection($username, $password, $database = NULL) {

    $keyname = !empty($database) ? $database : 'general';
    if (empty(self::$conn[$keyname])) {
      $dsn = 'mysql:host=' . Config::DB_HOST . ($database ? ';dbname=' . $database : '');
      self::$conn[$keyname] = new \PDO($dsn, $username, $password);
    }

    return self::$conn[$keyname];
  }

  /**
   * Shared function to dump an entire database by calling mysqldump via drush_shell_exec.
   * Copy function split up into dump and import function to allow for dumping databases only.
   * @param string $cur_dbname Current database
   * @param object $params Database credentials and settings
   * @return string|bool Dumpfile name, or false
   */
  public static function dump($cur_dbname, &$params) {

    if (!Util::isDrush()) {
      return Util::log('TESTENV: Database::dump can currently only be called via Drush.', 'error');
    }

    // Clean up any SQL files that may have been left in /tmp
    drush_op_system("rm -f " . Util::getTempDir() . DIRECTORY_SEPARATOR . "sptestenv_copy_*.sql");

    // Dump current database to system temp directory. Dump options are currently hardcoded. The sed command fixes definer permission issues and adds SQL to speed up dump import.
    $dumpfile = Util::getTempDir() . DIRECTORY_SEPARATOR . 'sptestenv_copy_' . time() . '.sql';
    $mysqldumpopts = "-u {$params->cur_username} -p{$params->cur_password} -f --create-options --add-drop-database --add-drop-table --routines --triggers --skip-events --single-transaction --max-allowed-packet=64M {$cur_dbname}";

    $dumpcmd_before = "/* Added by import script: */ SET FOREIGN_KEY_CHECKS = 0; SET UNIQUE_CHECKS = 0; SET AUTOCOMMIT = 0;";
    $dumpcmd_after = "/* Added by import script: */ SET FOREIGN_KEY_CHECKS = 1; SET UNIQUE_CHECKS = 1; SET AUTOCOMMIT = 1; COMMIT;";
    $sedcmd = "sed -e 's/DEFINER[ ]*=[]*[^*]*\\*/\\*/' -e '1i {$dumpcmd_before}' -e '\$a {$dumpcmd_after}'";

    // This is the actual command
    $dumpcmd = Config::MYSQLDUMP_LOCATION . " " . $mysqldumpopts . " | " . $sedcmd . " > " . $dumpfile;

    Util::log("Dumping database '{$cur_dbname}' to {$dumpfile}...", 'ok');
    Util::log("Calling command: '{$dumpcmd}'.\n", 'debug'); // debug only

    $dumpres = drush_shell_exec($dumpcmd, TRUE);
    if (!$dumpres || !file_exists($dumpfile)) {
      Util::log("TESTENV: could not dump current database '{$cur_dbname}'.", 'error');
      return FALSE;
    }

    return $dumpfile;
  }

  /**
   * Shared function to import a database dump by calling mysql via drush_shell_exec.
   * Copy function split up into dump and import function to allow for dumping databases only.
   * @param string $new_dbname Destination database
   * @param string $dumpfile Dumpfile name
   * @param object $params Database credentials and settings
   * @return bool Success
   */
  public static function import($new_dbname, $dumpfile, &$params) {

    if (!Util::isDrush()) {
      return Util::log('TESTENV: Database::import can currently only be called via Drush.', 'error');
    }

    // Try to create new database, if it doesn't exist (only possible when root credentials are supplied)
    $dbconn = self::connection($params->new_username, $params->new_password);
    $dbconn->prepare("CREATE DATABASE IF NOT EXISTS :database")->execute(['database' => $new_dbname]);

    // Execute dump file
    $readres = self::runSQLFile($new_dbname, $params->new_username, $params->new_password, $dumpfile);
    unlink($dumpfile);

    if (!$readres) {
      Util::log("TESTENV: could not read dump file for database '{$new_dbname}' ({$dumpfile}).", 'error');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Import an SQL dump file with the credentials provided
   * @param string $dbname Database name
   * @param string $username Username
   * @param string $password Password
   * @param string $dumpfile Dumpfile location
   * @return bool Success
   */
  public static function runSQLFile($dbname, $username, $password, $dumpfile) {
    if (empty($dumpfile)) {
      return FALSE;
    }

    // Try to import dumpfile
    $readcmd = Config::MYSQL_LOCATION . " -D {$dbname} -u {$username} -p{$password} -f < {$dumpfile}";

    Util::log("Importing database '{$dbname}' from {$dumpfile}...", 'ok');
    Util::log("Calling command: '{$readcmd}'.\n", 'debug'); // debug only

    return drush_shell_exec($readcmd, TRUE);
  }

  /**
   * Get current Drupal and CiviCRM database names and credentials. Hostname is currently assumed to be localhost.
   * @param object $params Referenced object that contains database credentials and settings
   * @return bool Success
   */
  public static function currentInfo(&$params) {

    if (empty($params) || !is_object($params)) {
      $params = new \stdClass;
    }

    global $databases;
    $conf = reset($databases);
    $thisdb = reset($conf);
    if (empty($thisdb) || empty($thisdb['username']) || empty($thisdb['password']) || empty($thisdb['database']) || empty($thisdb['prefix']['civicrm_contact'])) {
      Util::log('Error: could not get database credentials or CiviCRM tables prefix. Check if both are defined in settings.php.', 'error');
      return FALSE;
    }

    $params->cur_username = $thisdb['username'];
    $params->cur_password = $thisdb['password'];
    $params->cur_drupaldb = $thisdb['database'];
    $params->cur_cividb = preg_replace('/`(.*)`./imU', "$1", $thisdb['prefix']['civicrm_contact']);

    return TRUE;
  }

}