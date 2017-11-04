<?php namespace lang\ast\nodes;

class YieldKind extends Kind {
  public $key, $value;

  public function __construct($key, $value) {
    $this->key= $key;
    $this->value= $value;
  }
}