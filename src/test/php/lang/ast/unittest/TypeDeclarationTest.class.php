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
  public function trait_type() {
    $this->assertTrue($this->declare('trait <T> { }')->isTrait());
  }

  #[@test]
  public function interface_type() {
    $this->assertTrue($this->declare('interface <T> { }')->isInterface());
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
  #  'public', 'private', 'protected',
  #  'public static', 'private static', 'protected static'
  #])]
  public function method($modifiers) {
    $m= $this->declare('class <T> { '.$modifiers.' function test() { } }')->getMethod('test');
    $n= implode(' ', Modifiers::namesOf($m->getModifiers()));
    $this->assertEquals(
      ['name' => 'test', 'type' => 'var', 'modifiers' => $modifiers],
      ['name' => $m->getName(), 'type' => $m->getReturnTypeName(), 'modifiers' => $n]
    );
  }
}