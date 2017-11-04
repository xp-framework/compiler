<?php namespace lang\ast\nodes;

class DoKind extends Kind {
  public $expression, $body;

  public function __construct($expression, $body) {
    $this->expression= $expression;
    $this->body= $body;
  }
}