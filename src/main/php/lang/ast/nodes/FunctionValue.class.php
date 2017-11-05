<?php namespace lang\ast\nodes;

class FunctionValue extends Value {
  public $name, $signature, $body;

  public function __construct($name, $signature, $body) {
    $this->name= $name;
    $this->signature= $signature;
    $this->body= $body;
  }
}