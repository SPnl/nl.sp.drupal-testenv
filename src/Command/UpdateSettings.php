<?php
namespace Testenv\Command;

use Testenv\Util;

/**
 * Class CopyFiles
 * @package Testenv\Command
 */
class UpdateSettings extends Base {

  /**
   * @param string $destination Destination directory
   * @param object|null $params Database credentials and settings
   * @return mixed Result
   */
  public function run($destination, $params = NULL) {

    // If run directly instead of through CreateNew, we'll ask for credentials here:
    if (!isset($params->new_drupaldb) || !isset($params->new_civicrmdb)) {
      drush_print('Please enter valid database credentials for the NEW databases.');
      $params->new_drupaldb = drush_prompt('Drupal database name', basename($destination) . '_drupal');
      $params->new_civicrmdb = drush_prompt('CiviCRM database name', basename($destination) . '_civicrm');
      $params->new_username = drush_prompt('Database username', $params->new_drupaldb);
      $params->new_password = drush_prompt('Database password', NULL, TRUE, TRUE);
      $params->url = drush_prompt('Website URL (must be accessible to this script, no trailing slash!)', 'https://' . basename($params->destination) . Config::HOST_SUFFIX);
    }

    // Write new Drupal settings file
    $drupalPath = $destination . '/sites/default/settings.php';
    $update = $this->updateDrupalSettingsFile($drupalPath, $params);
    if($update === false) {
      return Util::log('TESTENV: could not write Drupal settings to ' . $drupalPath, 'error');
    }

    // Write new CiviCRM settings file
    $civiPath = $destination . '/sites/default/civicrm.settings.php';
    $update = $this->updateCiviSettingsFile($civiPath, $params);
    if($update === false) {
      return Util::log('TESTENV: could not write CiviCRM settings to ' . $civiPath, 'error');
    }

    // TODO ADD FURTHER PROCESSING + SPLIT TO SEPARATE FUNCTIONS / COMMANDS

    // Initialize Drush for remote installation
    $dstsite = Util::getSiteRecord($destination);

    // Update Drupal database settings
    Util::log('TESTENV: Updating Drupal settings.', 'ok');
    // TODO
    // drush vset site_name Test123
    // UPDATE menu_links SET link_title = 'Bla' WHERE link_path = ´<front>'

    // Update CiviCRM database settings
    Util::log('TESTENV: Updating CiviCRM settings.', 'ok');
    drush_invoke_process($dstsite, 'civicrm-update-cfg');
    // Bovenstaande eerst draaien, past zoveel mogelijk paden aan. Checken of civicrm_domain dan ook goed komt.
    // Deze settings zouden overrideable moeten zijn in de config! Kijken of dat werkt, dat is dan een universele makkelijke oplossing met __DIR__ oid. debug_enabled, uploadDir, imageUploadDir, customFileUploadDir, customTemplateDir, customPHPPathDir, extensionsDir, userFrameworkResourceURL, imageUploadURL, customCSSURL, extensionsURL
    // Mja, maar die zijn ook gewoon in civicrm_setting aan te passen. Serialized strings.
    // Verder org.civicoop.odoosync -> url, databasename, username, password, view_partner_url voor de zekerheid weghalen.
    // Evenals nl.sp.memoriamigration -> memoriamigr_dbhost/dbuser/dbpass/dbname
    // TODO hier juiste content, site-naam, users, etc instellen. testaccount erbij toevoegen?

    // Call Drush functions on new site: clear caches and logs, and run cron.
    Util::log('TESTENV: Clearing Drupal logs and caches.', 'ok');
    drush_invoke_process($dstsite, 'wd-del', 'all');
    drush_invoke_process($dstsite, 'cache-clear', 'all');

    // Run cron and show status
    Util::log('TESTENV: Running cron for your new environment.', 'ok');
    drush_invoke_process($dstsite, 'cron');
    drush_invoke_process($dstsite, 'civicrm-api', ['job.execute'], ['-u spwebsite', '-y']);

    // Show status and return
    Util::log('TESTENV: Finished updating settings.', 'ok');

    $status = drush_invoke_process($dstsite, 'status');

    return Util::log('TESTENV: Status information for your new environment: ' . $status);
  }

  /**
   * Validate arguments
   * @param string $destination Destination directory
   * @param array $params Database credentials and settings
   * @return bool Is valid
   */
  public function validate($destination = '', $params = NULL) {
    if (empty($destination) || !is_dir($destination)) {
      return drush_set_error('DB_EMPTY', 'TESTENV: No or invalid destination database specified.');
    }
  }

  /**
   * Change Drupal settings file. Assumes the current configuration is valid and complete.
   * (If anyone knows a better way, please contribute!)
   * @param string $path Destination settings.php path
   * @param object $params Database settings
   * @return int Success
   */
  private function updateDrupalSettingsFile($path, $params) {

    Util::log('TESTENV: Writing new Drupal settings.php...', 'ok');

    // Change Drupal settings file. Assumes the current configuration is valid and complete.
    // (If anyone knows a better way, please contribute!)
    $settingsFile = file_get_contents($path);
    $settingsFile = preg_replace(['/\$databases = \[(.*)\];/imU', '/\$databases = array\((.*)\);/imU'], "\$databases = [
      'default'    => 'default' => [
        'database' => '{$params->new_drupaldb}',
        'username' => '{$params->new_username}',
        'password' => '{$params->new_password}',
        'host'     => 'localhost',
        'driver'   => 'mysql',
      ], ],
    ];", $settingsFile);
    $settingsFile = preg_replace('/\$drupal_hash_salt = [\'"](.*)[\'"];/imU', '$drupal_hash_salt = "' . mcrypt_create_iv(40) . '"', $settingsFile);
    $settingsFile = preg_replace('/\$base_url = [\'"](.*)[\'"];/imU', '$base_url = "' . $params->url . '""', $settingsFile);

    if(!empty($params->old_civicrmdb)) {
      // UF integration - CiviCRM database name may occur in Drupal settings.php
      $settingsFile = str_replace("`{$params->old_civicrmdb}`", "`{$params->new_civicrmdb}`", $settingsFile);
    }

    return file_put_contents($path, $settingsFile);
  }

  /**
   * Change CiviCRM settings file. Assumes the current configuration is valid and complete.
   * (If anyone knows a better way, please contribute!)
   * @param string $path Destination civicrm.settings.php path
   * @param object $params Database settings
   * @return int Success
   */
  private function updateCiviSettingsFile($path, $params) {

    Util::log('TESTENV: Writing new CiviCRM settings.php...', 'ok');

    $settingsFile = file_get_contents($path);

    // TODO ADD THESE SETTINGS:
    // CIVICRM_UF_DSN
    // CIVICRM_DSN
    // $civicrm_root
    // CIVICRM_TEMPLATE_COMPILEDIR
    // CIVICRM_UF_BASEURL
    // CIVICRM_SITE_KEY
    // Then

    return file_put_contents($path, $settingsFile);
  }

}