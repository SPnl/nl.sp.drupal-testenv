<?php
namespace Testenv\Command;

/**
 * Class Base
 * @package Testenv
 */
abstract class Base {

	/**
	 * @var Base $instance Command instance
	 */
	protected static $instance;

	/**
	 * Return command instance
	 * @return static
	 */
	public static function get() {
		if(!static::$instance) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	/**
	 * Run command
	 */
	public function run() {
	}

	/**
	 * Validate command arguments
	 */
	public function validate() {
	}
}