nl.sp.drupal-testenv
====================

This Drupal 7 module contains functionality to create and configure test environments for Drupal and CiviCRM, based on a currently active (production) installation, and fill the new environment with sample data. The module has been specifically built for the SP but should, with some modifications, be usable elsewhere.


Commands:
---------
This module adds the following Drush commands:

- **drush testenv-new** - You will be prompted for the destination directory, database credentials and other options.  
    This command calls all the other commands below. This is the recommended way of using this script.  
- **drush testenv-copy-files <destination>** - Copy this site's files to a new testing environment at /destination
- **drush testenv-copy-drupaldb <new_db> <copytype>** - Copy this site's Drupal database
- **drush testenv-copy-cividb <new_db> <copytype>** - Copy this site's CiviCRM database
- **drush testenv-update-settings <destination>** - Modify Drupal / CiviCRM settings files and tables for a newly created environment
- **drush testenv-finish-copy <destination>** - Perform final actions after creating a new environment, clear all caches and run cron  
- **drush testenv-faker-create <destination> <count>** - Add fake contacts, using the Faker library (run in the *destination* environment!)
- **drush testenv-faker-replace <destination> <count>** - Replace all contacts with random fake data, using the Faker library (run in the *destination* environment!)


Improvements that could / should be made:
-----------------------------------------

Functional:
- Make all code and configuration dynamic and non-SP-specific  
      (there are some hardcoded options as well as a list of (pseudo)constants in Testenv\Config)
- SP-specific: maybe make some changes to the Drupal front page automatically  
    (changing and removing some panels, like at civicrm-cursus)
- Keep some more existing users and data, depending on the user's preferences
- Filter the right user / contact data before copying the database (data we don't want is currently removed *after* copying - but filtering using --where in mysqldump will probably be difficult)
- FakerCreate/FakerReplace performance could likely be improved by using direct queries instead of CiviCRM API requests (some actions could perhaps be completely handled in MySQL - e.g. UPDATE civicrm_address SELECT FROM civicrm_postcodenl ...)  

Random data:
- Replace IBAN accounts and SEPA mandates with random data
- Remove or replace activities, relationships, and participants with random data
- Remove or replace contributions (both member and event related)
- Keep Drupal user accounts and/or update them accordingly
See the internal Google Docs file for more possible improvements.

Nerd stuff:
- Improve the FakerCreate and FakeReplace command classes and make them less repetitive 
- Convert the $param array that's being tossed around into something like a ParameterBag
- Improve command output in general, and fix my random use of drush_log/_print and error levels
- Call commands and validators using a hook instead of the functions currently in sptestenv.drush.inc
