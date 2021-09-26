<?php namespace lang\ast\emit;

abstract class Type {
  public static $ENUMS;

  static function __static() {
    self::$ENUMS= PHP_VERSION_ID >= 80100;
  }

  /** @return string */
  public abstract function name();

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public abstract function rewriteEnumCase($member);
}