<?php
namespace Testenv\Command;

use Testenv\Config;
use Testenv\Database;
use Testenv\Util;

/**
 * Class CopyFiles
 * @package Testenv\Command
 */
class UpdateSettings extends Base {

  /**
   * @var UpdateSettings $instance Command instance
   */
  protected static $instance;

  /**
   * Update settings in Drupal and CiviCRM configuration files and databases.
   * @param string $destination Destination directory
   * @param object|null $params Database credentials and settings
   * @return mixed Result
   */
  public function run($destination, $params = NULL) {

    // If run directly instead of through CreateNew, we'll ask for credentials here:
    if ($params === NULL) {
      $params = new \StdClass;
    }
    if (!isset($params->new_drupaldb) || !isset($params->new_cividb)) {
      drush_print('Please enter valid database credentials for the NEW Drupal and CiviCRM databases.');
      $params->new_drupaldb = drush_prompt('Drupal database name', basename($destination) . '_drupal');
      $params->new_cividb = drush_prompt('CiviCRM database name', basename($destination) . '_civicrm');
      $params->new_username = drush_prompt('Database username', basename($destination));
      $params->new_password = drush_prompt('Database password', NULL, TRUE, TRUE);

      $params->sitename = drush_prompt('Environment name (will be used as site name and in content)', ucfirst(basename($destination)));
      $params->url = drush_prompt('Website URL (must be accessible to this script, no trailing slash!)', Config::ENV_BASE_URL . basename($destination));
      if (strrpos($params->url, '/') === 1) {
        return Util::log('TESTENV: I said - no trailing slash! Why will you never listen to me?', 'error');
      }
    }

    // Write new Drupal settings file
    $dfupdate = $this->updateDrupalSettingsFile($destination, $params);
    if ($dfupdate === FALSE) {
      return Util::log('TESTENV: could not write Drupal settings to ' . $destination . '/sites/default/settings.php', 'error');
    }

    // Write new CiviCRM settings file
    $cfupdate = $this->updateCiviSettingsFile($destination, $params);
    if ($cfupdate === FALSE) {
      return Util::log('TESTENV: could not write CiviCRM settings to ' . $destination . '/sites/default/civicrm.settings.php', 'error');
    }

    // Update Drupal database settings
    $ddupdate = $this->updateDrupalDbSettings($destination, $params);
    if ($ddupdate === FALSE) {
      return Util::log('TESTENV: could not update Drupal settings in database ' . $params->new_drupaldb, 'error');
    }

    // Update Drupal database settings
    $cdupdate = $this->updateCiviDbSettings($destination, $params);
    if ($cdupdate === FALSE) {
      return Util::log('TESTENV: could not update Drupal settings in database ' . $params->new_cividb, 'error');
    }

    return Util::log('TESTENV: finished updating settings files and tables.', 'ok');
  }

  /**
   * Validate arguments
   * @param string $destination Destination directory
   * @param array $params Database credentials and settings
   * @return bool Is valid
   */
  public function validate($destination = '', $params = NULL) {
    if (empty($destination) || !is_dir($destination)) {
      return drush_set_error('DEST_EMPTY', 'TESTENV: No or invalid destination directory specified.');
    }
  }

