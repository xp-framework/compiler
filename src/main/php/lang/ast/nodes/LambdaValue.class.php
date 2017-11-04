<?php namespace lang\ast\nodes;

class LambdaValue extends Value {
  public $signature, $body;

  public function __construct($signature, $body) {
    $this->signature= $signature;
    $this->body= $body;
  }
}