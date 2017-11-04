<?php namespace lang\ast\nodes;

class BinaryKind extends Kind {
  public $left, $operator, $right;

  public function __construct($left, $operator, $right) {
    $this->left= $left;
    $this->operator= $operator;
    $this->right= $right;
  }
}