  /**
   * Change Drupal settings file. Assumes the current configuration is valid and complete.
   * (If anyone knows a better way, please contribute!)
   * @param string $path Destination directory
   * @param object $params Database settings
   * @return int Success
   */
  private function updateDrupalSettingsFile($path, $params) {

    // Update logo (SP specific... -> houd de bestandsnaam aub op spnet_logo.png, dan heeft hij altijd de goede)
    Util::log('TESTENV: Overwriting files/spnet_logo.png...', 'ok');
    $logo_filename = (strpos($path, 'accept') !== false ? 'spnet_accept.png' : 'spnet_test.png');
    if(file_exists($path . '/sites/default/files/spnet_logo.png')) {
      $destpath = $path . '/sites/default/files/spnet_logo.png';
    } elseif(file_exists($path . '/sites/default/files/spnet_logo_0.png')) {
      $destpath = $path . '/sites/default/files/spnet_logo_0.png';
    }
    copy(DRUPAL_ROOT . '/' . drupal_get_path('module', 'sptestenv') . '/assets/' . $logo_filename, $destpath);

    Util::log('TESTENV: Writing new Drupal settings.php...', 'ok');
    // Change Drupal settings file. Assumes the current configuration is valid and complete.
    // (If anyone knows a better way, please contribute!)
    $settingsPath = $path . '/sites/default/settings.php';
    $settingsFile = file_get_contents($settingsPath);

    $settingsFile = preg_replace('/^\$databases(.*);/imsU', "\$databases = [
      'default' => [ 'default' => [
        'database' => '{$params->new_drupaldb}',
        'username' => '{$params->new_username}',
        'password' => '{$params->new_password}',
        'host'     => 'localhost',
        'driver'   => 'mysql',
      ], ], ];", $settingsFile);
    $settingsFile = preg_replace('/^\$drupal_hash_salt = [\'"](.*)[\'"];/imsU', "\$drupal_hash_salt = '" . bin2hex(openssl_random_pseudo_bytes(32)) . "';", $settingsFile);
    $settingsFile = preg_replace('/^\$base_url = [\'"](.*)[\'"];/imsU', "\$base_url = '{$params->url}';", $settingsFile);

    if (!empty($params->old_civicrmdb)) {
      // UF integration - CiviCRM database name may occur in Drupal settings.php
      $settingsFile = str_replace("`{$params->old_civicrmdb}`", "`{$params->new_cividb}`", $settingsFile);
    }

    return file_put_contents($settingsPath, $settingsFile);
  }

  /**
   * Change CiviCRM settings file. Assumes the current configuration is valid and complete.
   * (If anyone knows a better way, please contribute!)
   * @param string $path Destination directory
   * @param object $params Database settings
   * @return int Success
   */
  private function updateCiviSettingsFile($path, $params) {

    Util::log('TESTENV: Writing new CiviCRM settings.php...', 'ok');

    $settingsPath = $path . '/sites/default/civicrm.settings.php';
    $settingsFile = file_get_contents($settingsPath);

    $settingsFile = preg_replace('/^define\([\s\t]*\'CIVICRM_UF_DSN\'[\s\t]*,[\s\t]*\'(.*)\'[\s\t]*\);/imsU', "define ('CIVICRM_UF_DSN', 'mysql://{$params->new_username}:{$params->new_password}@localhost/{$params->new_drupaldb}?new_link=true');", $settingsFile);
    $settingsFile = preg_replace('/^define\([\s\t]*\'CIVICRM_DSN\'[\s\t]*,[\s\t]*\'(.*)\'[\s\t]*\);/imsU', "define ('CIVICRM_DSN', 'mysql://{$params->new_username}:{$params->new_password}@localhost/{$params->new_cividb}?new_link=true');", $settingsFile);

    $settingsFile = preg_replace('/^\$civicrm_root = \'(.*)\';/imsU', "\$civicrm_root = '{$path}/sites/default/modules/civicrm/';", $settingsFile);
    $settingsFile = preg_replace('/^define\([\s\t]*\'CIVICRM_TEMPLATE_COMPILEDIR\'[\s\t]*,[\s\t]*\'(.*)\'[\s\t]*\);/imsU', "define ('CIVICRM_TEMPLATE_COMPILEDIR', '{$path}/sites/default/files/civicrm/templates_c/');", $settingsFile);

    $settingsFile = preg_replace('/^define\([\s\t]*\'CIVICRM_UF_BASEURL\'[\s\t]*,[\s\t]*\'(.*)\'[\s\t]*\);/imsU', "define ('CIVICRM_UF_BASEURL', '{$params->url}/');", $settingsFile);
    $settingsFile = preg_replace('/^define\([\s\t]*\'CIVICRM_SITE_KEY\'[\s\t]*,[\s\t]*\'(.*)\'[\s\t]*\);/imsU', "define ('CIVICRM_SITE_KEY', '" . bin2hex(openssl_random_pseudo_bytes(32)) . "');", $settingsFile);

    return file_put_contents($settingsPath, $settingsFile);
  }

