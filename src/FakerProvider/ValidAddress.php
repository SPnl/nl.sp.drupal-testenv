<?php

namespace Testenv\FakerProvider;

class ValidAddress extends \Faker\Provider\Base {

  private $pcrow;

  public function initialize() {
    if (empty($this->pcrow)) {
      // Fetch a random row from the postcode database
      $dao = \CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_postcodenl ORDER BY RAND() LIMIT 1");
      $this->pcrow = $dao->getDatabaseResult()->fetchRow(DB_DATAOBJECT_FETCHMODE_ASSOC);
    }
  }
  
  public function pcrow() {
    $this->initialize();
    return $this->pcrow;
  }

  public function fullAddress() {
    $this->initialize();
    if (empty($this->pcrow['adres_compleet'])) {
      $this->pcrow['adres_compleet'] = $this->streetName() . " " . $this->streetNumber() . ($this->streetUnit() ? "-" . $this->streetUnit() : "") . "\n" .
                               $this->postcode() . ' ' . $this->city();
    }

    return $this->pcrow['adres_compleet'];
  }

  public function streetName() {
    $this->initialize();
    return $this->pcrow['adres'];
  }

  public function buildingNumber() {
    $this->initialize();
    if (empty($this->pcrow['huisnummer'])) {
      $this->pcrow['huisnummer'] = $this->buildingNumberBetween($this->pcrow['huisnummer_van'], $this->pcrow['huisnummer_tot'], $this->pcrow['even']);
    }

    return $this->pcrow['huisnummer'];
  }

  private function buildingNumberBetween($min, $max, $even = TRUE) {
    $rand = mt_rand($min, $max);
    if ($even) {
      return $rand & ~1;
    } else {
      return $rand | 1;
    }
  }

  public function streetNumber() {
    return $this->buildingNumber();
  }

  public function streetUnit() {
    $this->initialize();
    if (!isset($this->pcrow['huisnummer_rest'])) {
      $this->pcrow['huisnummer_rest'] = $this->optional($weight = 0.4)->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'II', 'III', 'HS']);
    }

    return $this->pcrow['huisnummer_rest'];
  }

  public function postcode() {
    $this->initialize();
    return $this->pcrow['postcode_nr'] . ' ' . $this->pcrow['postcode_letter'];
  }

  public function city() {
    $this->initialize();
    return $this->pcrow['woonplaats'];
  }

  public function gemeente() {
    $this->initialize();
    return $this->pcrow['gemeente'];
  }

  public function provincie() {
    $this->initialize();
    return $this->pcrow['provincie'];
  }

  public function country() {
    return 'Nederland';
  }

}
