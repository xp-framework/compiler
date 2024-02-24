<?php namespace lang\ast\emit;

abstract class Type {
  public static $ENUMS;

  /** @codeCoverageIgnore */
  static function __static() {
    self::$ENUMS= PHP_VERSION_ID >= 80100;
  }

  /** @return string */
  public abstract function name();

  /**
   * Checks whether a given method exists
   *
   * @param  string $named
   * @param  ?int $select
   * @return bool
   */
  public abstract function providesMethod($named, $select= null);

  /**
   * Checks `#[Override]`
   *
   * @param  self $type
   * @return void
   * @throws lang.ast.Error
   */
  public abstract function checkOverrides($type);

  /**
   * Checks `#[Override]` for a given method
   *
   * @param  string $method
   * @param  int $line
   * @return void
   * @throws lang.ast.Error
   */
  public abstract function checkOverride($method, $line);

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public abstract function rewriteEnumCase($member);
}