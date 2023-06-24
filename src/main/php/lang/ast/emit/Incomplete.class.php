<?php namespace lang\ast\emit;

class Incomplete extends Type {
  private $name;

  /** @param string $name */
  public function __construct($name) { $this->name= $name; }

  /** @return string */
  public function name() { return $this->name; }

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public function rewriteEnumCase($member) {
    return false;
  }

  /**
   * Returns the (fully qualified) default implementations' trait name for
   * interfaces, or `null`.
   *
   * @return ?string
   */
  public function defaultImplementations() {
    return null;
  }
}