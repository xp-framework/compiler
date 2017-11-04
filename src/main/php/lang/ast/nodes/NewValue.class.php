<?php namespace lang\ast\nodes;

class NewValue extends Value {
  public $type, $arguments;

  public function __construct($type, $arguments) {
    $this->type= $type;
    $this->arguments= $arguments;
  }
}