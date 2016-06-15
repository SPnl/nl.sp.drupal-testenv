<?php
namespace Testenv\Command;

use Testenv\Config;
use Testenv\Database;
use Testenv\Util;

/**
 * Class CopyCiviDB
 * @package Testenv\Command
 */
class CopyCiviDB extends BaseCommand {

  /**
   * @var CopyCiviDB $instance Command instance
   */
  protected static $instance;

  /**
   * Copy this site's CiviCRM database.
   * @param string $new_dbname Destination database
   * @param string $copytype Copy type: 'basic' or 'full'
   * @param array|null $params Database credentials and settings
   * @return mixed Result
   */
  public function run($new_dbname, $copytype = 'basic', &$params = NULL) {

    // If run directly instead of through CreateNew, we'll ask for credentials here:
    if ($params === NULL) {
      $params = new \stdClass;
    }
    if (!isset($params->new_username) || !isset($params->new_password)) {
      drush_print("Please enter valid database credentials for the NEW database.\n-- The username and password you enter must already exist. If the destination databases don't exist yet, this user must have the CREATE privilege. The user may temporarily need the SUPER privilege to copy CiviCRM procedures and triggers.");
      $params->new_username = drush_prompt('Database username', str_replace('_civicrm', '', $new_dbname));
      $params->new_password = drush_prompt('Database password', NULL, TRUE, TRUE);
    }

    // Copy database
    Database::currentInfo($params);
    if (!Database::copy($params->cur_cividb, $new_dbname, $params)) {
      return Util::log('TESTENV: copying CiviCRM database failed.', 'error');
    }

    // Clean up CiviCRM database. (Not running as one transaction because that might slow down this huge operation)
    $dbconn = Database::connection($params->new_username, $params->new_password, $new_dbname);

    try {
      Util::log('TESTENV: Cleaning up CiviCRM db...', 'ok');
      $dbconn->query("SET foreign_key_checks = 0");
      $dbconn->exec("TRUNCATE TABLE civicrm_cache");
      $dbconn->exec("TRUNCATE TABLE civicrm_acl_cache");
      $dbconn->exec("TRUNCATE TABLE civicrm_acl_contact_cache");
      $dbconn->exec("TRUNCATE TABLE civicrm_group_contact_cache");
      $dbconn->exec("TRUNCATE TABLE civicrm_job_log");

      // Basic (empty) and replace (with fake data) copy actions: clean up logging and some other tables
      if (in_array($copytype, ['basic', 'replace'])) {

        Util::log("TESTENV: Truncating logging tables and cleaning up uf_match and notes tables.", 'ok');
        $dbconn->exec("TRUNCATE TABLE civicrm_log");
        $dbconn->exec("TRUNCATE TABLE civicrm_membership_log");
        $dbconn->exec("TRUNCATE TABLE civicrm_odoo_sync_error_log");
        $dbconn->exec("TRUNCATE TABLE civicrm_migration_memoria");

        // Clean up ufmatch, except for contacts and Drupal users we're explicitly instructed to keep
        $dbconn->exec("DELETE FROM civicrm_uf_match WHERE contact_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ") OR uf_id NOT IN (" . Config::DRUPAL_KEEP_USERS . ")");
        $dbconn->exec("DELETE FROM civicrm_note WHERE entity_table = 'civicrm_contact' AND entity_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");
      }

      // Basic copy: remove all contacts except for admin / system accounts (and certain contact types)
      // and truncate Odoo and mailing tables
      if ($copytype == 'basic') {

        // Get the contact ids for all contacts we don't want to remove
        $contacts = $dbconn->query("SELECT id FROM civicrm_contact WHERE id IN (" . Config::CIVI_KEEP_CONTACTS . ") OR id IN (SELECT contact_id FROM civicrm_uf_match) OR contact_sub_type IN (" . Config::CIVI_KEEP_CONTACT_SUBTYPES . ")");
        $contact_ids = $contacts->fetchAll(\PDO::FETCH_COLUMN, 0);

        Util::log("TESTENV: Removing contacts from civicrm_contact, address, phone, email, ufmatch, relationship, contribution, participant, mandaat.", 'ok');
        $dbconn->exec("DELETE FROM civicrm_contact WHERE id NOT IN (" . implode(',', $contact_ids) . ")");

        $dbconn->exec("DELETE FROM civicrm_phone WHERE contact_id NOT IN (" . implode(',', $contact_ids) . ")");
        $dbconn->exec("DELETE FROM civicrm_address WHERE contact_id NOT IN (" . implode(',', $contact_ids) . ")");
        $dbconn->exec("DELETE FROM civicrm_email WHERE contact_id NOT IN (" . implode(',', $contact_ids) . ")");

        $dbconn->exec("DELETE FROM civicrm_relationship WHERE contact_id_a NOT IN (" . implode(',', $contact_ids) . ") AND contact_id_b NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");
        $dbconn->exec("DELETE FROM civicrm_contribution WHERE contact_id NOT IN (" . implode(',', $contact_ids) . ")");
        $dbconn->exec("DELETE FROM civicrm_participant WHERE contact_id NOT IN (" . implode(',', $contact_ids) . ")");
        $dbconn->exec("DELETE FROM civicrm_mandaat WHERE id NOT IN (" . implode(',', $contact_ids) . ")");

        Util::log("TESTENV: Truncating Odoo sync, financial and mailing tables.", 'ok');
        $dbconn->exec("TRUNCATE TABLE civicrm_odoo_entity");
        $dbconn->exec("TRUNCATE TABLE civicrm_entity_financial_trxn");
        $dbconn->exec("TRUNCATE TABLE civicrm_line_item");
        $dbconn->exec("TRUNCATE TABLE civicrm_membership_payment");
        $dbconn->exec("TRUNCATE TABLE civicrm_participant_payment");

        $dbconn->exec("TRUNCATE TABLE civicrm_mailing");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_component");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_bounce");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_confirm");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_delivered");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_forward");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_queue");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_reply");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_subscribe");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_trackable_url_open");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_event_unsubscribe");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_recipients");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_spool");
        $dbconn->exec("TRUNCATE TABLE civicrm_mailing_trackable_url");

        // Try to remove records based on contact_id / entity_id from all tables
        Util::log("TESTENV: Removing contacts from all custom field and other tables.", 'ok');
        $tables = $dbconn->query("SHOW TABLES", \PDO::FETCH_COLUMN, 0);

        foreach ($tables as $table) {
          $tdescribe = $dbconn->query("DESCRIBE `{$table}`", \PDO::FETCH_COLUMN, 0);
          if (!$tdescribe) {
            continue;
          }
          $tcolnames = $tdescribe->fetchAll();

          if (in_array('contact_id', $tcolnames)) {

            Util::log("TESTENV: Removing records by contact_id from table '{$table}'.", 'info');
            $dbconn->exec("DELETE FROM {$table} WHERE contact_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");

          } elseif (in_array('entity_id', $tcolnames) && strpos($table, 'civicrm_value_') === 0) {

            // If table contains entity_id, check if it is in civicrm_custom_group.
            $cgroup = $dbconn->query("SELECT * FROM civicrm_custom_group WHERE table_name = '{$table}' AND extends IN ('Individual','Contact')");
            if ($cgroup->rowCount() > 0) {

              Util::log("TESTENV: Removing records by entity_id from custom field group table '{$table}'.", 'info');
              $dbconn->exec("DELETE FROM {$table} WHERE entity_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");
            }
          }
        }
      }

      Util::log('TESTENV: Finished clean up action for CiviCRM database.', 'ok');
      $dbconn->query("SET foreign_key_checks = 1");

    } catch (\Exception $e) {
      Util::log('An error occurred while cleaning up the new CiviCRM database. (' . $e->getMessage() . ')', 'error');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validate arguments
   * @param string $new_dbname Destination database
   * @param string $copytype Copy type
   * @return bool Is valid
   */
  public function validate($new_dbname = '', $copytype = 'basic') {
    if (empty($new_dbname)) {
      return drush_set_error('DB_EMPTY', 'TESTENV: No destination database specified.');
    }
    if (!empty($type) && !in_array($type, ['basic', 'full'])) {
      return drush_set_error('DB_INVALIDTYPE', 'TESTENV: Invalid copy type, should be \'basic\' or \'full\'.');
    }
  }

}