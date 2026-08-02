<?php namespace lang\ast\emit;

class Local {
  public $type, $byRef;

  public function __construct($type, $byRef= false) {
    $this->type= $type;
    $this->byRef= $byRef;
  }
}