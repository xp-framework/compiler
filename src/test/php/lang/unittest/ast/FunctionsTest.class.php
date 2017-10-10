<?php namespace lang\unittest\ast;

class FunctionsTest extends ParseTest {

  #[@test]
  public function empty_function_without_parameters() {
    $this->assertNodes(
      [['(' => ['a', [], [], [], null]]],
      $this->parse('function a() { }')
    );
  }

  #[@test]
  public function two_functions() {
    $this->assertNodes(
      [['(' => ['a', [], [], [], null]], ['(' => ['b', [], [], [], null]]],
      $this->parse('function a() { } function b() { }')
    );
  }

  #[@test]
  public function with_parameter() {
    $this->assertNodes(
      [['(' => ['a', [], [['param', null, false, null, null]], [], null]]],
      $this->parse('function a($param) { }')
    );
  }

  #[@test]
  public function with_typed_parameter() {
    $this->assertNodes(
      [['(' => ['a', [], [['param', 'string', false, null, null]], [], null]]],
      $this->parse('function a(string $param) { }')
    );
  }

  #[@test]
  public function with_variadic_parameter() {
    $this->assertNodes(
      [['(' => ['a', [], [['param', null, true, null, null]], [], null]]],
      $this->parse('function a(... $param) { }')
    );
  }

  #[@test]
  public function with_optional_parameter() {
    $this->assertNodes(
      [['(' => ['a', [], [['param', null, false, null, ['null' => null]]], [], null]]],
      $this->parse('function a($param= null) { }')
    );
  }

  #[@test]
  public function with_return_type() {
    $this->assertNodes(
      [['(' => ['a', [], [], [], 'void']]],
      $this->parse('function a(): void { }')
    );
  }

  #[@test]
  public function default_closure() {
    $block= ['+' => [['(variable)' => 'a'], ['(literal)' => 1]]];
    $this->assertNodes(
      [['(' => [null, [], [['a', null, false, null, null]], [['return' => $block]], null]]],
      $this->parse('function($a) { return $a + 1; };')
    );
  }

  #[@test]
  public function short_closure() {
    $block= ['+' => [['(variable)' => 'a'], ['(literal)' => 1]]];
    $this->assertNodes(
      [['(' => [null, [], [['a', null, false, null, null]], [['==>' => $block]], null]]],
      $this->parse('($a) ==> $a + 1;')
    );
  }
}