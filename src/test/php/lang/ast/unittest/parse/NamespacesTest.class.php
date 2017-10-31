<?php namespace lang\ast\unittest\parse;

class NamespacesTest extends ParseTest {

  #[@test]
  public function simple_namespace() {
    $this->assertNodes([['namespace' => 'test']], $this->parse('namespace test;'));
  }

  #[@test]
  public function compound_namespace() {
    $this->assertNodes([['namespace' => 'lang\\ast']], $this->parse('namespace lang\ast;'));
  }

  #[@test]
  public function use_statement() {
    $this->assertNodes([['use' => ['lang\ast\Parse' => null]]], $this->parse('use lang\ast\Parse;'));
  }

  #[@test]
  public function use_with_alias() {
    $this->assertNodes([['use' => ['lang\ast\Parse' => 'P']]], $this->parse('use lang\ast\Parse as P;'));
  }

  #[@test]
  public function grouped_use_statement() {
    $this->assertNodes(
      [['use' => ['lang\ast\Parse' => null, 'lang\ast\Emitter' => null]]],
      $this->parse('use lang\ast\{Parse, Emitter};')
    );
  }

  #[@test]
  public function grouped_use_with_alias() {
    $this->assertNodes([['use' => ['lang\ast\Parse' => 'P']]], $this->parse('use lang\ast\{Parse as P};'));
  }
}