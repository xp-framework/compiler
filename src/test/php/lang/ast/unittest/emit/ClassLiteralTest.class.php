<?php namespace lang\ast\unittest\emit;

use lang\Primitive;
use test\{Assert, Test};

/**
 * Tests `::class`
 *
 * @see  https://wiki.php.net/rfc/class_name_literal_on_object
 */
class ClassLiteralTest extends EmittingTest {

  #[Test]
  public function on_name() {
    $r= $this->run('use lang\Primitive; class %T {
      public function run() { return Primitive::class; }
    }');
    Assert::equals(Primitive::class, $r);
  }

  #[Test]
  public function on_self() {
    $t= $this->declare('class %T {
      public function run() { return self::class; }
    }');
    Assert::equals($t->literal(), $t->newInstance()->run());
  }

  #[Test]
  public function on_object() {
    $t= $this->declare('class %T {
      public function run() { return $this::class; }
    }');
    Assert::equals($t->literal(), $t->newInstance()->run());
  }

  #[Test]
  public function on_instantiation() {
    $t= $this->declare('class %T {
      public function run() { return new self()::class; }
    }');
    Assert::equals($t->literal(), $t->newInstance()->run());
  }

  #[Test]
  public function on_braced_expression() {
    $t= $this->declare('class %T {
      public function run() { return (new self())::class; }
    }');
    Assert::equals($t->literal(), $t->newInstance()->run());
  }
}