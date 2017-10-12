<?php namespace lang\ast\unittest;

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
    $this->assertNodes([['use' => 'use']], $this->parse('use lang\ast\Parse;'));
  }

  #[@test]
  public function grouped_use_statement() {
    $this->assertNodes([['use' => 'use']], $this->parse('use lang\ast\{Parse, Emitter};'));
  }
}