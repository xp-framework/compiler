<?php namespace lang\ast\unittest\emit;

use lang\TypeUnion;
use lang\Primitive;

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

    $this->assertEquals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getField('test')->getType()
    );
  }

  #[@test]
  public function parameter_type() {
    $t= $this->type('class <T> {
      public function test(int|string $arg) { }
    }');

    $this->assertEquals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getMethod('test')->getParameter(0)->getType()
    );
  }

  #[@test]
  public function return_type() {
    $t= $this->type('class <T> {
      public function test(): int|string { }
    }');

    $this->assertEquals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getMethod('test')->getReturnType()
    );
  }
}