<?php namespace lang\ast;

class FunctionType extends Type {
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
  public function literal() { return 'callable'; }

  /** @return string */
  public function name() {
    $n= '';
    foreach ($this->signature as $type) {
      $n.= ', '.$type->name();
    }
    return 'function('.substr($n, 2).'): '.$this->returns->name();
  }
}