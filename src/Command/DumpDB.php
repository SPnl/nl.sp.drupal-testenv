<?php
namespace Testenv\Command;

use Testenv\Database;
use Testenv\Util;

/**
 * Class DumpDB
 * @package Testenv\Command
 */
class DumpDB extends BaseCommand {

  /**
   * @var DumpDB $instance Command instance
   */
  protected static $instance;

  /**
   * Dump Drupal AND CiviCRM databases to .sql files. This is a new separate command for easy server migration.
   * Options passed to mysqldump are optimized for the quickest possible export/import.
   * The dumpfiles are passed through sed to remove definer/trigger SQL that causes import errors.
   * @param string $dumppath Path to store dump files
   * @return bool Result
   */
  function run($dumppath) {

    // Get current database credentials and absolute dump destination path
    $params = new \stdClass;
    Database::currentInfo($params);
    $dumppath = realpath($dumppath);

    // Dump both Drupal and CiviCRM database
    foreach ([$params->cur_drupaldb, $params->cur_cividb] as $dbname) {

      // Run MySQL dump command
      $dumpfile = Database::dump($dbname, $params);
      if (!$dumpfile) {
        Util::log("TESTENV: dumping database '{$dbname}' failed.", 'error');
        return FALSE;
      }

      // Move to $dumppath destination directory
      $newpath = $dumppath . '/db_' . $dbname . '_' . date('Ymd') . '.sql';
      if (rename($dumpfile, $newpath)) {
        Util::log("TESTENV: finished dumping database '{$dbname}'. Dump file location: '{$newpath}'.", 'ok');
      } else {
        Util::log("TESTENV: finished dumping database '{$dbname}'. Could not move dump file - move or remove it manually.", 'warning');
      }
    }

    return TRUE;
  }

  /**
   * Validate arguments
   * @param string $dumppath Path to store dump files
   * @return bool Is valid
   */
  public function validate($dumppath = NULL) {
    if (!file_exists($dumppath) || !is_dir($dumppath)) {
      return drush_set_error('PATH_INVALID', 'TESTENV: Pass a valid, existing path to store the dump files.');
    }
  }

}