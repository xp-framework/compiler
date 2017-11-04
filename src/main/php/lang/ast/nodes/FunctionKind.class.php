<?php namespace lang\ast\nodes;

class FunctionKind extends Kind {
  public $name, $signature, $body;

  public function __construct($name, $signature, $body) {
    $this->name= $name;
    $this->signature= $signature;
    $this->body= $body;
  }
}