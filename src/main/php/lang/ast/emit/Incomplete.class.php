<?php namespace lang\ast\emit;

class Incomplete extends Type {
  private $name;

  /** @param string $name */
  public function __construct($name) { $this->name= $name; }

  /** @return string */
  public function name() { return $this->name; }

  /** @return iterable */
  public function implementedInterfaces() { return []; }

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
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public function rewriteEnumCase($member) {
    return false;
  }
}