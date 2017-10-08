<?php namespace lang\unittest\ast;

class MembersTest extends ParseTest {

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