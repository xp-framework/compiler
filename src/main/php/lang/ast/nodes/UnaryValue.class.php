<?php namespace lang\ast\nodes;

class UnaryValue extends Value {
  public $expression, $operator;

  public function __construct($expression, $operator) {
    $this->expression= $expression;
    $this->operator= $operator;
  }
}