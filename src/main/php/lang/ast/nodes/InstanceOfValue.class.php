<?php namespace lang\ast\nodes;

class InstanceOfValue extends Value {
  public $expression, $type;

  public function __construct($expression, $type) {
    $this->expression= $expression;
    $this->type= $type;
  }
}