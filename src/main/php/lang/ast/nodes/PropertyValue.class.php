<?php namespace lang\ast\nodes;

class PropertyValue extends Value {
  public $name, $modifiers, $expression, $type, $annotations, $comment;

  public function __construct($name, $modifiers, $expression, $type, $annotations, $comment) {
    $this->name= $name;
    $this->modifiers= $modifiers;
    $this->expression= $expression;
    $this->type= $type;
    $this->annotations= $annotations;
    $this->comment= $comment;
  }
}