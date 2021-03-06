<?php
/** --------------------------------------------------------------------------
 *  nl.sp.drupal-testenv - SP Test Environments - Drush commands
 *  Levity / Kevin Levie, kevin@levity.nl, 2016
 * -------------------------------------------------------------------------- */

/* ----- DEVELOPMENT ONLY ----- */
define('DEBUG', TRUE);
if (defined('DEBUG') && DEBUG == TRUE) {
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

function drush_sptestenv_testenv_dumpdb($dumppath) {
  return \Testenv\Command\DumpDB::get()->run($dumppath);
}

function drush_sptestenv_testenv_update_settings($destination) {
  return \Testenv\Command\UpdateSettings::get()->run($destination);
}

function drush_sptestenv_testenv_finish_copy($destination) {
  return \Testenv\Command\FinishCopy::get()->run($destination);
}

function drush_sptestenv_testenv_faker_create($count = 1000) {
  return \Testenv\Command\FakerCreate::get()->run($count);
}

function drush_sptestenv_testenv_faker_replace() {
  return \Testenv\Command\FakerReplace::get()->run();
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

function drush_sptestenv_testenv_dumpdb_validate($dumppath = NULL) {
  return \Testenv\Command\DumpDB::get()->validate($dumppath);
}

function drush_sptestenv_testenv_update_settings_validate($destination = '') {
  return \Testenv\Command\UpdateSettings::get()->validate($destination);
}

function drush_sptestenv_testenv_finish_copy_validate($destination = '') {
  return \Testenv\Command\FinishCopy::get()->validate($destination);
}

function drush_sptestenv_testenv_faker_create_validate($count = 1000) {
  return \Testenv\Command\FakerCreate::get()->validate($count);
}

function drush_sptestenv_testenv_faker_replace_validate() {
  return \Testenv\Command\FakerReplace::get()->validate();
}