<?php
namespace Testenv\Command;

use Testenv\Config;
use Testenv\Util;

/**
 * Class FinishCopy
 * @package Testenv\Command
 */
class FinishCopy extends Base {

  /**
   * @var FinishCopy $instance Command instance
   */
  protected static $instance;

  /**
   * Finish up creating a new environment: empty caches, clean up temporary files and execute cron.
   * @param string $destination Destination directory
   * @param object $params Database settings and credentials
   * @return mixed Result
   */
  public function run($destination, $params = null) {

    // Initialize Drush for remote installation
    chdir($destination);
    $dstsite = Util::getSiteRecord($destination);
    $drushret = drush_invoke_process($dstsite, 'status', [], ['--fields=drupal-version,bootstrap,db-name,db-status,root,site-path']);

    if (empty($drushret) || $drushret['error_status'] == 1) {
      return Util::log("Could not run Drush for the installation at '{$destination}'. Please try manually, fix any errors, and if necessary call 'drush testenv-finish-copy {$destination}' manually.", 'error');
    }

    Util::log("TESTENV: Drush is working for site '{$destination}'! Finishing up...", 'ok');

    // Try to change ownership of the files for $destination
    Util::log('TESTENV: Trying to change directory ownership to match the parent directory...', 'ok');
    $stat = stat(realpath($destination . '/../'));
    if ($stat) {
      drush_shell_exec("chown -R {$stat[4]}:{$stat[5]} {$destination}/");
    }

    // Call Drupal commands for new site
    Util::log('TESTENV: Clearing Drupal logs and caches.', 'ok');
    drush_invoke_process($dstsite, 'wd-del', ['all']);
    drush_invoke_process($dstsite, 'cache-clear', ['all']);

    // Call CiviCRM commands for new site
    Util::log('TESTENV: Updating CiviCRM config_backend.', 'ok');
    drush_invoke_process($dstsite, 'civicrm-update-cfg'); // Tries to update config_backend automatically

    Util::log('TESTENV: Clearing CiviCRM caches (system.flush).', 'ok');
    drush_invoke_process($dstsite, 'civicrm-api', ['system.flush'], ['-u ' . Config::CRON_USER, '-y'], FALSE); // API command to flush cache

    // Run cron and show status
    Util::log('TESTENV: Running Drupal and CiviCRM cron for your new environment.', 'ok');
    drush_invoke_process($dstsite, 'cron');
    drush_invoke_process($dstsite, 'civicrm-api', ['job.execute'], ['-u ' . Config::CRON_USER, '-y'], FALSE); // API command to run cron
    Util::log('TESTENV: Cron has run. Please note that the crontab currently is not automatically updated.');

    // Set permissions for files folder to 777 just in case (got errors on testing)
    drush_shell_exec("chmod -R 777 {$destination}/sites/default/files/");

    // Add fake data? (Only when called from CreateNew)
    if(isset($params->faker_count) && $params->faker_count > 0) {
        drush_invoke_process($dstsite, 'testenv-faker-data', [$params->faker_count], [], FALSE);
    }

    // We're done! At last!
    Util::log('TESTENV: Your new testing environment has been successfully created!', 'ok');
  }

  /**
   * Validate arguments
   * @param string $destination Destination directory
   * @return bool Is valid
   */
  public function validate($destination = '') {
    if (empty($destination) || !is_dir($destination)) {
      return drush_set_error('DEST_EMPTY', 'TESTENV: No or invalid destination directory specified.');
    }
  }


}