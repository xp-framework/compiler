<?php namespace lang\ast;

use lang\ast\emit\{Reflection, Declaration, Incomplete};

class CodeGen {
  private $id= 0;
  public $scope= [];
  public $source= '(unknown)';

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

  /**
   * Looks up a given type
   *
   * @param  string $type
   * @return lang.ast.emit.Type
   */
  public function lookup($type) {
    $enclosing= $this->scope[0] ?? null;

    if ('self' === $type || 'static' === $type) {
      return new Declaration($enclosing->type, $this);
    } else if ('parent' === $type) {
      return isset($enclosing->type->parent) ? $this->lookup($enclosing->type->parent->literal()) : null;
    }

    foreach ($this->scope as $scope) {
      if ($scope->type->name && $type === $scope->type->name->literal()) {
        return new Declaration($scope->type, $this);
      }
    }

    if (class_exists($type) || interface_exists($type) || trait_exists($type) || enum_exists($type)) {
      return new Reflection($type);
    } else {
      return new Incomplete($type);
    }
  }
}