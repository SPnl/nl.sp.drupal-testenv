<?php

namespace Testenv\FakerProvider;

class Person extends \Faker\Provider\nl_NL\Person {

  const GENDER_FEMALE = 1;
  const GENDER_MALE = 2;
  const GENDER_OTHER = 3;

  private $myGender;
  private $myFirstName;
  private $myLastName;

  public function clearPerson() {
    $this->myGender = NULL;
    $this->myFirstName = NULL;
    $this->myLastName = NULL;
  }

  public function gender() {
    if (empty($this->myGender)) {
      $this->myGender = $this->generator->biasedNumberBetween(1, 3, 'sqrt');
    }

    return $this->myGender;
  }

  public function firstName($gender = NULL) {
    if (!empty($this->myFirstName)) {
      return $this->myFirstName;
    }

    if (empty($this->myGender)) {
      if ($gender) {
        $this->myGender = $gender;
      } else {
        $this->myGender = $this->gender();
      }
    }

    switch ($this->myGender) {
      case static::GENDER_FEMALE:
        $this->myFirstName = $this->firstNameFemale();
        break;
      case static::GENDER_MALE;
        $this->myFirstName = $this->firstNameMale();
        break;
      case static::GENDER_OTHER;
        // Hier zit natuurlijk die vervloekte segmentation error. !#)$*(!@$)(*!@
        $this->myFirstName = parent::firstName();
        break;
    }

    return $this->myFirstName;
  }

  public function lastName() {
    if (empty($this->myLastName)) {
      $this->myLastName = parent::lastName();
    }

    return $this->myLastName;
  }

  public function name($gender = NULL) {
    return $this->firstName($gender) . ' ' . $this->lastName();
  }

  public function sortName($gender = NULL) {
    return $this->lastName() . ', ' . $this->firstName($gender);
  }

  public function initials($gender = NULL) {
    $firstname = $this->firstName($gender);
    return strtoupper(substr($firstname, 0, 1));
  }

  public function contactAddressee($gender = NULL) {
    return $this->initials($gender) . ' ' . $this->lastName();
  }

  public function contactGreeting($gender = NULL) {
    return 'Beste ' . $this->firstName($gender);
  }

}
