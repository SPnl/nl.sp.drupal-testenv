<?php
namespace Testenv\Command;

use Testenv\Util;

/**
 * Class CopyFiles
 * @package Testenv\Command
 */
class CopyFiles extends Base {

	/**
	 * Copy this site's files to a new testing environment.
	 * @param string $destination Destination directory
	 * @return mixed Result
	 */
	public function run($destination) {

		// Call drush core-rsync to perform the actual sync
		$ret = drush_invoke_process('@self', 'rsync', ['@self', $destination], ['--include-conf', '--exclude-paths="sites/default/files/civicrm/templates_c/:sites/default/files/civicrm/ConfigAndLog/*:sites/default/files/css/*:sites/default/files/js/*:"']);
		if ($ret === FALSE) {
			return Util::log('TESTENV: drush rsync failed.', 'error');
		}

		return Util::log('TESTENV: finished copying files.', 'ok');
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