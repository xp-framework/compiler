<?php namespace lang\ast\nodes;

class UnaryKind extends Kind {
  public $expression, $operator;

  public function __construct($expression, $operator) {
    $this->expression= $expression;
    $this->operator= $operator;
  }
}