<?php
namespace Testenv\Command;

use Testenv\Config;
use Testenv\Database;
use Testenv\Util;

/**
 * Class CopyCiviDB
 * @package Testenv\Command
 */
class CopyCiviDB extends Base {

  /**
   * Copy this site's CiviCRM database.
   * @param string $cur_dbname Current database
   * @param string $new_dbname Destination database
   * @param string $copytype Copy type: 'basic' or 'full'
   * @param array|null $params Database credentials and settings
   * @return mixed Result
   */
  public function run($cur_dbname, $new_dbname, $copytype = 'basic', &$params = NULL) {

    // If run directly instead of through CreateNew, we'll ask for credentials here:
    if (!isset($params->new_drupaldb)) {
      drush_print('Please enter valid database credentials for the NEW database.');
      $params->new_username = drush_prompt('Database username', $new_dbname);
      $params->new_password = drush_prompt('Database password', NULL, TRUE, TRUE);
    }

    // Copy database
    if (!Database::copy($cur_dbname, $new_dbname, $dbinfo)) {
      return Util::log('TESTENV: copying CiviCRM database failed.', 'error');
    }

    // Clean up CiviCRM database. Running in one transaction
    $dbconn = Database::connection($dbinfo, $new_dbname);
    $dbconn->beginTransaction();

    try {
      $dbconn->query("SET foreign_key_checks = 0");
      $dbconn->exec("TRUNCATE TABLE civicrm_cache");
      $dbconn->exec("TRUNCATE TABLE civicrm_job_log");

      if ($copytype == 'basic') {

        // TODO organisaties zoals SP-afdelingen behouden, dus deze queries wat ingewikkelder.
        Util::log('TESTENV: Cleaning up CiviCRM db. TODO - keep some other reserved contact records, such as SP-afdelingen.', 'notice');

        // Remove all contacts except for admin / system accounts
        Util::log("TESTENV: Removing contacts from _contact, _uf_match, _relationship, _contribution, _participant, _mandaat.", 'info');
        $dbconn->exec("DELETE FROM civicrm_contact WHERE id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");
        $dbconn->exec("DELETE FROM civicrm_uf_match WHERE contact_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ") OR uf_id NOT IN (" . Config::DRUPAL_KEEP_USERS . ")");

        $dbconn->exec("DELETE FROM civicrm_relationship WHERE contact_id_a NOT IN (" . Config::CIVI_KEEP_CONTACTS . ") AND contact_id_b NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");
        $dbconn->exec("DELETE FROM civicrm_contribution WHERE contact_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");
        $dbconn->exec("DELETE FROM civicrm_participant WHERE contact_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");
        $dbconn->exec("DELETE FROM civicrm_mandaat WHERE id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");

        Util::log("TESTENV: Truncating log, Odoo sync, financial and mailing tables.", 'info');
        $dbconn->exec("TRUNCATE TABLE civicrm_log");
        $dbconn->exec("TRUNCATE TABLE civicrm_membership_log");
        $dbconn->exec("TRUNCATE TABLE civicrm_odoo_sync_error_log");
        $dbconn->exec("TRUNCATE TABLE civicrm_migration_memoria");

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

        $tables = $dbconn->query("SHOW TABLES", \PDO::FETCH_COLUMN, 0);
        $tables->execute();

        foreach ($tables as $table) {
          $tcolumns = $dbconn->query("DESCRIBE `{$table}`", \PDO::FETCH_COLUMN, 0);
          $tcolumns->execute();
          $tcolnames = $tcolumns->fetchAll();

          if (in_array('contact_id', $tcolnames)) {

            Util::log("TESTENV: Removing records by contact_id from table '{$table}'.", 'info');
            $dbconn->exec("DELETE FROM {$table} WHERE contact_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");

          } elseif (in_array('entity_id', $tcolnames) && strpos($table, 'civicrm_value_') === 0) {

            // If table contains entity_id, check if it is in civicrm_custom_group.
            $cgroup = $dbconn->query("SELECT * FROM civicrm_custom_group WHERE table_name = ? AND extends IN (?)");
            $cgroup->execute([1 => $table, 2 => ['Contact', 'Individual', 'Organization']]);
            if ($cgroup->rowCount() > 0) {

              Util::log("TESTENV: Removing records by entity_id from custom field group table '{$table}'.", 'info');
              $dbconn->exec("DELETE FROM {$table} WHERE entity_id NOT IN (" . Config::CIVI_KEEP_CONTACTS . ")");
            }
          }
        }
      }

      Util::log('TESTENV: Committing clean up transaction for CiviCRM database.', 'ok');
      $dbconn->query("SET foreign_key_checks = 1");
      $dbconn->commit();

    } catch (\Exception $e) {
      $dbconn->rollBack();
      Util::log('An error occurred while cleaning up the new CiviCRM database. Rolling back. (' . $e->getMessage() . ')');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validate arguments
   * @param string $destination Destination directory
   * @param string $copytype Copy type
   * @return bool Is valid
   */
  public function validate($destination = '', $copytype = 'basic') {
    if (empty($destination)) {
      return drush_set_error('DB_EMPTY', 'TESTENV: No destination database specified.');
    }
    if (!empty($type) && !in_array($type, ['basic', 'full'])) {
      return drush_set_Error('DB_INVALIDTYPE', 'TESTENV: Invalid copy type, should be \'basic\' or \'full\'.');
    }
  }
}