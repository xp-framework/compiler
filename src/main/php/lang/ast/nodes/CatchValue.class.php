<?php namespace lang\ast\nodes;

class CatchValue extends Value {
  public $types, $variable, $body;

  public function __construct($types, $variable, $body) {
    $this->types= $types;
    $this->variable= $variable;
    $this->body= $body;
  }
}