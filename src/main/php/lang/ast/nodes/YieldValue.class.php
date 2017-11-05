<?php namespace lang\ast\nodes;

class YieldValue extends Value {
  public $key, $value;

  public function __construct($key, $value) {
    $this->key= $key;
    $this->value= $value;
  }
}