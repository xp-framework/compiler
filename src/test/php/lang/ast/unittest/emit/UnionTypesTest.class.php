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
  public function numeric_union() {
    $t= $this->type('class <T> {
      private int|string $test;
    }');

    $this->assertEquals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getField('test')->getType()
    );
  }
}