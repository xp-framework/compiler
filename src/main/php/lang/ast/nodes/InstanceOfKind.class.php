<?php namespace lang\ast\nodes;

class InstanceOfKind extends Kind {
  public $expression, $type;

  public function __construct($expression, $type) {
    $this->expression= $expression;
    $this->type= $type;
  }
}