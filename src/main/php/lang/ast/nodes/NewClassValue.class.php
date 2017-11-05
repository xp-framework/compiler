<?php namespace lang\ast\nodes;

class NewClassValue extends Value {
  public $definition, $arguments;

  public function __construct($definition, $arguments) {
    $this->definition= $definition;
    $this->arguments= $arguments;
  }
}