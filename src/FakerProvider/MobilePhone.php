<?php

namespace Testenv\FakerProvider;

class MobilePhone extends \Faker\Provider\PhoneNumber
{
  protected static $formats = array(
    '06-########',
  );

  public static function mobilePhone()
  {
    return static::phoneNumber();
  }

}
