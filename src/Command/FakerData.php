<?php
namespace Testenv\Command;

use Faker\Factory as FakerFactory;
use Testenv\FakerProvider\HomePhone;
use Testenv\FakerProvider\MobilePhone;
use Testenv\FakerProvider\Person;
use Testenv\FakerProvider\ValidAddress;

/**
 * Class FakerData
 * @package Testenv\Command
 */
class FakerData extends Base {

	/** @var \Faker\Generator $faker */
	private $faker;

	/**
	 * FakerData constructor. Adds a few custom providers to Faker.
	 */
	public function __construct() {

		$this->faker = $faker = FakerFactory::create('nl_NL');
		$faker->addProvider(new Person($faker));
		$faker->addProvider(new ValidAddress($faker));
		$faker->addProvider(new HomePhone($faker));
		$faker->addProvider(new MobilePhone($faker));
	}

	/**
	 * @param string $destination Destination directory
	 * @param int $count Number of fake contacts to add
	 * @param array|null $dbinfo Database info, if called from sptestenv_create
	 * @return mixed Result
	 */
	public function run($destination, $count = 1000, $dbinfo = NULL) {

		// TODO add fake data to CiviCRM!
		// Kijken / nadenken of en hoe ik kan zorgen voor wat mensen, geostelsel-info, wellicht wat relaties met afdelingen, wat lidmaatschappen, evenementen, bijdragen, .....
		
		$ret = "<pre>\n";

		$ret .= "Geslacht: {$this->faker->gender}\n";
		$ret .= "Voornaam: {$this->faker->firstName}\n";
		$ret .= "Achternaam: {$this->faker->lastName}\n";
		$ret .= "Bedrijf: " . $this->faker->company . "\n\n";

		$ret .= "Adres: {$this->faker->fullAddress}\n";
		$ret .= "Postcode: {$this->faker->postcode}\n";
		$ret .= "Plaats: {$this->faker->city}\n";
		$ret .= "Gemeente: {$this->faker->gemeente}\n";
		$ret .= "Provincie: {$this->faker->provincie}\n";
		$ret .= "Land: {$this->faker->country}\n\n";

		$ret .= "Telefoon thuis: {$this->faker->homePhone}\n";
		$ret .= "Mobiel nummer: {$this->faker->mobilePhone}\n";

		$ret .= "E-mail (safe): " . $this->faker->optional(0.8)->safeEmail . "\n\n"; // In 20% van de gevallen null

		$ret .= "Geboortedatum: " . $this->faker->dateTimeBetween('-80 years', '-14 years')->format('d-m-Y') . "\n"; // \DateTime
		$ret .= "IBAN: " . $this->faker->bankAccountNumber . "\n";
		$ret .= "Creditcard: " . $this->faker->optional($weight = 0.2)->creditCardNumber . "\n";
		$ret .= "Favoriete kleur: " . $this->faker->colorName . "\n";
		$ret .= "IP-adres: " . $this->faker->ipv4 . "\n\n";

		// $ret .= "Lorem ipsum: " . $this->faker->text(250) . "\n";

		$ret .= "</pre>\n";

		// TODO Run cron. Run geostelsel and bezorggebieden update jobs, for instance.

		return Util::log($ret);
	}

	/**
	 * Validate arguments
	 * @param string $destination Destination directory
	 * @param int $count Number of contact entries to create
	 * @return bool Is valid
	 */
	public function validate($destination = '', $count = null) {
		if (empty($destination) || !is_dir($destination)) {
			return drush_set_error('DB_EMPTY', 'TESTENV: No or invalid destination database specified.');
		}
		if (empty($count) || !is_numeric($count) || $count < 0) {
			return drush_set_error('COUNT_INVALID', 'TESTENV: Invalid number of fake contacts specified.');
		}
	}

}