<?php namespace lang\ast\nodes;

class ScopeKind extends Kind {
  public $type, $member;

  public function __construct($type, $member) {
    $this->type= $type;
    $this->member= $member;
  }
}