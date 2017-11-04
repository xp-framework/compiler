<?php namespace lang\ast\nodes;

class ConstValue extends Value {
  public $name, $modifiers, $expression;

  public function __construct($name, $modifiers, $expression) {
    $this->name= $name;
    $this->modifiers= $modifiers;
    $this->expression= $expression;
  }
}