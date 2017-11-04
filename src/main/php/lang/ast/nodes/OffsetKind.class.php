<?php namespace lang\ast\nodes;

class OffsetKind extends Kind {
  public $expression, $offset;

  public function __construct($expression, $offset) {
    $this->expression= $expression;
    $this->offset= $offset;
  }
}