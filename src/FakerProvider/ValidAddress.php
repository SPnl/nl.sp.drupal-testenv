<?php

namespace Testenv\FakerProvider;

class ValidAddress extends \Faker\Provider\Base {

  private $pcrow = [];

  public function clearAddress() {
    $this->pcrow = [];
  }

  public function initializeAddress($postcode = NULL, $street_number = NULL) {
    if (empty($this->pcrow)) {

      if ($postcode && $street_number) {
        try {
          // Fetch a specific row from the postcode database - used to replace an existing address with another address within the same postcode range
          $postcode = preg_replace('/[^\da-z]/i', '', $postcode);
          $postcode_4pp = substr($postcode, 0, 4);
          $postcode_2pp = substr($postcode, 4, 2);
          $dao = \CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_postcodenl WHERE postcode_nr = '" . $postcode_4pp . "' AND postcode_letter = '" . $postcode_2pp . "' AND (even = '" . (int)($street_number % 2 == 0) . "' XOR adres LIKE 'Postbus') AND ((" . (int)$street_number . " BETWEEN huisnummer_van AND huisnummer_tot) XOR adres LIKE 'Postbus')");
          $this->pcrow = $dao->getDatabaseResult()->fetchRow(DB_DATAOBJECT_FETCHMODE_ASSOC);
          return;
        } catch (\Exception $e) {
          // Return random row instead
        }
      }

      // Fetch a random row from the postcode database. ORDER BY RAND() LIMIT 1 takes ten seconds!
      // Borrowed this solution from http://stackoverflow.com/questions/4329396
      $dao = \CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_postcodenl AS r1 JOIN (SELECT (RAND() * (SELECT MAX(id) FROM civicrm_postcodenl)) AS id) AS r2 WHERE r1.id >= r2.id ORDER BY r1.id ASC LIMIT 1;");
      $this->pcrow = $dao->getDatabaseResult()->fetchRow(DB_DATAOBJECT_FETCHMODE_ASSOC);
    }
  }

  public function pcrow() {
    $this->initializeAddress();
    return $this->pcrow;
  }

  public function fullAddress() {
    $this->initializeAddress();
    if (empty($this->pcrow['adres_compleet'])) {
      $this->pcrow['adres_compleet'] = $this->streetName() . " " . $this->streetNumber() . ($this->streetUnit() ? "-" . $this->streetUnit() : "");
    }

    return $this->pcrow['adres_compleet'];
  }

  public function streetName() {
    $this->initializeAddress();
    return $this->pcrow['adres'];
  }

  public function buildingNumber() {
    $this->initializeAddress();
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
    $this->initializeAddress();
    if (!isset($this->pcrow['huisnummer_rest'])) {
      $this->pcrow['huisnummer_rest'] = $this->optional($weight = 0.4)->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'I', 'II', 'III', 'A1', 'A2', 'A3', 'HS']);
      if(!$this->pcrow['huisnummer_rest']) {
        $this->pcrow['huisnummer_rest'] = '';
      }
    }

    return $this->pcrow['huisnummer_rest'];
  }

  public function postcode() {
    $this->initializeAddress();
    return $this->pcrow['postcode_nr'] . ' ' . $this->pcrow['postcode_letter'];
  }

  public function city() {
    $this->initializeAddress();
    return $this->pcrow['woonplaats'];
  }

  public function cbs_wijkcode() {
    $this->initializeAddress();
    return $this->pcrow['cbs_wijkcode'];
  }

  public function cbs_buurtcode() {
    $this->initializeAddress();
    return $this->pcrow['cbs_buurtcode'];
  }

  public function cbs_buurtnaam() {
    $this->initializeAddress();
    return $this->pcrow['cbs_buurtnaam'];
  }

  public function gemeente() {
    $this->initializeAddress();
    return $this->pcrow['gemeente'];
  }

  public function provincie() {
    $this->initializeAddress();
    return $this->pcrow['provincie'];
  }

  public function country() {
    return 'Nederland';
  }

  public function countryId() {
    // Nederland = hardcoded... of anders iets met CRM_Core_PseudoConstant::country()
    return 1152;
  }

  public function latitude() {
    $this->initializeAddress();
    return $this->pcrow['latitude'];
  }

  public function longitude() {
    $this->initializeAddress();
    return $this->pcrow['longitude'];
  }

}
