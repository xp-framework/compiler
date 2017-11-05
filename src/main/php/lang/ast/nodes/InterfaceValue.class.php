<?php namespace lang\ast\nodes;

class InterfaceValue extends Value {
  public $name, $modifiers, $parents, $body, $annotations, $comment;

  public function __construct($name, $modifiers, $parents, $body, $annotations, $comment) {
    $this->name= $name;
    $this->modifiers= $modifiers;
    $this->parents= $parents;
    $this->body= $body;
    $this->annotations= $annotations;
    $this->comment= $comment;
  }
}