<?php namespace lang\ast;

class UnionType extends Type {
  public $components;

  /**
   * Creates a new type
   *
   * @param  self[] $components
   */
  public function __construct($components= []) {
    $this->components= $components;
  }

  /** @return string */
  public function literal() { return literal($this->name()); }

  /** @return string */
  public function name() {
    $n= '';
    foreach ($this->components as $type) {
      $n.= '|'.$type->name();
    }
    return substr($n, 1);
  }
}