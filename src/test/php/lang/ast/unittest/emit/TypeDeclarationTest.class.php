<?php namespace lang\ast\unittest\emit;

use lang\reflection\Kind;
use lang\{XPClass, Type};
use test\verify\Runtime;
use test\{Assert, Test, Values};

class TypeDeclarationTest extends EmittingTest {

  #[Test, Values(['class', 'interface', 'trait'])]
  public function empty_type($kind) {
    $t= $this->declare($kind.' %T { }');
    Assert::equals(
      ['constants' => [], 'properties' => [], 'methods' => []],
      [
        'constants'  => iterator_to_array($t->constants()),
        'properties' => iterator_to_array($t->properties()),
        'methods'    => iterator_to_array($t->methods())
      ]
    );
  }

  #[Test]
  public function abstract_class_type() {
    Assert::true($this->declare('abstract class %T { }')->modifiers()->isAbstract());
  }

  #[Test]
  public function final_class_type() {
    Assert::true($this->declare('final class %T { }')->modifiers()->isFinal());
  }

  #[Test]
  public function class_without_parent() {
    Assert::null($this->declare('class %T { }')->parent());
  }

  #[Test]
  public function class_with_parent() {
    Assert::equals(
      EmittingTest::class,
      $this->declare('class %T extends \\lang\\ast\\unittest\\emit\\EmittingTest { }')->parent()->literal()
    );
  }

  #[Test]
  public function trait_type() {
    Assert::equals(Kind::$TRAIT, $this->declare('trait %T { }')->kind());
  }

  #[Test]
  public function trait_type_with_method() {
    Assert::equals(
      Kind::$TRAIT,
      $this->declare('trait %T { public function name() { return "Test"; }}')->kind()
    );
  }

  #[Test]
  public function interface_type() {
    Assert::equals(Kind::$INTERFACE, $this->declare('interface %T { }')->kind());
  }

  #[Test]
  public function interface_type_with_method() {
    Assert::equals(
      Kind::$INTERFACE,
      $this->declare('interface %T { public function name(); }')->kind()
    );
  }

  #[Test]
  public function interface_type_with_default_method() {
    $i= $this->type('interface <T> {
      public function all();
      public function filter($filter) {
        foreach ($this->all() as $element) {
          $filter($element) || yield $element;
        }
      }
    }');
    $t= $this->type('class <T> implements '.$i->literal().'{
      public function all() { return [1, 2, 3]; }
    }');
    Assert::equals([2], iterator_to_array($t->newInstance()->filter(function($i) { return $i % 2; })));
  }

  #[Test, Values(['public', 'private', 'protected']), Runtime(php: '>=7.1.0')]
  public function constant($modifiers) {
    $c= $this->declare('class %T { '.$modifiers.' const test = 1; }')->constant('test');
    Assert::equals(
      ['name' => 'test', 'type' => Type::$VAR, 'modifiers' => $modifiers],
      ['name' => $c->name(), 'type' => $c->constraint()->type(), 'modifiers' => $c->modifiers()->names()]
    );
  }

  #[Test, Values(['public', 'private', 'protected', 'public static', 'private static', 'protected static'])]
  public function property($modifiers) {
    $p= $this->declare('class %T { '.$modifiers.' $test; }')->property('test');
    Assert::equals(
      ['name' => 'test', 'type' => Type::$VAR, 'modifiers' => $modifiers],
      ['name' => $p->name(), 'type' => $p->constraint()->type(), 'modifiers' => $p->modifiers()->names()]
    );
  }

  #[Test, Values(['public', 'protected', 'private', 'public final', 'protected final', 'public static', 'protected static', 'private static'])]
  public function method($modifiers) {
    $m= $this->declare('class %T { '.$modifiers.' function test() { } }')->method('test');
    Assert::equals(
      ['name' => 'test', 'type' => Type::$VAR, 'modifiers' => $modifiers],
      ['name' => $m->name(), 'type' => $m->returns()->type(), 'modifiers' => $m->modifiers()->names()]
    );
  }

  #[Test]
  public function abstract_method() {
    $m= $this->declare('abstract class %T { abstract function test(); }')->method('test');
    Assert::true($m->modifiers()->isAbstract());
  }

  #[Test]
  public function method_with_keyword() {
    $t= $this->declare('class %T {
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
    Assert::equals([2, 4, 6], $t->method('run')->invoke(null, [[1, 2, 3]]));
  }
}