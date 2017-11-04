<?php namespace lang\ast\nodes;

class CatchKind extends Kind {
  public $types, $variable, $body;

  public function __construct($types, $variable, $body) {
    $this->types= $types;
    $this->variable= $variable;
    $this->body= $body;
  }
}