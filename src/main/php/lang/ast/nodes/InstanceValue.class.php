<?php namespace lang\ast\nodes;

class InstanceValue extends Value {
  public $expression, $member;

  public function __construct($expression, $member) {
    $this->expression= $expression;
    $this->member= $member;
  }
}