<?php namespace lang\ast\nodes;

class NewKind extends Kind {
  public $type, $arguments;

  public function __construct($type, $arguments) {
    $this->type= $type;
    $this->arguments= $arguments;
  }
}