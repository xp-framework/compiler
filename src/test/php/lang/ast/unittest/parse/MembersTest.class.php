<?php namespace lang\ast\unittest\parse;

use lang\ast\Type;

class MembersTest extends ParseTest {

  #[@test]
  public function private_instance_property() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], ['$a' => ['(variable)' => ['a', ['private'], null, null, null, null]]], [], null]]],
      $this->parse('class A { private $a; }')
    );
  }

  #[@test]
  public function private_instance_properties() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        '$a' => ['(variable)' => ['a', ['private'], null, null, null, null]],
        '$b' => ['(variable)' => ['b', ['private'], null, null, null, null]],
      ], [], null]]],
      $this->parse('class A { private $a, $b; }')
    );
  }

  #[@test]
  public function private_instance_method() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['private'], [[], null], null, [], null]]
      ], [], null]]],
      $this->parse('class A { private function a() { } }')
    );
  }

  #[@test]
  public function private_static_method() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['private', 'static'], [[], null], null, [], null]]
      ], [], null]]],
      $this->parse('class A { private static function a() { } }')
    );
  }

  #[@test]
  public function class_constant() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], ['T' => ['const' => ['T', [], ['(literal)' => '1']]]], [], null]]],
      $this->parse('class A { const T = 1; }')
    );
  }

  #[@test]
  public function class_constants() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'T' => ['const' => ['T', [], ['(literal)' => '1']]],
        'S' => ['const' => ['S', [], ['(literal)' => '2']]]
      ], [], null]]],
      $this->parse('class A { const T = 1, S = 2; }')
    );
  }

  #[@test]
  public function private_class_constant() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], ['T' => ['const' => ['T', ['private'], ['(literal)' => '1']]]], [], null]]],
      $this->parse('class A { private const T = 1; }')
    );
  }

  #[@test]
  public function method_with_return_type() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [
        'a()' => ['function' => ['a', ['public'], [[], new Type('void')], null, [], null]]
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
      [['->' => [['(variable)' => 'a'], ['member' => 'member']]]],
      $this->parse('$a->member;')
    );
  }

  #[@test]
  public function dynamic_instance_property_access() {
    $this->assertNodes(
      [['->' => [['(variable)' => 'a'], ['(variable)' => 'member']]]],
      $this->parse('$a->{$member};')
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
      [['(' => [['->' => [['(variable)' => 'a'], ['member' => 'member']]], [['(literal)' => '1']]]]],
      $this->parse('$a->member(1);')
    );
  }

  #[@test]
  public function static_method_invocation() {
    $this->assertNodes(
      [['(' => [['::' => ['\\A', ['member' => 'member']]], [['(literal)' => '1']]]]],
      $this->parse('A::member(1);')
    );
  }
}