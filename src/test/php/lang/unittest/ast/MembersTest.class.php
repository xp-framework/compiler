<?php namespace lang\unittest\ast;

class MembersTest extends ParseTest {

  #[@test]
  public function private_instance_property() {
    $this->assertNodes(
      [['class' => ['A', null, [['(variable)' => ['a', ['private'], null, null, null]]]]]],
      $this->parse('class A { private $a; }')
    );
  }

  #[@test]
  public function private_instance_method() {
    $this->assertNodes(
      [['class' => ['A', null, [['(' => ['a', ['private'], [], [], null, null]]]]]],
      $this->parse('class A { private function a() { } }')
    );
  }

  #[@test]
  public function private_static_method() {
    $this->assertNodes(
      [['class' => ['A', null, [['(' => ['a', ['private', 'static'], [], [], null, null]]]]]],
      $this->parse('class A { private static function a() { } }')
    );
  }

  #[@test]
  public function short_method() {
    $block= [['==>' => ['true' => true]]];
    $this->assertNodes(
      [['class' => ['A', null, [['(' => ['a', ['public'], [], $block, null, null]]]]]],
      $this->parse('class A { public function a() ==> true; }')
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
  public function static_property_access() {
    $this->assertNodes(
      [['::' => ['A', ['(variable)' => 'member']]]],
      $this->parse('A::$member;')
    );
  }

  #[@test]
  public function instance_method_invocation() {
    $this->assertNodes(
      [['->' => [['(variable)' => 'a'], ['(' => [['member' => 'member'], [['(literal)' => 1]]]]]]],
      $this->parse('$a->member(1);')
    );
  }

  #[@test]
  public function static_method_invocation() {
    $this->assertNodes(
      [['::' => ['A', ['(' => [['member' => 'member'], [['(literal)' => 1]]]]]]],
      $this->parse('A::member(1);')
    );
  }
}