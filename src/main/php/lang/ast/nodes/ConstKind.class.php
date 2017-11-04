<?php namespace lang\ast\nodes;

class ConstKind extends Kind {
  public $name, $modifiers, $expression;

  public function __construct($name, $modifiers, $expression) {
    $this->name= $name;
    $this->modifiers= $modifiers;
    $this->expression= $expression;
  }
}