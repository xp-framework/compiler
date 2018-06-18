<?php namespace lang\ast\unittest\parse;

use lang\ast\Type;

class MembersTest extends ParseTest {

  #[@test]
  public function private_instance_property() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], ['$a' => ['(variable)' => ['a', ['private'], null, null, [], null]]], [], null]]],
      $this->parse('class A { private $a; }')
    );
  }

  #[@test]
  public function private_instance_properties() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        '$a' => ['(variable)' => ['a', ['private'], null, null, [], null]],
        '$b' => ['(variable)' => ['b', ['private'], null, null, [], null]],
      ], [], null]]],
      $this->parse('class A { private $a, $b; }')
    );
  }

  #[@test]
  public function private_instance_method() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['private'], [[], null], [], [], null]]
      ], [], null]]],
      $this->parse('class A { private function a() { } }')
    );
  }

  #[@test]
  public function private_static_method() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['private', 'static'], [[], null], [], [], null]]
      ], [], null]]],
      $this->parse('class A { private static function a() { } }')
    );
  }

  #[@test]
  public function class_constant() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], ['T' => ['const' => ['T', [], ['(literal)' => '1'], null]]], [], null]]],
      $this->parse('class A { const T = 1; }')
    );
  }

  #[@test]
  public function class_constants() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'T' => ['const' => ['T', [], ['(literal)' => '1'], null]],
        'S' => ['const' => ['S', [], ['(literal)' => '2'], null]]
      ], [], null]]],
      $this->parse('class A { const T = 1, S = 2; }')
    );
  }

  #[@test]
  public function private_class_constant() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], ['T' => ['const' => ['T', ['private'], ['(literal)' => '1'], null]]], [], null]]],
      $this->parse('class A { private const T = 1; }')
    );
  }

  #[@test]
  public function method_with_return_type() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['public'], [[], new Type('void')], [], [], null]]
      ], [], null]]],
      $this->parse('class A { public function a(): void { } }')
    );
  }

  #[@test]
  public function method_with_annotation() {
    $annotations= ['test' => null];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['public'], [[], null], $annotations, [], null]]
      ], [], null]]],
      $this->parse('class A { <<test>> public function a() { } }')
    );
  }

  #[@test]
  public function method_with_annotations() {
    $annotations= ['test' => null, 'ignore' => ['(literal)' => '"Not implemented"']];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['public'], [[], null], $annotations, [], null]]
      ], [], null]]],
      $this->parse('class A { <<test, ignore("Not implemented")>> public function a() { } }')
    );
  }

  #[@test]
  public function instance_property_access() {
    $this->assertNodes(
      [['->' => [['(variable)' => 'a'], ['(name)' => 'member']]]],
      $this->parse('$a->member;')
    );
  }

  #[@test]
  public function dynamic_instance_property_access_via_variable() {
    $this->assertNodes(
      [['->' => [['(variable)' => 'a'], ['(variable)' => 'member']]]],
      $this->parse('$a->{$member};')
    );
  }

  #[@test]
  public function dynamic_instance_property_access_via_expression() {
    $this->assertNodes(
      [['->' => [['(variable)' => 'a'], ['(' => [
        ['->' => [['(variable)' => 'field'], ['(name)' => 'get']]],
        [['(variable)' => 'instance']]
      ]]]]],
      $this->parse('$a->{$field->get($instance)};')
    );
  }

  #[@test]
  public function static_property_access() {
    $this->assertNodes(
      [['::' => ['\\A', ['(variable)' => 'member']]]],
      $this->parse('A::$member;')
    );
  }

  #[@test, @values(['self', 'parent', 'static'])]
  public function scope_resolution($scope) {
    $this->assertNodes(
      [['::' => [$scope, ['class' => 'class']]]],
      $this->parse($scope.'::class;')
    );
  }

  #[@test]
  public function class_resolution() {
    $this->assertNodes(
      [['::' => ['\\A', ['class' => 'class']]]],
      $this->parse('A::class;')
    );
  }

  #[@test]
  public function instance_method_invocation() {
    $this->assertNodes(
      [['(' => [['->' => [['(variable)' => 'a'], ['(name)' => 'member']]], [['(literal)' => '1']]]]],
      $this->parse('$a->member(1);')
    );
  }

  #[@test]
  public function static_method_invocation() {
    $this->assertNodes(
      [['(' => [['::' => ['\\A', ['(name)' => 'member']]], [['(literal)' => '1']]]]],
      $this->parse('A::member(1);')
    );
  }

  #[@test]
  public function typed_property() {
    $decl= ['$a' => ['(variable)' => ['a', ['private'], null, new Type('string'), [], null]]];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], $decl, [], null]]],
      $this->parse('class A { private string $a; }')
    );
  }

  #[@test]
  public function typed_property_with_value() {
    $decl= ['$a' => ['(variable)' => ['a', ['private'], ['(literal)' => '"test"'], new Type('string'), [], null]]];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], $decl, [], null]]],
      $this->parse('class A { private string $a = "test"; }')
    );
  }

  #[@test]
  public function typed_properties() {
    $decl= [
      '$a' => ['(variable)' => ['a', ['private'], null, new Type('string'), [], null]],
      '$b' => ['(variable)' => ['b', ['private'], null, new Type('string'), [], null]],
      '$c' => ['(variable)' => ['c', ['private'], null, new Type('int'), [], null]],
    ];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], $decl, [], null]]],
      $this->parse('class A { private string $a, $b, int $c; }')
    );
  }

  #[@test]
  public function typed_constant() {
    $decl= ['T' => ['const' => ['T', [], ['(literal)' => '1'], new Type('int')]]];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], $decl, [], null]]],
      $this->parse('class A { const int T = 1; }')
    );
  }

  #[@test]
  public function typed_constants() {
    $decl= [
      'T' => ['const' => ['T', [], ['(literal)' => '1'], new Type('int')]],
      'S' => ['const' => ['S', [], ['(literal)' => '2'], new Type('int')]],
      'I' => ['const' => ['I', [], ['(literal)' => '"i"'], new Type('string')]],
    ];
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], $decl, [], null]]],
      $this->parse('class A { const int T = 1, S = 2, string I = "i"; }')
    );
  }
}