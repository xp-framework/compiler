<?php namespace lang\ast\nodes;

class SwitchValue extends Value {
  public $expression, $cases;

  public function __construct($expression, $cases) {
    $this->expression= $expression;
    $this->cases= $cases;
  }
}