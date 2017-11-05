<?php namespace lang\ast\nodes;

class Signature extends Value {
  public $parameters, $returns;

  public function __construct($parameters, $returns) {
    $this->parameters= $parameters;
    $this->returns= $returns;
  }
}