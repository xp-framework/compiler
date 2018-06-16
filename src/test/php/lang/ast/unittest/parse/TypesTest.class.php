<?php namespace lang\ast\unittest\parse;

use lang\ast\Error;

class TypesTest extends ParseTest {

  #[@test]
  public function empty_class() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [], [], null]]],
      $this->parse('class A { }')
    );
  }

  #[@test]
  public function class_with_parent() {
    $this->assertNodes(
      [['class' => ['\\A', [], '\\B', [], [], [], null]]],
      $this->parse('class A extends B { }')
    );
  }

  #[@test]
  public function class_with_interface() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, ['\\C'], [], [], null]]],
      $this->parse('class A implements C { }')
    );
  }

  #[@test]
  public function class_with_interfaces() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, ['\\C', '\\D'], [], [], null]]],
      $this->parse('class A implements C, D { }')
    );
  }

  #[@test]
  public function abstract_class() {
    $this->assertNodes(
      [['abstract' => ['\\A', ['abstract'], null, [], [], [], null]]],
      $this->parse('abstract class A { }')
    );
  }

  #[@test]
  public function final_class() {
    $this->assertNodes(
      [['final' => ['\\A', ['final'], null, [], [], [], null]]],
      $this->parse('final class A { }')
    );
  }

  #[@test]
  public function empty_interface() {
    $this->assertNodes(
      [['interface' => ['\\A', [], [], [], [], null]]],
      $this->parse('interface A { }')
    );
  }

  #[@test]
  public function interface_with_parent() {
    $this->assertNodes(
      [['interface' => ['\\A', [], ['\\B'], [], [], null]]],
      $this->parse('interface A extends B { }')
    );
  }

  #[@test]
  public function interface_with_parents() {
    $this->assertNodes(
      [['interface' => ['\\A', [], ['\\B', '\\C'], [], [], null]]],
      $this->parse('interface A extends B, C { }')
    );
  }

  #[@test]
  public function empty_trait() {
    $this->assertNodes(
      [['trait' => ['\\A', [], [], [], null]]],
      $this->parse('trait A { }')
    );
  }

  #[@test]
  public function class_with_trait() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [['use' => [['\\B'], []]]], [], null]]],
      $this->parse('class A { use B; }')
    );
  }

  #[@test]
  public function class_with_multiple_traits() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [['use' => [['\\B'], []]], ['use' => [['\\C'], []]]], [], null]]],
      $this->parse('class A { use B; use C; }')
    );
  }

  #[@test]
  public function class_with_comma_separated_traits() {
    $this->assertNodes(
      [['class' => ['\\A', [], null, [], [['use' => [['\\B', '\\C'], []]]], [], null]]],
      $this->parse('class A { use B, C; }')
    );
  }

  #[@test]
  public function class_in_namespace() {
    $this->assertNodes(
      [['namespace' => 'test'], ['class' => ['\\test\\A', [], null, [], [], [], null]]],
      $this->parse('namespace test; class A { }')
    );
  }

  #[@test, @expect(class= Error::class, withMessage= 'Cannot redeclare method b()')]
  public function cannot_redeclare_method() {
    iterator_to_array($this->parse('class A { public function b() { } public function b() { }}'));
  }

  #[@test, @expect(class= Error::class, withMessage= 'Cannot redeclare property $b')]
  public function cannot_redeclare_property() {
    iterator_to_array($this->parse('class A { public $b; private $b; }'));
  }

  #[@test, @expect(class= Error::class, withMessage= 'Cannot redeclare constant B')]
  public function cannot_redeclare_constant() {
    iterator_to_array($this->parse('class A { const B = 1; const B = 3; }'));
  }
}