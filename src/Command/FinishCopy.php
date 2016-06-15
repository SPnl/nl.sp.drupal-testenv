<?php
namespace Testenv\Command;

use Testenv\Config;
use Testenv\Util;

/**
 * Class FinishCopy
 * @package Testenv\Command
 */
class FinishCopy extends BaseCommand {

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
  public function run($destination, $params = NULL) {

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
    drush_invoke_process($dstsite, 'civicrm-update-cfg'); // Tries to update config_backend automatically - this is probably implemented better in 4.6 / 4.7

    Util::log('TESTENV: Clearing CiviCRM caches (system.flush).', 'ok');
    drush_invoke_process($dstsite, 'civicrm-api', ['system.flush'], ['-u ' . Config::CRON_USER, '-y'], FALSE); // API command to flush cache

    // Run cron and show status
    Util::log('TESTENV: Running Drupal and CiviCRM cron for your new environment.', 'ok');
    drush_invoke_process($dstsite, 'cron');
    drush_invoke_process($dstsite, 'civicrm-api', ['job.execute'], ['-u ' . Config::CRON_USER, '-y'], FALSE); // API command to run cron
    Util::log('TESTENV: Cron has run. Note that you currently have to add crontab entries for the new site yourself!', 'ok');

    // Set permissions for files folder to 777 just in case (got errors on testing)
    Util::log('TESTENV: Setting permissions on /files/...', 'ok');
    drush_shell_exec("chmod -R 777 {$destination}/sites/default/files/");

    // Add fake data? (Only when called from CreateNew)
    if ($params->copytype == 'replace' || $params->faker_count > 0) {
      $method_name = ($params->copytype == 'replace' ? 'testenv-faker-replace' : 'testenv-faker-create');
      Util::log('TESTENV: Running process ' . $method_name . ' in destination environment...', 'ok');

      drush_invoke_process($dstsite, $method_name, [$params->faker_count], [], ['interactive']);
      // drush_invoke_process in interactive mode seems to work well, an alternative would be:
      // drush_shell_exec_interactive("drush -y -r \"{$destination}\" {$method_name} {$params->faker_count}");
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