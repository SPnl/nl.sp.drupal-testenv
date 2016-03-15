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

  ini_set('display_errors', 'on');
  error_reporting(E_ALL);
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

/* ----- COMMANDS (provisional, classes should be called by a universal hook instead) ----- */

function drush_sptestenv_testenv_new() {
	return \Testenv\Command\CreateNew::get()->run();
}

function drush_sptestenv_testenv_copy_files($destination) {
	return \Testenv\Command\CopyFiles::get()->run($destination);
}

function drush_sptestenv_testenv_copy_drupaldb($new_dbname, $copytype = 'basic') {
	return \Testenv\Command\CopyDrupalDB::get()->run($new_dbname, $copytype);
}

function drush_sptestenv_testenv_copy_cividb($new_dbname, $copytype = 'basic') {
	return \Testenv\Command\CopyCiviDB::get()->run($new_dbname, $copytype);
}

function drush_sptestenv_testenv_update_settings($destination) {
	return \Testenv\Command\UpdateSettings::get()->run($destination);
}

function drush_sptestenv_testenv_finish_copy($destination) {
	return \Testenv\Command\FinishCopy::get()->run($destination);
}

function drush_sptestenv_testenv_faker_data($destination, $count = 1000) {
	return \Testenv\Command\FakerData::get()->run($destination, $count);
}

/* ----- VALIDATORS (should be called by a universal hook too) ----- */

function drush_sptestenv_testenv_new_validate() {
  return \Testenv\Command\CreateNew::get()->validate();
}

function drush_sptestenv_testenv_copy_files_validate($destination = '') {
	return \Testenv\Command\CopyFiles::get()->validate($destination);
}

function drush_sptestenv_testenv_copy_drupaldb_validate($new_dbname = '', $copytype = NULL) {
	return \Testenv\Command\CopyDrupalDB::get()->validate($new_dbname, $copytype);
}

function drush_sptestenv_testenv_copy_cividb_validate($new_dbname = '', $copytype = NULL) {
	return \Testenv\Command\CopyCiviDB::get()->validate($new_dbname, $copytype);
}

function drush_sptestenv_testenv_update_settings_validate($destination = '') {
	return \Testenv\Command\UpdateSettings::get()->validate($destination);
}

function drush_sptestenv_testenv_finish_copy_validate($destination = '') {
	return \Testenv\Command\FinishCopy::get()->validate($destination);
}

function drush_sptestenv_testenv_faker_data_validate($destination = '', $count = 1000) {
	return \Testenv\Command\FakerData::get()->validate($destination, $count);
}