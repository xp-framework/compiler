<?php namespace lang\ast\nodes;

class AssignmentValue extends Value {
  public $variable, $operator, $expression;

  public function __construct($variable, $operator, $expression) {
    $this->variable= $variable;
    $this->operator= $operator;
    $this->expression= $expression;
  }
}