<?php namespace lang\ast\nodes;

class TraitValue extends Value {
  public $name, $modifiers, $body, $annotations, $comment;

  public function __construct($name, $modifiers, $body, $annotations, $comment) {
    $this->name= $name;
    $this->modifiers= $modifiers;
    $this->body= $body;
    $this->annotations= $annotations;
    $this->comment= $comment;
  }
}