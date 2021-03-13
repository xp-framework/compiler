<?php namespace lang\ast\types;

abstract class Type {
  public static $ENUMS;

  static function __static() {
    self::$ENUMS= class_exists(\ReflectionEnum::class, false); // TODO remove once enum PR is merged
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