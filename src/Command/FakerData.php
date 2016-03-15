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
 * Class FakerData
 * @package Testenv\Command
 */
class FakerData extends Base {

  /**
   * @var FakerData $instance Command instance
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
   * Currently only adds contacts + phone/email/address + memberships - might add events, activities, etc later.
   *
   * WORKS LOCALLY: must be run from the NEW environment!
   * (FinishCopy will take care of this automatically if called from CreateNew)
   *
   * @param int $count Number of fake contacts to add
   * @return mixed Result
   */
  public function run($count = 1000) {

    Util::log("TESTENV: Trying to create {$count} fake contact records for this environment (" . DRUPAL_ROOT . ")...", 'ok');
    $spcivi = \SPCivi::getInstance();

    // Get custom fields
    $initialsFieldId = $spcivi->getCustomFieldId('Migratie_Contacten', 'Voorletters');
    $gemeenteFieldId = $spcivi->getCustomFieldId('Adresgegevens', 'Gemeente');

    // Get membership types
    $memberApi = $spcivi->api('MembershipType', 'get');
    $membershipTypes = [];
    foreach ($memberApi['values'] as $membershipType) {
      $membershipTypes[ $membershipType['name'] ] = $membershipType;
    }

    // Generate data!
    for ($i = 0; $i < $count; $i ++) {

      // Create contact
      $contactParams = [
        'contact_type'               => 'Individual',
        'first_name'                 => $this->faker->firstName,
        'last_name'                  => $this->faker->lastName,
        'display_name'               => $this->faker->name,
        'custom_' . $initialsFieldId => $this->faker->initials,
        'birth_date'                 => $this->faker->dateTimeBetween('-80 years', '-14 years')->format('Ymd'),
        'gender_id'                  => $this->faker->gender,
      ];

      Util::log(print_r($contactParams, TRUE), 'notice');
      echo "TEST";
      var_dump($contactParams);
      exit;
      $contact = $spcivi->api('Contact', 'create', $contactParams);
      if (!$contact) {
        return Util::log("TESTENV: CiviCRM API error, could not create contact.", 'error');
      }

      // Add address (valid address including geodata so we won't have to wait for the cronjob)
      $address = $spcivi->api('Address', 'create', [
        'contact_id'                 => $contact['id'],
        'is_primary'                 => 1,
        'location_type_id'           => 1,
        'street_name'                => $this->faker->streetName,
        'street_number'              => $this->faker->streetNumber,
        'street_unit'                => $this->faker->streetUnit,
        'city'                       => $this->faker->city,
        'postal_code'                => $this->faker->postcode,
        'custom_' . $gemeenteFieldId => $this->faker->gemeente,
        'country_id'                 => 1152, // Nederland, of iets met CRM_Core_PseudoConstant::country()
      ]);

      // Add phone number(s) (p=0.4 for home phone, p=0.7 for mobile phone)
      if ($this->faker->boolean(40)) {
        $phone = $spcivi->api('Phone', 'create', [
          'contact_id'       => $contact['id'],
          'phone'            => $this->faker->homePhone,
          'location_type_id' => 1,
          'phone_type_id'    => 1,
        ]);
      }
      if ($this->faker->boolean(70)) {
        $mobile = $spcivi->api('Phone', 'create', [
          'contact_id'       => $contact['id'],
          'phone'            => $this->faker->mobilePhone,
          'location_type_id' => 1,
          'phone_type_id'    => 2,
        ]);
      }

      // Add email address (p=0.8, always ends in example..., just to be sure)
      if ($this->faker->boolean(80)) {
        $email = $spcivi->api('Email', 'create', [
          'contact_id'       => $contact['id'],
          'phone'            => $this->faker->safeEmail,
          'location_type_id' => 1,
        ]);
      }

      // Add SP (+ ROOD) membership (p=0.9)
      if ($this->faker->boolean(90)) {
        $randomNumber = $this->faker->numberBetween(1, 100);
        if ($randomNumber < 75) {
          $membershipType = $membershipTypes['Lid SP'];
        } elseif ($randomNumber < 95) {
          $membershipType = $membershipTypes['Lid SP en ROOD'];
        } else {
          $membershipType = $membershipTypes['Lid ROOD'];
        }

        $membershipParams = [
          'contact_id'             => $contact['id'],
          'membership_type_id'     => $membershipType['id'],
          'membership_start_date'  => date('Y') . '0101000000',
          'join_date'              => $this->faker->dateTimeBetween('-10 years', 'now')->format('Ymdhis'),
          'is_override'            => 1,
          'status_id'              => 2,
          'total_amount'           => $this->faker->randomFloat(1, 5, 25),
          'financial_type_id'      => $membershipType['financial_type_id'],
          'contribution_status_id' => 2, // Pending
          'payment_instrument_id'  => ($this->faker->boolean(80) ? 10 : 9), // Incasso/acceptgiro, add lookup later
          'new_mandaat'            => 0,
          'iban'                   => $this->faker->optional(0.9)->bankAccountNumber,
        ];

        if ($membershipParams['payment_instrument_id'] == 10 && $membershipParams['iban']) {
          $membershipData['new_mandaat'] = 1;
          $membershipData['mandaat_status'] = 'FRST';
          $membershipData['mandaat_datum'] = date('Ymdhis', $membershipParams['join_date']);
          $membershipData['mandaat_plaats'] = $this->faker->city;
        }

        Util::log(print_r($membershipParams, TRUE), 'notice');
        $membership = $spcivi->api('Membership', 'spcreate', $membershipParams);
      }
    }

    return Util::log("TESTENV: Finished creating fake contacts, addresses and memberships", 'ok');
  }

  /**
   * Validate arguments
   * @param int $count Number of contact entries to create
   * @return bool Is valid
   */
  public function validate($count = NULL) {
    if (empty($count) || !is_numeric($count) || $count < 0) {
      return drush_set_error('COUNT_INVALID', 'TESTENV: Invalid number of fake contacts specified.');
    }
  }

}