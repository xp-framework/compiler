<?php namespace lang\ast\nodes;

class InvokeValue extends Value {
  public $expression, $arguments;

  public function __construct($expression, $arguments) {
    $this->expression= $expression;
    $this->arguments= $arguments;
  }
}