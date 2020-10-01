<?php namespace lang\ast\unittest\emit;

use lang\{Primitive, TypeUnion};
use unittest\{Assert, Test};

/**
 * Function types
 *
 * @see  https://wiki.php.net/rfc/union_types (Declined)
 */
class UnionTypesTest extends EmittingTest {

  #[Test]
  public function field_type() {
    $t= $this->type('class <T> {
      private int|string $test;
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getField('test')->getType()
    );
  }

  #[Test]
  public function parameter_type() {
    $t= $this->type('class <T> {
      public function test(int|string $arg) { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getMethod('test')->getParameter(0)->getType()
    );
  }

  #[Test]
  public function return_type() {
    $t= $this->type('class <T> {
      public function test(): int|string { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getMethod('test')->getReturnType()
    );
  }
}