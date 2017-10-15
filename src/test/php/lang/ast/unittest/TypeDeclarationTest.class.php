<?php namespace lang\ast\unittest;

use lang\reflect\Modifiers;

class TypeDeclarationTest extends EmittingTest {

  #[@test, @values(['class', 'interface', 'trait'])]
  public function empty_type($kind) {
    $t= $this->declare($kind.' <T> { }');
    $this->assertEquals(
      ['const' => [], 'fields' => [], 'methods' => []],
      ['const' => $t->getConstants(), 'fields' => $t->getFields(), 'methods' => $t->getMethods()]
    );
  }

  #[@test]
  public function abstract_class_type() {
    $this->assertTrue(Modifiers::isAbstract($this->declare('abstract class <T> { }')->getModifiers()));
  }

  #[@test]
  public function final_class_type() {
    $this->assertTrue(Modifiers::isFinal($this->declare('final class <T> { }')->getModifiers()));
  }

  #[@test]
  public function trait_type() {
    $this->assertTrue($this->declare('trait <T> { }')->isTrait());
  }

  #[@test]
  public function interface_type() {
    $this->assertTrue($this->declare('interface <T> { }')->isInterface());
  }

  #[@test, @values(['public', 'private', 'protected'])]
  public function constant($modifiers) {
    $c= $this->declare('class <T> { '.$modifiers.' const test = 1; }')->getConstant('test');
    $this->assertEquals(1, $c);
  }

  #[@test, @values([
  #  'public', 'private', 'protected',
  #  'public static', 'private static', 'protected static'
  #])]
  public function field($modifiers) {
    $f= $this->declare('class <T> { '.$modifiers.' $test; }')->getField('test');
    $n= implode(' ', Modifiers::namesOf($f->getModifiers()));
    $this->assertEquals(
      ['name' => 'test', 'type' => 'var', 'modifiers' => $modifiers],
      ['name' => $f->getName(), 'type' => $f->getTypeName(), 'modifiers' => $n]
    );
  }

  #[@test, @values([
  #  'public', 'protected', 'private',
  #  'public final', 'protected final',
  #  'public static', 'protected static', 'private static'
  #])]
  public function method($modifiers) {
    $m= $this->declare('class <T> { '.$modifiers.' function test() { } }')->getMethod('test');
    $n= implode(' ', Modifiers::namesOf($m->getModifiers()));
    $this->assertEquals(
      ['name' => 'test', 'type' => 'var', 'modifiers' => $modifiers],
      ['name' => $m->getName(), 'type' => $m->getReturnTypeName(), 'modifiers' => $n]
    );
  }

  #[@test]
  public function abstract_method() {
    $m= $this->declare('abstract class <T> { abstract function test(); }')->getMethod('test');
    $this->assertTrue(Modifiers::isAbstract($m->getModifiers()));
  }
}