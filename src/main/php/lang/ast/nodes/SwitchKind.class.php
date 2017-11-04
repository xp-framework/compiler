<?php namespace lang\ast\nodes;

class SwitchKind extends Kind {
  public $expression, $cases;

  public function __construct($expression, $cases) {
    $this->expression= $expression;
    $this->cases= $cases;
  }
}