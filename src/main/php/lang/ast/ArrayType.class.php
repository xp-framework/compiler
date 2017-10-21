<?php namespace lang\ast;

class ArrayType extends Type {
  public $component;

  /**
   * Creates a new type
   *
   * @param  self[] $component
   */
  public function __construct($component= []) {
    $this->component= $component;
  }

  /** @return string */
  public function literal() { return 'array'; }

  /** @return string */
  public function name() { return $this->component->name().'[]'; }
}