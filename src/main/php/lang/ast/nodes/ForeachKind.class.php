<?php namespace lang\ast\nodes;

class ForeachKind extends Kind {
  public $expression, $key, $value, $body;

  public function __construct($expression, $key, $value, $body) {
    $this->expression= $expression;
    $this->key= $key;
    $this->value= $value;
    $this->body= $body;
  }
}