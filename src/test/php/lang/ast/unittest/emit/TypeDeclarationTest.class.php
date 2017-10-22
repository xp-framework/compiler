<?php namespace lang\ast\unittest\emit;

use lang\reflect\Modifiers;
use lang\XPClass;

class TypeDeclarationTest extends EmittingTest {

  #[@test, @values(['class', 'interface', 'trait'])]
  public function empty_type($kind) {
    $t= $this->type($kind.' <T> { }');
    $this->assertEquals(
      ['const' => [], 'fields' => [], 'methods' => []],
      ['const' => $t->getConstants(), 'fields' => $t->getFields(), 'methods' => $t->getMethods()]
    );
  }

  #[@test]
  public function abstract_class_type() {
    $this->assertTrue(Modifiers::isAbstract($this->type('abstract class <T> { }')->getModifiers()));
  }

  #[@test]
  public function final_class_type() {
    $this->assertTrue(Modifiers::isFinal($this->type('final class <T> { }')->getModifiers()));
  }

  #[@test]
  public function class_without_parent() {
    $this->assertNull($this->type('class <T> { }')->getParentclass());
  }

  #[@test]
  public function class_with_parent() {
    $this->assertEquals(
      new XPClass(EmittingTest::class),
      $this->type('class <T> extends \\lang\\ast\\unittest\\emit\\EmittingTest { }')->getParentclass()
    );
  }

  #[@test]
  public function trait_type() {
    $this->assertTrue($this->type('trait <T> { }')->isTrait());
  }

  #[@test]
  public function interface_type() {
    $this->assertTrue($this->type('interface <T> { }')->isInterface());
  }

  #[@test, @values(['public', 'private', 'protected'])]
  public function constant($modifiers) {
    $c= $this->type('class <T> { '.$modifiers.' const test = 1; }')->getConstant('test');
    $this->assertEquals(1, $c);
  }

  #[@test, @values([
  #  'public', 'private', 'protected',
  #  'public static', 'private static', 'protected static'
  #])]
  public function field($modifiers) {
    $f= $this->type('class <T> { '.$modifiers.' $test; }')->getField('test');
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
    $m= $this->type('class <T> { '.$modifiers.' function test() { } }')->getMethod('test');
    $n= implode(' ', Modifiers::namesOf($m->getModifiers()));
    $this->assertEquals(
      ['name' => 'test', 'type' => 'var', 'modifiers' => $modifiers],
      ['name' => $m->getName(), 'type' => $m->getReturnTypeName(), 'modifiers' => $n]
    );
  }

  #[@test]
  public function abstract_method() {
    $m= $this->type('abstract class <T> { abstract function test(); }')->getMethod('test');
    $this->assertTrue(Modifiers::isAbstract($m->getModifiers()));
  }

  #[@test]
  public function method_with_keyword() {
    $t= $this->type('class <T> {
      private $items;

      public static function new($items) {
        $self= new self();
        $self->items= $items;
        return $self;
      }

      public function forEach(callable $callback) {
        return array_map($callback, $this->items);
      }

      public static function run($values) {
        return self::new($values)->forEach(function($a) { return $a * 2; });
      }
    }');
    $this->assertEquals([2, 4, 6], $t->getMethod('run')->invoke(null, [[1, 2, 3]]));
  }
}