<?php namespace lang\ast\types;

interface Type {

  /** @return string */
  public function name();

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @param  bool $native Whether native enum support exists
   * @return bool
   */
  public function rewriteEnumCase($member, $native= false);
}