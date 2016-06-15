<?php
namespace Testenv\Command;

use Testenv\Util;

/**
 * Class CopyFiles
 * @package Testenv\Command
 */
class CopyFiles extends BaseCommand {

  /**
   * @var CopyFiles $instance Command instance
   */
  protected static $instance;

  /**
   * Copy this site's files to a new testing environment.
   * @param string $destination Destination directory
   * @return mixed Result
   */
  public function run($destination) {

    // Call drush core-rsync to perform the actual sync... can't get this to work with drush_invoke_process, so doing this instead for now
    chdir(DRUPAL_ROOT);
    $drushret = drush_shell_exec_interactive('drush -y rsync @self ' . $destination . ' --include-conf --exclude-paths="sites/default/files/civicrm/templates_c/:sites/default/files/civicrm/ConfigAndLog/*:sites/default/files/css/*:sites/default/files/js/*:"');

    if (empty($drushret) || $drushret['error_status'] == 1) {
      return Util::log("TESTENV: drush rsync failed.", 'error');
    }

    return Util::log("TESTENV: finished copying files.", 'ok');
  }

  /**
   * Command arguments validation
   * @param string $destination Destination directory
   * @return bool Is valid
   */
  public function validate($destination = '') {
    if (empty($destination) || !is_dir($destination)) {
      return drush_set_error('DIR_INVALID', 'TESTENV: No valid destination directory specified.');
    }
    if (!is_writable($destination)) {
      return drush_set_error('DIR_NOWRITE', 'TESTENV: Destination directory isn\'t writable.');
    }
  }

}