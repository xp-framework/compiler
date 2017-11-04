<?php namespace lang\ast\nodes;

class ScopeValue extends Value {
  public $type, $member;

  public function __construct($type, $member) {
    $this->type= $type;
    $this->member= $member;
  }
}