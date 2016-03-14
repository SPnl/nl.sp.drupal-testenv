nl.sp.drupal-testenv
====================

Deze Drupal 7-module bevat functionaliteit bevatten voor het opzetten en inrichten van testomgevingen voor Drupal/CiviCRM, en voor het toevoegen van fake / sample data aan die omgevingen. Gebouwd voor de SP, maar wellicht ook elders bruikbaar.

Mijn idee tot nu toe: bij nader inzien niet te ingewikkeld maken. Geen grote webinterface, geen overzichten met testomgevingen. Gebaseerd op Drush-commando's.
Commando('s) om:
- Bestanden van een omgeving naar een nieuwe te kopieren, exclusief lokale uploads en cache.
- Database van een naar een andere omgeving te kopieren.
- Op schijf bepaalde configuratiebestanden aan te passen, zoals settings.php / civicrm.settings.php.
- In de database afhankelijk van instelling bepaalde tabellen skippen of alleen de structuur kopieren.
- In de database bepaalde instellingen aanpassen: site-naam en evt content, directory's en URL's van CiviCRM, en scheduled jobs en mailverzending.
- Genereren van een hoeveelheid sample data / Nederlandse fake-contacten, naar behoefte.
- Codestructuur gaarne iets meer in de verte op iets objectgeorienteerds laten lijken.