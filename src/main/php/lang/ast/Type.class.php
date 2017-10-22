<?php namespace lang\ast;

class Type implements \lang\Value {
  public $literal;

  /** @param string $literal */
  public function __construct($literal) { $this->literal= $literal; }

  /** @return string */
  public function literal() { return $this->literal; }

  /** @return string */
  public function name() { return strtr(ltrim($this->literal, '\\'), '\\', '.'); }

  /** @return string */
  public function toString() { return nameof($this).'('.$this->name().')'; }

  /** @return string */
  public function hashCode() { return crc32($this->name()); }

  /**
   * Compare
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? strcmp($this->name(), $value->name()) : 1;
  }
}