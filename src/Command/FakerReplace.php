<?php
namespace Testenv\Command;

use Faker\Factory as FakerFactory;
use Testenv\Config;
use Testenv\FakerProvider\HomePhone;
use Testenv\FakerProvider\MobilePhone;
use Testenv\FakerProvider\Person;
use Testenv\FakerProvider\ValidAddress;
use Testenv\Util;

/**
 * Class FakerReplace
 * @package Testenv\Command
 */
class FakerReplace extends BaseCommand {

  /**
   * @var FakerReplace $instance Command instance
   */
  protected static $instance;

  /**
   * @var \Faker\Generator $faker
   * For documentation, see https://github.com/fzaninotto/Faker
   */
  private $faker;

  /**
   * FakerData constructor. Adds custom Faker data providers to Faker for generating gender data with matching M/F
   * first names, correct Dutch phone numbers, and random valid postal addresses with matching street/postcode/city.
   */
  public function __construct() {

    $this->faker = $faker = FakerFactory::create(Config::FAKER_LOCALE);
    $faker->addProvider(new Person($faker));
    $faker->addProvider(new ValidAddress($faker));
    $faker->addProvider(new HomePhone($faker));
    $faker->addProvider(new MobilePhone($faker));
  }

  /**
   * Generate fake sample data for Drupal and CiviCRM using Faker.
   * This class will replace all existing sensitive contact data with fake data. It supports the most important database tables and fields, but may not replace or erase *all* sensitive data. Please review your installation manually after running this script.
   *
   * WORKS LOCALLY: must be run from the NEW environment!
   * (FinishCopy will take care of this automatically if called from CreateNew)
   *
   * @return mixed Result
   */
  public function run() {

    Util::log("TESTENV: Trying to replace all contact data with fake data for this environment (" . DRUPAL_ROOT . ")...", 'ok');
    $spcivi = \SPCivi::getInstance();

    // Custom fields and relevant data
    $keepContacts = explode(',', Config::CIVI_KEEP_CONTACTS);
    $currentMembershipStatuses = \CRM_Member_BAO_MembershipStatus::getMembershipStatusCurrent();
    $initialsFieldId = $spcivi->getCustomFieldId('Migratie_Contacten', 'Voorletters');

    // If session->get('userID') is not set, nl.sp.logaddresschange will crash this script
    $session = \CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if (empty($id)) {
      $session->set('userID', 1);
    }

    // Fetch and update all contacts
    // TODO Deze functie is ietwat repetitief en zou nog beter omgeschreven kunnen worden.
    $contacts = $spcivi->api('Contact', 'get', [
      'contact_type' => 'Individual',
      'options'      => ['limit' => 999999],
    ]);

    if ($contacts['is_error']) {
      Util::log("TESTENV: CiviCRM API error, could not fetch contacts: " . $contacts['error_message'] . ".", 'error');
      return FALSE;
    }

    Util::log("TESTENV: Randomizing names, birth dates and gender for " . $contacts['count'] . " contacts.");
    $count = 0;
    foreach ($contacts['values'] as $contact) {

      // Do not replace important admin contacts
      if (in_array($contact['id'], $keepContacts)) {
        continue;
      }

      $this->faker->clearPerson();

      // Update contact name, birthdate and gender
      $contactParams = [
        'id'                         => $contact['id'],
        'first_name'                 => $this->faker->firstName,
        'last_name'                  => $this->faker->lastName,
        'display_name'               => $this->faker->name,
        'custom_' . $initialsFieldId => $this->faker->initials,
        'birth_date'                 => $this->faker->dateTimeBetween('-80 years', '-14 years')->format('Ymd'),
        'gender_id'                  => (int) $this->faker->gender,
      ];

      // Update deceased date if not empty
      if (!empty($contact['deceased_date'])) {
        $contactParams['deceased_date'] = $this->faker->dateTimeBetween('-10 years', 'now')->format('Ymd');
      }

      $result = $spcivi->api('Contact', 'create', $contactParams);
      if (!$result || $result['is_error']) {
        Util::log('TESTENV: CiviCRM API error, could not update contact: ' . $result['error_message'] . '.', 'error');
        continue;
      }
      // Util::log(print_r($result, TRUE), 'ok');

      $count++;
      if($count % 500 == 0) {
        Util::log("TESTENV: Randomized {$count} contacts... (current id: {$contact['id']})", 'ok');
      }
    }

    // Fetch and update all (Dutch) addresses
    $addresses = $spcivi->api('Address', 'get', [
      'country_id' => 1152,
      'options'    => ['limit' => 999999],
    ]);

    Util::log("TESTENV: Randomizing " . $addresses['count'] . " addresses.");
    $count = 0;
    foreach ($addresses['values'] as $address) {

      if (in_array($address['contact_id'], $keepContacts)) {
        continue;
      }

      $this->faker->clearAddress();
      $this->faker->initializeAddress($address['postal_code'] . $address['postal_code_suffix'], $address['street_number']);

      $aresult = $spcivi->api('Address', 'create', [
        'id'             => $address['id'],
        'street_name'    => $this->faker->streetName,
        'street_number'  => $this->faker->streetNumber,
        'street_unit'    => $this->faker->streetUnit,
        'street_address' => $this->faker->fullAddress,
        'city'           => strtoupper($this->faker->city),
        'postal_code'    => $this->faker->postcode,
        'latitude'       => $this->faker->latitude,
        'longitude'      => $this->faker->longitude,
      ]);
      // Util::log(print_r($aresult, TRUE), 'ok');

      $count++;
      if($count % 500 == 0) {
        Util::log("TESTENV: Randomized {$count} addresses... (current id: {$address['id']})", 'ok');
      }
    }

    // Fetch and update all phone numbers
    $phones = $spcivi->api('Phone', 'get', [
      'options' => ['limit' => 999999],
    ]);

    Util::log("TESTENV: Randomizing " . $phones['count'] . " phone numbers.");
    $count = 0;
    foreach ($phones['values'] as $phone) {

      if (in_array($phone['contact_id'], $keepContacts)) {
        continue;
      }

      $presult = $spcivi->api('Phone', 'create', [
        'id'    => $phone['id'],
        'phone' => ($phone['phone_type_id'] == 1 ? $this->faker->homePhone : $this->faker->mobilePhone),
      ]);
      // Util::log(print_r($presult, TRUE), 'ok');

      $count++;
      if($count % 500 == 0) {
        Util::log("TESTENV: Randomized {$count} phone numbers... (current id: {$phone['id']})", 'ok');
      }
    }

    // Fetch and update all email addresses
    $emails = $spcivi->api('Email', 'get', [
      'options' => ['limit' => 999999],
    ]);

    Util::log("TESTENV: Randomizing " . $emails['count'] . " email addresses.");
    $count = 0;
    foreach ($emails['values'] as $email) {

      if (in_array($email['contact_id'], $keepContacts)) {
        continue;
      }

      $eresult = $spcivi->api('Email', 'create', [
        'id'    => $email['id'],
        'email' => $this->faker->safeEmail,
      ]);
      // Util::log(print_r($eresult, TRUE), 'ok');

      $count++;
      if($count % 500 == 0) {
        Util::log("TESTENV: Randomized {$count} email addresses... (current id: {$email['id']})", 'ok');
      }
    }

    // Fetch and update all memberships
    $memberships = $spcivi->api('Membership', 'get', [
      'options' => ['limit' => 999999],
    ]);

    Util::log("TESTENV: Randomizing " . $memberships['count'] . " memberships.");
    $count = 0;
    foreach ($memberships['values'] as $membership) {

      if (in_array($membership['contact_id'], $keepContacts)) {
        continue;
      }

      $membershipParams = [
        'id'           => $membership['id'],
        'start_date'   => date('Y') . '0101000000',
        'end_date'     => date('Y') . '1231000000',
        'join_date'    => $this->faker->dateTimeBetween('-10 years', 'now')->format('Ymdhis'),
        'total_amount' => $this->faker->randomFloat(1, 5, 25),
      ];
      if (!in_array($membership['status_id'], $currentMembershipStatuses)) {
        $membershipParams['end_date'] = $this->faker->dateTimeBetween('-10 years', 'now')->format('Ymdhis');
      }

      $mresult = $spcivi->api('Membership', 'create', $membershipParams);
      // Util::log(print_r($mresult, TRUE), 'ok');

      $count++;
      if($count % 500 == 0) {
        Util::log("TESTENV: Randomized {$count} memberships... (current id: {membership['id']})", 'ok');
      }
    }

    // Fetch and update all IBAN accounts and SEPA mandates.
    // TODO
    Util::log("TESTENV: TODO, not implemented yet: randomize all IBAN accounts, SEPA mandates and contributions.");

    // Replacing participant records, activities and relationships is also not implemented yet.
    // TODO
    Util::log("TESTENV: TODO, not implemented yet: randomize or remove participant records, activities and relationships.");

    // Contact notes are cleared in the CopyCiviDB command.

    // All done!
    return Util::log("TESTENV: Finished replacing contact data with random data.", 'ok');
  }

  /**
   * Validate arguments
   * @return bool Is valid
   */
  public function validate() {
    return TRUE;
  }

}