<?php namespace lang\ast\unittest;

class MembersTest extends ParseTest {

  #[@test]
  public function private_instance_property() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['(variable)' => ['a', ['private'], null, null, null]]], []]]],
      $this->parse('class A { private $a; }')
    );
  }

  #[@test]
  public function private_instance_properties() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [
        ['(variable)' => ['a', ['private'], null, null, null]],
        ['(variable)' => ['b', ['private'], null, null, null]],
      ], []]]],
      $this->parse('class A { private $a, $b; }')
    );
  }

  #[@test]
  public function private_instance_method() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['(' => ['a', ['private'], [], [], null, null, null]]], []]]],
      $this->parse('class A { private function a() { } }')
    );
  }

  #[@test]
  public function private_static_method() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['(' => ['a', ['private', 'static'], [], [], null, null, null]]], []]]],
      $this->parse('class A { private static function a() { } }')
    );
  }

  #[@test]
  public function class_constant() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['const' => ['T', [], ['(literal)' => '1']]]], []]]],
      $this->parse('class A { const T = 1; }')
    );
  }

  #[@test]
  public function class_constants() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [
        ['const' => ['T', [], ['(literal)' => '1']]],
        ['const' => ['S', [], ['(literal)' => '2']]]
      ], []]]],
      $this->parse('class A { const T = 1, S = 2; }')
    );
  }

  #[@test]
  public function private_class_constant() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['const' => ['T', ['private'], ['(literal)' => '1']]]], []]]],
      $this->parse('class A { private const T = 1; }')
    );
  }

  #[@test]
  public function short_method() {
    $block= [['==>' => ['true' => 'true']]];
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['(' => ['a', ['public'], [], $block, null, null, null]]], []]]],
      $this->parse('class A { public function a() ==> true; }')
    );
  }

  #[@test]
  public function method_with_return_type() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['(' => ['a', ['public'], [], [], 'void', null, null]]], []]]],
      $this->parse('class A { public function a(): void { } }')
    );
  }

  #[@test]
  public function method_with_annotation() {
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['(' => ['a', ['public'], [], [], null, null, [['test']]]]], []]]],
      $this->parse('class A { <<test>> public function a() { } }')
    );
  }

  #[@test]
  public function method_with_annotations() {
    $annotations= [['test'], ['ignore', [['(literal)' => '"Not implemented"']]]];
    $this->assertNodes(
      [['class' => ['A', [], null, [], [['(' => ['a', ['public'], [], [], null, null, $annotations]]], []]]],
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
      [['::' => ['A', ['(variable)' => 'member']]]],
      $this->parse('A::$member;')
    );
  }

  #[@test, @values(['self', 'parent', 'static', 'A'])]
  public function class_resolution($scope) {
    $this->assertNodes(
      [['::' => [$scope, ['class' => 'class']]]],
      $this->parse($scope.'::class;')
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
      [['(' => [['::' => ['A', ['member' => 'member']]], [['(literal)' => '1']]]]],
      $this->parse('A::member(1);')
    );
  }
}