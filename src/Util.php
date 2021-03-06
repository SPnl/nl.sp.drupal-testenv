<?php

namespace Testenv;

/**
 * Class Util
 * @package Testenv
 */
class Util {

  /**
   * Check if this script is being called via Drush.
   * @return bool Is Drush
   */
  public static function isDrush() {
    return (function_exists('drush_main'));
  }

  /**
   * Print or log a message. Uses drush_log for Drush calls (use -v to see all messages).
   * Uses watchdog in other environments (turn on debugging to see all messages, such as notices).
   * @param $string
   * @param string $type
   * @return bool
   */
  public static function log($string, $type = 'ok') {
    if (is_array($string)) {
      $string = print_r($string, TRUE);
    }
    if (static::isDrush()) {
      $type = ($type == 'debug' ? 'notice' : $type);
      drush_log(date('[Y-m-d H:i:s] ') . $string, $type);
    } else {
      watchdog('sptestenv', $string, $type);
    }
    return ($type == 'ok');
  }

  /**
   * Get temporary directory - used for copying databases using mysqldump.
   * @return string Temp dir
   */
  public static function getTempDir() {
    if (defined('DRUSH_TEMP')) {
      return DRUSH_TEMP;
    } elseif (defined('TMP_DIR')) {
      return TMP_DIR;
    } else {
      return sys_get_temp_dir();
    }
  }

  /**
   * Get site record by directory name. Used for drush_invoke_process calls.
   * @param string $dir Drupal root directory
   * @return array Site record
   */
  public static function getSiteRecord($dir) {
    return [
      '#name' => 'newenv',
      'root'  => $dir,
      'url'   => 'https://civicrmacc.sp.nl',
      'v'     => DRUSH_VERBOSE,
    ];
  }


}