  /**
   * Update Drupal settings that are stored in settings tables.
   * @param string $path Destination directory root
   * @param object $params Database credentials and settings
   * @return mixed Result
   */
  private function updateDrupalDbSettings($path, $params) {

    $dbconn = Database::connection($params->new_username, $params->new_password, $params->new_drupaldb);

    // Update Drupal variables. We could have called these with 'drush vset' or 'variable_set',
    // but it's only a few and we're still connected to the old environment
    $settingsToUpdate = [
      'site_name'                   => $params->sitename,
      'composer_manager_file_dir'   => $path . '/sites/all/libraries',
      'composer_manager_vendor_dir' => $path . '/sites/all/libraries/vendor',
    ];

    $settingStatement = $dbconn->prepare("UPDATE variable SET value = :value WHERE name = :name");
    foreach ($settingsToUpdate as $name => $value) {
      $settingStatement->execute([
        'name'  => $name,
        'value' => serialize($value),
      ]);
    }

    // Update homepage menu item title
    $dbconn->prepare("UPDATE menu_links SET link_title = :title WHERE link_path = :front")->execute([
      'front' => '<front>',
      'title' => $params->sitename,
    ]);

    return TRUE;
  }

  /**
   * Update CiviCRM settings that are stored in settings tables.
   * @param string $path Destination directory root
   * @param object $params Database credentials and settings
   * @return mixed Result
   */
  private function updateCiviDbSettings($path, $params) {

    $dbconn = Database::connection($params->new_username, $params->new_password, $params->new_cividb);

    // Update CiviCRM settings that are stored in table civicrm_setting
    // Some paths *should* be relative but setting them just in case.
    // We're clearing Memoria/Odoosync settings to prevent accidental syncing with live environments.
    // Mail is redirected to database ($outboundOption = 5 = CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB).
    $settingsToUpdate = [
      'uploadDir'                              => 'upload/',
      'customPHPPathDir'                       => '',
      'customTemplateDir'                      => $path . Config::DEFAULT_THEME_PATH . '/templates_civicrm/',
      'customFileUploadDir'                    => 'custom/',
      'extensionsDir'                          => $path . '/sites/default/modules/extensions/',
      'extensionsURL'                          => 'modules/extensions/',
      'imageUploadDir'                         => 'persist/contribute/',
      'imageUploadURL'                         => 'files/civicrm/persist/contribute/',
      'userFrameworkResourceURL'               => 'modules/civicrm/',
      'memoriamigr_dbhost'                     => '',
      'memoriamigr_dbname'                     => '',
      'memoriamigr_dbpass'                     => '',
      'memoriamigr_dbuser'                     => '',
      'org.civicoop.odoosync:url'              => '',
      'org.civicoop.odoosync:database_name'    => '',
      'org.civicoop.odoosync:username'         => '',
      'org.civicoop.odoosync:password'         => '',
      'org.civicoop.odoosync:view_partner_url' => '',
      'mailing_backend'                        => serialize(['outBound_option' => 5]),
    ];

    $settingStatementGroup = $dbconn->prepare("UPDATE civicrm_setting SET value = :value WHERE group_name = :group_name AND name = :name");
    $settingStatementSingle = $dbconn->prepare("UPDATE civicrm_setting SET value = :value WHERE name = :name");

    foreach ($settingsToUpdate as $name => $value) {
      if (strpos($name, ':') !== FALSE) {
        list($group_name, $setting_name) = explode(':', $name);
        $settingStatementGroup->execute(['group_name' => $group_name, 'name' => $setting_name, 'value' => serialize($value)]);
      } else {
        $settingStatementSingle->execute(['name' => $name, 'value' => serialize($value)]);
      }
    }

    // Disable some cronjobs
    $disableCronjobs = ['Odoo.autofill', 'Odoo.sync', 'MemoriaMigration.run', 'Job.process_sms', 'Job.process_pledge', 'Job.fetch_bounces'];
    foreach ($disableCronjobs as $cronjob) {
      list($entity, $action) = explode('.', $cronjob);
      $dbconn->prepare("UPDATE civicrm_job SET is_active = 0 WHERE api_entity = :entity AND api_action = :action")
             ->execute(['entity' => $entity, 'action' => $action]);
    }

    // Add word replacement to make extra extra obvious what environment we're in
    $dbconn->prepare("INSERT INTO civicrm_word_replacement SET find_word = :search, replace_word = :replace, is_active = :is_active, match_type = :match_type, domain_id = :domain_id")
      ->execute(['search' => 'CiviCRM-startpagina', 'replace' => 'CiviCRM (' . $params->sitename . ')', 'is_active' => 1, 'match_type' => 'exactMatch', 'domain_id' => 1]);

    return TRUE;
  }

}