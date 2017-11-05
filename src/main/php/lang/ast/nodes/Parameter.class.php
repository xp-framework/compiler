<?php namespace lang\ast\nodes;

class Parameter extends Value {
  public $name, $reference, $type, $variadic, $promote, $default, $annotations;

  public function __construct($name, $reference, $type, $variadic, $promote, $default, $annotations) {
    $this->name= $name;
    $this->reference= $reference;
    $this->type= $type;
    $this->variadic= $variadic;
    $this->promote= $promote;
    $this->default= $default;
    $this->annotations= $annotations;
  }
}