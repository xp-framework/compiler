<?php namespace lang\ast\unittest;

class TypesTest extends ParseTest {

  #[@test]
  public function empty_class() {
    $this->assertNodes(
      [['class' => ['A', null, [], []]]],
      $this->parse('class A { }')
    );
  }

  #[@test]
  public function class_with_parent() {
    $this->assertNodes(
      [['class' => ['A', 'B', [], []]]],
      $this->parse('class A extends B { }')
    );
  }

  #[@test]
  public function class_with_interface() {
    $this->assertNodes(
      [['class' => ['A', null, ['C'], []]]],
      $this->parse('class A implements C { }')
    );
  }

  #[@test]
  public function class_with_interfaces() {
    $this->assertNodes(
      [['class' => ['A', null, ['C', 'D'], []]]],
      $this->parse('class A implements C, D { }')
    );
  }

  #[@test]
  public function abstract_class() {
    $this->assertNodes(
      [['abstract' => ['A', null, [], []]]],
      $this->parse('abstract class A { }')
    );
  }

  #[@test]
  public function final_class() {
    $this->assertNodes(
      [['final' => ['A', null, [], []]]],
      $this->parse('final class A { }')
    );
  }

  #[@test]
  public function empty_interface() {
    $this->assertNodes(
      [['interface' => ['A', [], []]]],
      $this->parse('interface A { }')
    );
  }

  #[@test]
  public function interface_with_parent() {
    $this->assertNodes(
      [['interface' => ['A', ['B'], []]]],
      $this->parse('interface A extends B { }')
    );
  }

  #[@test]
  public function interface_with_parents() {
    $this->assertNodes(
      [['interface' => ['A', ['B', 'C'], []]]],
      $this->parse('interface A extends B, C { }')
    );
  }

  #[@test]
  public function empty_trait() {
    $this->assertNodes(
      [['trait' => ['A', []]]],
      $this->parse('trait A { }')
    );
  }

  #[@test]
  public function class_with_trait() {
    $this->assertNodes(
      [['class' => ['A', null, [], [['use' => 'B']]]]],
      $this->parse('class A { use B; }')
    );
  }

  #[@test]
  public function class_with_traits() {
    $this->assertNodes(
      [['class' => ['A', null, [], [['use' => 'B'], ['use' => 'C']]]]],
      $this->parse('class A { use B; use C; }')
    );
  }
}