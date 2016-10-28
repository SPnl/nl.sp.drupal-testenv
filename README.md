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
- **drush testenv-dumpdb <destination>** - Generate SQL dumps for both databases, that can be imported on another server
- **drush testenv-update-settings <destination>** - Modify Drupal / CiviCRM settings files and tables for a newly created environment
- **drush testenv-finish-copy <destination>** - Perform final actions after creating a new environment, clear all caches and run cron  
- **drush testenv-faker-create <destination> <count>** - Add fake contacts, using the Faker library (run in the *destination* environment!)
- **drush testenv-faker-replace <destination> <count>** - Replace all contacts with random fake data, using the Faker library (run in the *destination* environment!)


Faker library:
--------------
The last two commands require the Faker library ([fzaninotto/faker](https://github.com/fzaninotto/Faker)). If you have the [composer_manager](https://www.drupal.org/project/composer_manager) module installed, the library can be easily installed and updated because of the included composer.json file.  
If you do not wish to use composer_manager, you'll need to download the Faker library yourself, add it to your libraries directory and make it available through an autoloader. 


Improvements that could / should be made:
-----------------------------------------

Functional:
- Make all code and configuration dynamic and non-SP-specific
      (there are some hardcoded options as well as a list of (pseudo)constants in Testenv\Config, and quite a lot of specific table / field references in the Faker* classes)
- SP-specific: maybe make some changes to the Drupal front page automatically
    (changing and removing some panels, like at civicrm-cursus)
- Keep some more existing users and data, depending on the user's preferences
- FakerReplace now uses database queries, FakerCreate still uses the API and would perform better if it also queried the database directly
- We could add the database and an installation database user automatically, if the user provides a root login

Random data:
- Replace all SP specific table and field references with something more flexible and non-SP-specific 
- Remove or replace activities, relationships, and participants with random data
- Remove or replace contributions (both member and event related)
- Keep Drupal user accounts and/or update them accordingly

See the internal Google Docs file for more possible improvements.

Nerd stuff:
- Improve the FakerCreate and FakeReplace command classes and make them less repetitive 
- Convert the $param array that's being tossed around into something like a ParameterBag
- Improve command output in general, and fix my random use of drush_log/_print and error levels
- Add commands and validators using a hook instead of the functions currently in sptestenv.drush.inc
