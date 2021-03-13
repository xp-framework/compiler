<?php namespace lang\ast\emit;

abstract class Type {
  public static $ENUMS;

  static function __static() {

    // TODO: Check PHP version ID once enum PR is merged
    self::$ENUMS= class_exists(\ReflectionEnum::class, false);
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