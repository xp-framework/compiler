<?php namespace lang\ast\nodes;

class OffsetValue extends Value {
  public $expression, $offset;

  public function __construct($expression, $offset) {
    $this->expression= $expression;
    $this->offset= $offset;
  }
}