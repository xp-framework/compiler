<?php namespace lang\ast\emit;

abstract class Type {
  public static $ENUMS;

  /** @codeCoverageIgnore */
  static function __static() {
    self::$ENUMS= PHP_VERSION_ID >= 80100;
  }

  /** @return string */
  public abstract function name();

  /** @return iterable */
  public abstract function implementedInterfaces();

  /**
   * Checks whether a given method exists
   *
   * @param  string $named
   * @return bool
   */
  public abstract function providesMethod($named);

  /**
   * Returns all methods annotated with a given annotation
   *
   * @param  string $annotation
   * @return iterable
   */
  public abstract function methodsAnnotated($annotation);

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public abstract function rewriteEnumCase($member);
}