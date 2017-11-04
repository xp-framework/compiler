<?php namespace lang\ast\nodes;

class IfValue extends Value {
  public $expression, $body, $otherwise;

  public function __construct($expression, $body, $otherwise) {
    $this->expression= $expression;
    $this->body= $body;
    $this->otherwise= $otherwise;
  }
}