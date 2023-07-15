<?php namespace lang\ast\emit;

class Incomplete extends Type {
  private $name;

  /** @param string $name */
  public function __construct($name) { $this->name= $name; }

  /** @return string */
  public function name() { return $this->name; }

  /**
   * Checks whether a given method exists
   *
   * @param  string $named
   * @return bool
   */
  public function providesMethod($named) {
    return false;
  }

  /**
   * Checks `#[Override]`
   *
   * @param  lang.ast.emit.Type $type
   * @return void
   * @throws lang.ast.Error
   */
  public function checkOverrides($type) {
    // NOOP
  }

  /**
   * Checks `#[Override]` for a given method
   *
   * @param  string $method
   * @param  int $line
   * @return void
   * @throws lang.ast.Error
   */
  public function checkOverride($method, $line) {
    // NOOP
  }

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public function rewriteEnumCase($member) {
    return false;
  }
}