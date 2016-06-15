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
    if (empty(self::$conn[ $keyname ])) {
      $dsn = 'mysql:host=' . Config::DB_HOST . ($database ? ';dbname=' . $database : '');
      self::$conn[ $keyname ] = new \PDO($dsn, $username, $password);
    }

    return self::$conn[ $keyname ];
  }

  /**
   * Shared function to copy an entire database by calling mysqldump / mysql via drush_shell_exec.
   * @param string $cur_dbname Current database
   * @param string $new_dbname Destination database
   * @param object $params Database credentials and settings
   * @return bool Result
   */
  public static function copy($cur_dbname, $new_dbname, &$params) {

    if (!Util::isDrush()) {
      return Util::log('TESTENV: Database::copy can currently only be called via Drush.', 'error');
    }

    // Clean up any SQL files that may have been left in /tmp
    drush_op_system("rm -f /tmp/sptestenv_copy_*.sql");

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
      return Util::log("TESTENV: could not dump current database '{$cur_dbname}'.", 'error');
    }

    // Try to create new database, if it doesn't exist
    $dbconn = self::connection($params->new_username, $params->new_password);
    $query = $dbconn->prepare("CREATE DATABASE IF NOT EXISTS :database")->execute(['database' => $new_dbname]);

    // Try to read dumpfile
    $readcmd = Config::MYSQL_LOCATION . " -D {$new_dbname} -u {$params->new_username} -p{$params->new_password} -f < {$dumpfile}";

    Util::log("Importing database '{$new_dbname}' from {$dumpfile}...", 'ok');
    Util::log("Calling command: '{$readcmd}'.\n", 'debug'); // debug only

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

    if (empty($params) || !is_object($params)) {
      $params = new \stdClass;
    }

    global $databases;
    $conf = reset($databases);
    $thisdb = reset($conf);
    if (empty($thisdb) || empty($thisdb['username']) || empty($thisdb['password']) || empty($thisdb['database']) || empty($thisdb['prefix']['civicrm_contact'])) {
      return FALSE;
    }

    $params->cur_username = $thisdb['username'];
    $params->cur_password = $thisdb['password'];
    $params->cur_drupaldb = $thisdb['database'];
    $params->cur_cividb = preg_replace('/`(.*)`./imU', "$1", $thisdb['prefix']['civicrm_contact']);

    return TRUE;
  }

}