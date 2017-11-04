<?php namespace lang\ast\nodes;

class WhileKind extends Kind {
  public $expression, $body;

  public function __construct($expression, $body) {
    $this->expression= $expression;
    $this->body= $body;
  }
}