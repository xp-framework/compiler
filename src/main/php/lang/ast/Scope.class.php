<?php namespace lang\ast;

/**
 * Scope
 *
 * @test  xp://lang.unittest.ast.ScopeTest
 */
class Scope {
  private static $reserved= [
    'string'   => true,
    'int'      => true,
    'float'    => true,
    'bool'     => true,
    'array'    => true,
    'callable' => true,
    'iterable' => true,
    'object'   => true,
    'self'     => true,
    'static'   => true,
    'parent'   => true
  ];

  public $parent, $package;
  public $imports= [];

  public function __construct(self $parent= null) {
    $this->parent= $parent;
  }

  public function define($name, $node) {
    $definition= clone $node;
    $definition->symbol->nud= function($self) { return $self; };
    $definition->symbol->led= null;
    $definition->symbol->std= null;
    $definition->symbol->lbp= 0;
    $this->defines[$name]= $definition;
  }

  /**
   * Sets package
   *
   * @param  string $name
   * @return void
   */
  public function package($name) {
    $this->package= '\\'.$name;
  }

  /**
   * Adds an import
   *
   * @param  string $name
   * @return void
   */
  public function import($name) {
    $this->imports[substr($name, strrpos($name,  '\\') + 1)]= '\\'.$name;
  }

  /**
   * Resolves a type
   *
   * @param  string $name
   * @return string
   */
  public function resolve($name) {
    if (isset(self::$reserved[$name])) {
      return $name;
    } else if ('\\' === $name{0}) {
      return $name;
    } else if (isset($this->imports[$name])) {
      return $this->imports[$name];
    } else if ($this->package) {
      return $this->package.'\\'.$name;
    } else if ($this->parent) {
      return $this->parent->resolve($name);
    } else {
      return $name;
    }
  }

  public function find($name) {
    return isset($this->defines[$name]) ? $this->defines[$name] : null;
  }
}