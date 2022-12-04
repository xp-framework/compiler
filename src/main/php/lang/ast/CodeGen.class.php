<?php namespace lang\ast;

class CodeGen {
  private $id= 0;
  public $scope= [];

  /** Creates a new, unique symbol */
  public function symbol() { return '_'.($this->id++); }

  public function enter($scope) {
    array_unshift($this->scope, $scope);
    return $scope;
  }

  public function leave() {
    return array_shift($this->scope);
  }

  /**
   * Search a given scope recursively for nodes with a given kind
   *
   * @param  lang.ast.Node $node
   * @param  string $kind
   * @return iterable
   */
  public function search($node, $kind) {
    if ($node->kind === $kind) yield $node;

    foreach ($node->children() as $child) {
      foreach ($this->search($child, $kind) as $result) {
        yield $result;
      }
    }
  }
}