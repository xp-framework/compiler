<?php namespace lang\ast\nodes;

class InvokeKind extends Kind {
  public $expression, $arguments;

  public function __construct($expression, $arguments) {
    $this->expression= $expression;
    $this->arguments= $arguments;
  }
}