<?php namespace lang\ast\nodes;

class LambdaKind extends Kind {
  public $signature, $body;

  public function __construct($signature, $body) {
    $this->signature= $signature;
    $this->body= $body;
  }
}