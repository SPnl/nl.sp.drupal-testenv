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
	 * @return mixed
	 */
	public static function log($string, $type = 'ok') {
		if (static::isDrush()) {
			return drush_log($string, $type);
		} else {
			return watchdog('sptestenv', $string, $type);
		}
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
	 * @return array Site record
	 */
	public static function getSiteRecord($dir) {
		if(!defined('DRUSH_VERBOSE')) {
			define('DRUSH_VERBOSE', TRUE);
		}
		return [
			'root' => $dir,
			'v'    => DRUSH_VERBOSE,
		];
	}


}