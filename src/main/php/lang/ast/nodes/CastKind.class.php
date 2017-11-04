<?php namespace lang\ast\nodes;

class CastKind extends Kind {
  public $type, $expression;

  public function __construct($type, $expression) {
    $this->type= $type;
    $this->expression= $expression;
  }
}