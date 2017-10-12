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
    $result= nameof($this).'(symbol= `'.$this->symbol->id.'`, arity= '.$this->arity;
    if ($this->value instanceof self) {
      return $result.")@{\n  ".str_replace("\n", "\n  ", $this->value->toString())."\n}";
    } else if (is_array($this->value)) {
      $list= implode("\n", array_map([Objects::class, 'stringOf'], $this->value));
      return $result.")@{\n  ".str_replace("\n", "\n  ", $list)."\n}";
    } else {
      return $result.', value= '.Objects::stringOf($this->value).')';
    }
  }

  public function compareTo($that) {
    return 1; // TBI
  }
}