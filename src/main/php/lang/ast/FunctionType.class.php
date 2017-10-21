<?php namespace lang\ast;

use util\Objects;

class FunctionType implements \lang\Value {
  public $signature, $returns;

  /**
   * Creates a new type
   *
   * @param  self[] $signature
   * @param  self $returns
   */
  public function __construct($signature, $returns) {
    $this->signature= $signature;
    $this->returns= $returns;
  }

  /** @return string */
  public function name() {
    $n= '';
    foreach ($this->signature as $type) {
      $n.= ', '.$type->name();
    }
    return 'function('.substr($n, 2).'): '.$this->returns->name();
  }

  /** @return string */
  public function toString() {
    return $this->name();
  }

  /** @return string */
  public function hashCode() {
    return Objects::hashOf([$this->signature, $this->returs]);
  }

  /**
   * Compare
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare([$this->signature, $this->returs], [$value->signature, $value->returs])
      : 1
    ;
  }
}