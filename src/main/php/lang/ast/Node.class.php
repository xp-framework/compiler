<?php namespace lang\ast;

use util\Objects;

class Node implements \lang\Value {
  public $symbol;
  public $value= null, $arity= null, $line= null;

  public function __construct(Symbol $symbol) {
    $this->symbol= $symbol;
  }

  public function nud() {
    return $this->symbol->nud
      ? $this->symbol->nud->__invoke($this)
      : $this
    ;
  }

  public function led($left) {
    return $this->symbol->led
      ? $this->symbol->led->__invoke($this, $left)
      : $this->symbol->error('Missing operator')
    ;
  }

  public function std() {
    return $this->symbol->std ? $this->symbol->std->__invoke($this) : null;
  }

  public function hashCode() {
    return $this->symbol->hashCode().$this->arity.Objects::hashOf($this->value);
  }

  public function toString() {
    return nameof($this).'(arity= '.$this->arity.', value= '.Objects::stringOf($this->value).')';
  }

  public function compareTo($that) {
    return 1; // TBI
  }
}