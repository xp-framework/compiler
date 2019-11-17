<?php namespace lang\ast\unittest\emit;

use lang\Primitive;
use lang\TypeUnion;
use unittest\Assert;

/**
 * Function types
 *
 * @see  https://wiki.php.net/rfc/union_types (Declined)
 */
class UnionTypesTest extends EmittingTest {

  #[@test]
  public function field_type() {
    $t= $this->type('class <T> {
      private int|string $test;
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getField('test')->getType()
    );
  }

  #[@test]
  public function parameter_type() {
    $t= $this->type('class <T> {
      public function test(int|string $arg) { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getMethod('test')->getParameter(0)->getType()
    );
  }

  #[@test]
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