<?php namespace lang\ast\nodes;

class BinaryValue extends Value {
  public $left, $operator, $right;

  public function __construct($left, $operator, $right) {
    $this->left= $left;
    $this->operator= $operator;
    $this->right= $right;
  }
}