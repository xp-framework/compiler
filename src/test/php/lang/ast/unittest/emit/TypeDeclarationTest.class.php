<?php namespace lang\ast\unittest\emit;

use lang\XPClass;
use lang\reflect\Modifiers;
use unittest\{Assert, Test, Values};

class TypeDeclarationTest extends EmittingTest {

  #[Test, Values(['class', 'interface', 'trait'])]
  public function empty_type($kind) {
    $t= $this->type($kind.' <T> { }');
    Assert::equals(
      ['const' => [], 'fields' => [], 'methods' => []],
      ['const' => $t->getConstants(), 'fields' => $t->getFields(), 'methods' => $t->getMethods()]
    );
  }

  #[Test]
  public function abstract_class_type() {
    Assert::true(Modifiers::isAbstract($this->type('abstract class <T> { }')->getModifiers()));
  }

  #[Test]
  public function final_class_type() {
    Assert::true(Modifiers::isFinal($this->type('final class <T> { }')->getModifiers()));
  }

  #[Test]
  public function class_without_parent() {
    Assert::null($this->type('class <T> { }')->getParentclass());
  }

  #[Test]
  public function class_with_parent() {
    Assert::equals(
      new XPClass(EmittingTest::class),
      $this->type('class <T> extends \\lang\\ast\\unittest\\emit\\EmittingTest { }')->getParentclass()
    );
  }

  #[Test]
  public function trait_type() {
    Assert::true($this->type('trait <T> { }')->isTrait());
  }

  #[Test]
  public function trait_type_with_method() {
    Assert::true($this->type('trait <T> { public function name() { return "Test"; }}')->isTrait());
  }

  #[Test]
  public function interface_type() {
    Assert::true($this->type('interface <T> { }')->isInterface());
  }

  #[Test]
  public function interface_type_with_method() {
    Assert::true($this->type('interface <T> { public function name(); }')->isInterface());
  }

  #[Test, Values(['public', 'private', 'protected'])]
  public function constant($modifiers) {
    $c= $this->type('class <T> { '.$modifiers.' const test = 1; }')->getConstant('test');
    Assert::equals(1, $c);
  }

  #[Test, Values(['public', 'private', 'protected', 'public static', 'private static', 'protected static'])]
  public function field($modifiers) {
    $f= $this->type('class <T> { '.$modifiers.' $test; }')->getField('test');
    $n= implode(' ', Modifiers::namesOf($f->getModifiers()));
    Assert::equals(
      ['name' => 'test', 'type' => 'var', 'modifiers' => $modifiers],
      ['name' => $f->getName(), 'type' => $f->getTypeName(), 'modifiers' => $n]
    );
  }

  #[Test, Values(['public', 'protected', 'private', 'public final', 'protected final', 'public static', 'protected static', 'private static'])]
  public function method($modifiers) {
    $m= $this->type('class <T> { '.$modifiers.' function test() { } }')->getMethod('test');
    $n= implode(' ', Modifiers::namesOf($m->getModifiers()));
    Assert::equals(
      ['name' => 'test', 'type' => 'var', 'modifiers' => $modifiers],
      ['name' => $m->getName(), 'type' => $m->getReturnTypeName(), 'modifiers' => $n]
    );
  }

  #[Test]
  public function abstract_method() {
    $m= $this->type('abstract class <T> { abstract function test(); }')->getMethod('test');
    Assert::true(Modifiers::isAbstract($m->getModifiers()));
  }

  #[Test]
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
    Assert::equals([2, 4, 6], $t->getMethod('run')->invoke(null, [[1, 2, 3]]));
  }
}