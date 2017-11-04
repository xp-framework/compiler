<?php namespace lang\ast\nodes;

class ClassKind extends Kind {
  public $name, $modifiers, $parent, $implements, $body, $annotations, $comment;

  public function __construct($name, $modifiers, $parent, $implements, $body, $annotations, $comment) {
    $this->name= $name;
    $this->modifiers= $modifiers;
    $this->parent= $parent;
    $this->implements= $implements;
    $this->body= $body;
    $this->annotations= $annotations;
    $this->comment= $comment;
  }
}