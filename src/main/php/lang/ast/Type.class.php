<?php namespace lang\ast;

/**
 * Represents a type
 *
 * @test  xp://lang.ast.unittest.TypeTest
 */
class Type implements \lang\Value {
  public $literal;

  /** @param string $literal */
  public function __construct($literal) { $this->literal= $literal; }

  /** @return string */
  public function literal() { return $this->literal; }

  /** @return string */
  public function name() {
    $name= strtr(ltrim($this->literal, '?\\'), '\\', '.');
    return '?' === $this->literal{0} ? '?'.$name : $name;
  }

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