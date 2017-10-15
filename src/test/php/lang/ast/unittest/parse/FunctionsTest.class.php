<?php namespace lang\ast\unittest\parse;

class FunctionsTest extends ParseTest {

  #[@test]
  public function empty_function_without_parameters() {
    $this->assertNodes(
      [['function' => ['a', [[], null], []]]],
      $this->parse('function a() { }')
    );
  }

  #[@test]
  public function two_functions() {
    $this->assertNodes(
      [['function' => ['a', [[], null], []]], ['function' => ['b', [[], null], []]]],
      $this->parse('function a() { } function b() { }')
    );
  }

  #[@test, @values(['param', 'protected'])]
  public function with_parameter($name) {
    $this->assertNodes(
      [['function' => ['a', [[[$name, false, null, false, null, null]], null], []]]],
      $this->parse('function a($'.$name.') { }')
    );
  }

  #[@test]
  public function with_reference_parameter() {
    $this->assertNodes(
      [['function' => ['a', [[['param', true, null, false, null, null]], null], []]]],
      $this->parse('function a(&$param) { }')
    );
  }

  #[@test]
  public function dangling_comma_in_parameter_lists() {
    $this->assertNodes(
      [['function' => ['a', [[['param', false, null, false, null, null]], null], []]]],
      $this->parse('function a($param, ) { }')
    );
  }

  #[@test]
  public function with_typed_parameter() {
    $this->assertNodes(
      [['function' => ['a', [[['param', false, 'string', false, null, null]], null], []]]],
      $this->parse('function a(string $param) { }')
    );
  }

  #[@test]
  public function with_nullable_typed_parameter() {
    $this->assertNodes(
      [['function' => ['a', [[['param', false, '?string', false, null, null]], null], []]]],
      $this->parse('function a(?string $param) { }')
    );
  }

  #[@test]
  public function with_variadic_parameter() {
    $this->assertNodes(
      [['function' => ['a', [[['param', false, null, true, null, null]], null], []]]],
      $this->parse('function a(... $param) { }')
    );
  }

  #[@test]
  public function with_optional_parameter() {
    $this->assertNodes(
      [['function' => ['a', [[['param', false, null, false, null, ['null' => 'null']]], null], []]]],
      $this->parse('function a($param= null) { }')
    );
  }

  #[@test]
  public function with_return_type() {
    $this->assertNodes(
      [['function' => ['a', [[], 'void'], []]]],
      $this->parse('function a(): void { }')
    );
  }

  #[@test]
  public function default_closure() {
    $block= ['+' => [['(variable)' => 'a'], ['(literal)' => '1']]];
    $this->assertNodes(
      [['function' => [[[], null], null, [['return' => $block]]]]],
      $this->parse('function() { return $a + 1; };')
    );
  }

  #[@test]
  public function default_closure_with_use_by_value() {
    $block= ['+' => [['(variable)' => 'a'], ['(literal)' => '1']]];
    $this->assertNodes(
      [['function' => [[[], null], ['$a', '$b'], [['return' => $block]]]]],
      $this->parse('function() use($a, $b) { return $a + 1; };')
    );
  }

  #[@test]
  public function default_closure_with_use_by_reference() {
    $block= ['+' => [['(variable)' => 'a'], ['(literal)' => '1']]];
    $this->assertNodes(
      [['function' => [[[], null], ['$a', '&$b'], [['return' => $block]]]]],
      $this->parse('function() use($a, &$b) { return $a + 1; };')
    );
  }

  #[@test]
  public function generator() {
    $statement= ['yield' => [null, null]];
    $this->assertNodes(
      [['function' => ['a', [[], null], [$statement]]]],
      $this->parse('function a() { yield; }')
    );
  }

  #[@test]
  public function generator_with_value() {
    $statement= ['yield' => [null, ['(literal)' => '1']]];
    $this->assertNodes(
      [['function' => ['a', [[], null], [$statement]]]],
      $this->parse('function a() { yield 1; }')
    );
  }

  #[@test]
  public function generator_with_key_and_value() {
    $statement= ['yield' => [['(literal)' => '"number"'], ['(literal)' => '1']]];
    $this->assertNodes(
      [['function' => ['a', [[], null], [$statement]]]],
      $this->parse('function a() { yield "number" => 1; }')
    );
  }

  #[@test]
  public function generator_delegation() {
    $statement= ['yield' => ['[' => []]];
    $this->assertNodes(
      [['function' => ['a', [[], null], [$statement]]]],
      $this->parse('function a() { yield from []; }')
    );
  }
}