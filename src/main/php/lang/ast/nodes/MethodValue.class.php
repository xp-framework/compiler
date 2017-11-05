<?php namespace lang\ast\nodes;

class MethodValue extends Value {
  public $name, $modifiers, $signature, $annotations, $body, $comment;

  public function __construct($name, $modifiers, $signature, $annotations, $body, $comment) {
    $this->name= $name;
    $this->modifiers= $modifiers;
    $this->signature= $signature;
    $this->annotations= $annotations;
    $this->body= $body;
    $this->comment= $comment;
  }
}