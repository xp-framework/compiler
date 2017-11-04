<?php namespace lang\ast\nodes;

class UseKind extends Kind {
  public $types, $aliases;

  public function __construct($types, $aliases) {
    $this->types= $types;
    $this->aliases= $aliases;
  }
}