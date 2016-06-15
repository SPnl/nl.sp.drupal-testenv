<?php
/** --------------------------------------------------------------------------
 *  nl.sp.drupal-testenv - SP Test Environments - Drush commands
 *  Levity / Kevin Levie, kevin@levity.nl, 2016
 * -------------------------------------------------------------------------- */

/* ----- DEVELOPMENT ONLY ----- */
define('DEBUG', true);
if(defined('DEBUG') && DEBUG == true) {
  ini_set('display_errors', 'on');
  ini_set('display_startup_errors', 'on');
  error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
  // drush_set_context('DRUSH_VERBOSE', TRUE);
}

/* ----- AUTOLOADER ----- */

// Load and register autoloader
require_once __DIR__ . '/src/Autoloader.php';
$sptestenv_loader = \Testenv\Autoloader::getInstance();

// Register Testenv namespace
$sptestenv_loader->register();
$sptestenv_loader->addNamespace('Testenv\\', __DIR__ . '/src/');

/* ----- LIST DRUSH COMMANDS ----- */

/**
 * Implements hook_drush_command().
 * @see \Testenv\Config::getDrushCommands().
 * @return array Available Drush Commands
 */
function sptestenv_drush_command() {
  return \Testenv\Config::getDrushCommands();
}

/* ----- COMMANDS (provisional calls) ----- */

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

function drush_sptestenv_testenv_faker_create($destination, $count = 1000) {
	return \Testenv\Command\FakerCreate::get()->run($destination, $count);
}

function drush_sptestenv_testenv_faker_replace($destination) {
  return \Testenv\Command\FakerReplace::get()->run($destination);
}

/* ----- VALIDATORS (provisional calls) ----- */

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

function drush_sptestenv_testenv_faker_create_validate($destination = '', $count = 1000) {
  return \Testenv\Command\FakerCreate::get()->validate($destination, $count);
}

function drush_sptestenv_testenv_faker_replace_validate($destination = '') {
  return \Testenv\Command\FakerReplace::get()->validate($destination);
}