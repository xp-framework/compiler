<?php namespace lang\ast\nodes;

class InstanceKind extends Kind {
  public $expression, $member;

  public function __construct($expression, $member) {
    $this->expression= $expression;
    $this->member= $member;
  }
}