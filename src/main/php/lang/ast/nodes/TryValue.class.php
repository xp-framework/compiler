<?php namespace lang\ast\nodes;

class TryValue extends Value {
  public $body, $catches, $finally;

  public function __construct($body, $catches, $finally) {
    $this->body= $body;
    $this->catches= $catches;
    $this->finally= $finally;
  }
}