<?php
namespace Testenv\Command;

/**
 * Class Command\BaseCommand
 * @package Testenv
 */
abstract class BaseCommand {

  /**
   * @var BaseCommand $instance Command instance
   */
  protected static $instance;

  /**
   * Return command instance
   * @return static
   */
  public static function get() {
    if (!static::$instance) {
      static::$instance = new static;
    }
    return static::$instance;
  }

  /**
   * Run command
   * (not defined as abstract because parameters vary)
   */
  public function run() {}

  /**
   * Validate command arguments
   */
  public function validate() {}

}