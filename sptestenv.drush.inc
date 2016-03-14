<?php
/** --------------------------------------------------------------------------
 *  nl.sp.drupal-testenv - SP Test Environments - Drush commands
 *  Levity / Kevin Levie, kevin@levity.nl, 2016
 * -------------------------------------------------------------------------- */

/**
 * Early autoloader to make classes available for Drush
 */
function sptestenv_early_autoload() {
	require_once __DIR__ . '/../../contrib/xautoload/xautoload.early.inc';
	xautoload()->finder->addPsr4('Testenv\\', __DIR__ . '/src/');
}

/**
 * Implements hook_drush_command().
 * @see \Testenv\Config::getDrushCommands().
 * @return array Available Drush Commands
 */
function sptestenv_drush_command() {
	sptestenv_early_autoload();
	return \Testenv\Config::getDrushCommands();
}

/* ----- COMMANDS ----- */

function drush_sptestenv_testenv_new() {
	return \Testenv\Command\CreateNew::get()->run();
}

function drush_sptestenv_testenv_copy_files($destination) {
	return \Testenv\Command\CopyFiles::get()->run($destination);
}

function drush_sptestenv_testenv_copy_drupaldb($cur_dbname, $new_dbname, $copytype = 'basic', $dbinfo = null) {
	return \Testenv\Command\CopyDrupalDB::get()->run($cur_dbname, $new_dbname, $copytype, $dbinfo);
}

function drush_sptestenv_testenv_copy_cividb($cur_dbname, $new_dbname, $copytype = 'basic', $dbinfo = null) {
	return \Testenv\Command\CopyCiviDB::get()->run($cur_dbname, $new_dbname, $copytype, $dbinfo);
}

function drush_sptestenv_testenv_update_settings($destination) {
	return \Testenv\Command\UpdateSettings::get()->run($destination);
}

function drush_sptestenv_testenv_fakerdata($destination, $count = 1000) {
	return \Testenv\Command\FakerData::get()->run($destination, $count);
}

/* ----- VALIDATORS ----- */

function drush_sptestenv_testenv_copy_files_validate($destination = '') {
	return \Testenv\Command\CopyFiles::get()->validate($destination);
}

function drush_sptestenv_testenv_copy_drupaldb_validate($destination = '', $type = NULL) {
	return \Testenv\Command\CopyDrupalDB::get()->validate($destination, $type);
}

function drush_sptestenv_testenv_copy_cividb_validate($destination = '', $type = NULL) {
	return \Testenv\Command\CopyCiviDB::get()->validate($destination, $type);
}

function drush_sptestenv_testenv_update_settings_validate($destination = '') {
	return \Testenv\Command\UpdateSettings::get()->validate($destination);
}

function drush_sptestenv_testenv_fakerdata_validate($destination = '', $count = 1000) {
	return \Testenv\Command\FakerData::get()->validate($destination, $count);
}