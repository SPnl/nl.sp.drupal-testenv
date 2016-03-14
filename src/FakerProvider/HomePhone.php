<?php

namespace Testenv\FakerProvider;

class HomePhone extends \Faker\Provider\PhoneNumber {

  protected static $formats = array(
    '010-#######',
    '01#-#######',
    '020-#######',
    '02#-#######',
    '03#-#######',
    '04#-#######',
    '05#-#######',
    '07#-#######',
    '084-#######',
    '088-#######',
  );

  public static function homePhone()
  {
    return static::phoneNumber();
  }
}