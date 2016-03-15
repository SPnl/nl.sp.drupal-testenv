nl.sp.drupal-testenv
====================

This Drupal 7 module contains functionality to create and configure test environments for Drupal and CiviCRM, based on a currently active (production) installation, and fill the new environment with sample data.  
The module has been specifically built for the SP but should, with some modifications, be usable elsewhere.


Commands:
---------
This module adds the following Drush commands:
- **drush testenv-new** - You will be prompted for the destination directory, database credentials and other options.  
    This command calls all the other commands below.
- **drush testenv-copy-files <destination>** - Copy this site's files to a new testing environment at /destination
- **drush testenv-copy-drupaldb <new_db> <copytype>** - Copy this site's Drupal database
- **drush testenv-copy-cividb <new_db> <copytype>** - Copy this site's CiviCRM database
- **drush testenv-update-settings <destination>** - Modify Drupal / CiviCRM settings files and tables for a newly created environment
- **drush testenv-finish-copy <destination>** - Perform final actions after creating a new environment, clear all caches and run cron
- **drush testenv-faker-data <destination> <count>** - Add fake contacts and Drupal users, using the Faker library: run this one in the *destination* environment!


Improvements that could / should be made:
-----------------------------------------
- Make all code and configuration dynamic and non-SP-specific  
      (there are some hardcoded options as well as a list of constants in Testenv\Config)
- SP-specific: maybe make some changes to the Drupal front page automatically  
    (changing and removing some panels, like at civicrm-cursus)
- Keep some more existing users and data, depending on the user's preferences
- Filter the right user / contact data before copying the database (data we don't want is currently only removed *after* the database has been copied; mysqldump seems to have a --where option!)
- Convert the $param array that's being tossed around into something like a ParameterBag
- Improve command output and fix my random use of drush_log/_print and error levels
- Call Command classes and validators using a hook instead of the functions currently in sptestenv.drush.inc
