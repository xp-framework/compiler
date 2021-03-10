<?php namespace lang\ast\types;

interface Type {

  /** @return string */
  public function name();

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public function isEnumCase($member);
}