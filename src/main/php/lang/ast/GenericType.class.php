<?php namespace lang\ast;

class GenericType extends Type {
  public $base, $components;

  /**
   * Creates a new type
   *
   * @param  string $base
   * @param  self[] $components
   */
  public function __construct($base, $components= []) {
    $this->base= $base;
    $this->components= $components;
  }

  /** @return string */
  public function literal() { return literal($this->name()); }

  /** @return string */
  public function name() {
    $n= '';
    foreach ($this->components as $type) {
      $n.= ', '.$type->name();
    }
    return $this->base.'<'.substr($n, 2).'>';
  }
}