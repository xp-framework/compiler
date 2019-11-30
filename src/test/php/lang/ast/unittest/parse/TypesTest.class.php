<?php namespace lang\ast\unittest\parse;

use lang\ast\Errors;
use lang\ast\nodes\{ClassDeclaration, InterfaceDeclaration, NamespaceDeclaration, TraitDeclaration, UseExpression};
use unittest\Assert;

class TypesTest extends ParseTest {

  #[@test]
  public function empty_class() {
    $this->assertParsed(
      [new ClassDeclaration([], '\\A', null, [], [], [], null, self::LINE)],
      'class A { }'
    );
  }

  #[@test]
  public function class_with_parent() {
    $this->assertParsed(
      [new ClassDeclaration([], '\\A', '\\B', [], [], [], null, self::LINE)],
      'class A extends B { }'
    );
  }

  #[@test]
  public function class_with_interface() {
    $this->assertParsed(
      [new ClassDeclaration([], '\\A', null, ['\\C'], [], [], null, self::LINE)],
      'class A implements C { }'
    );
  }

  #[@test]
  public function class_with_interfaces() {
    $this->assertParsed(
      [new ClassDeclaration([], '\\A', null, ['\\C', '\\D'], [], [], null, self::LINE)],
      'class A implements C, D { }'
    );
  }

  #[@test]
  public function abstract_class() {
    $this->assertParsed(
      [new ClassDeclaration(['abstract'], '\\A', null, [], [], [], null, self::LINE)],
      'abstract class A { }'
    );
  }

  #[@test]
  public function final_class() {
    $this->assertParsed(
      [new ClassDeclaration(['final'], '\\A', null, [], [], [], null, self::LINE)],
      'final class A { }'
    );
  }

  #[@test]
  public function empty_interface() {
    $this->assertParsed(
      [new InterfaceDeclaration([], '\\A', [], [], [], null, self::LINE)],
      'interface A { }'
    );
  }

  #[@test]
  public function interface_with_parent() {
    $this->assertParsed(
      [new InterfaceDeclaration([], '\\A', ['\\B'], [], [], null, self::LINE)],
      'interface A extends B { }'
    );
  }

  #[@test]
  public function interface_with_parents() {
    $this->assertParsed(
      [new InterfaceDeclaration([], '\\A', ['\\B', '\\C'], [], [], null, self::LINE)],
      'interface A extends B, C { }'
    );
  }

  #[@test]
  public function empty_trait() {
    $this->assertParsed(
      [new TraitDeclaration([], '\\A', [], [], null, self::LINE)],
      'trait A { }'
    );
  }

  #[@test]
  public function class_with_trait() {
    $this->assertParsed(
      [new ClassDeclaration([], '\\A', null, [], [new UseExpression(['\\B'], [], self::LINE)], [], null, self::LINE)],
      'class A { use B; }'
    );
  }

  #[@test]
  public function class_with_multiple_traits() {
    $body= [new UseExpression(['\\B'], [], self::LINE), new UseExpression(['\\C'], [], self::LINE)];
    $this->assertParsed(
      [new ClassDeclaration([], '\\A', null, [], $body, [], null, self::LINE)],
      'class A { use B; use C; }'
    );
  }

  #[@test]
  public function class_with_comma_separated_traits() {
    $body= [new UseExpression(['\\B', '\\C'], [], self::LINE)];
    $this->assertParsed(
      [new ClassDeclaration([], '\\A', null, [], $body, [], null, self::LINE)],
      'class A { use B, C; }'
    );
  }

  #[@test]
  public function class_in_namespace() {
    $this->assertParsed(
      [new NamespaceDeclaration('test', self::LINE), new ClassDeclaration([], '\\test\\A', null, [], [], [], null, self::LINE)],
      'namespace test; class A { }'
    );
  }

  #[@test, @expect(['class' => Errors::class, 'withMessage' => 'Cannot redeclare method b()'])]
  public function cannot_redeclare_method() {
    iterator_to_array($this->parse('class A { public function b() { } public function b() { }}'));
  }

  #[@test, @expect(['class' => Errors::class, 'withMessage' => 'Cannot redeclare property $b'])]
  public function cannot_redeclare_property() {
    iterator_to_array($this->parse('class A { public $b; private $b; }'));
  }

  #[@test, @expect(['class' => Errors::class, 'withMessage' => 'Cannot redeclare constant B'])]
  public function cannot_redeclare_constant() {
    iterator_to_array($this->parse('class A { const B = 1; const B = 3; }'));
  }
}