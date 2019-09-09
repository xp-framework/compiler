<?php namespace lang\ast;

use lang\Value;
use util\Objects;

class Token implements Value {
  public $symbol, $value, $kind;
  public $comment= null;
  public $line= -1;

  /**
   * Creates a new node
   *
   * @param lang.ast.Symbol $symbol
   * @param string $kind
   * @param var $value
   */
  public function __construct(Symbol $symbol= null, $kind= null, $value= null) {
    $this->symbol= $symbol;
    $this->kind= $kind;
    $this->value= $value;
  }

  /** @return string */
  public function hashCode() {
    return $this->symbol->hashCode().$this->kind.Objects::hashOf($this->value);
  }

  /** @return string */
  public function toString() {
    return nameof($this).'(kind= '.$this->kind.', value= '.Objects::stringOf($this->value).')';
  }

  /**
   * Compares this node to another given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value === $this ? 0 : 1;
  }
}