<?php namespace lang\ast\nodes;

class ForeachValue extends Value {
  public $expression, $key, $value, $body;

  public function __construct($expression, $key, $value, $body) {
    $this->expression= $expression;
    $this->key= $key;
    $this->value= $value;
    $this->body= $body;
  }
}