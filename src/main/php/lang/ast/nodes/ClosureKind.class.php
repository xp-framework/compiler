<?php namespace lang\ast\nodes;

class ClosureKind extends Kind {
  public $signature, $use, $body;

  public function __construct($signature, $use, $body) {
    $this->signature= $signature;
    $this->use= $use;
    $this->body= $body;
  }
}