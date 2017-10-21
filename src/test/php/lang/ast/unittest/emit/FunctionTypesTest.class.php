<?php namespace lang\ast\unittest\emit;

use lang\FunctionType;
use lang\Primitive;

/**
 * Function types
 *
 * @see  https://docs.hhvm.com/hack/types/summary-table
 */
class FunctionTypesTest extends EmittingTest {

  #[@test]
  public function function_without_parameters() {
    $t= $this->type('class <T> {
      private (function(): string) $test;
    }');

    $this->assertEquals(
      new FunctionType([], Primitive::$STRING),
      $t->getField('test')->getType()
    );
  }

  #[@test]
  public function function_with_parameters() {
    $t= $this->type('class <T> {
      private (function(int, string): string) $test;
    }');

    $this->assertEquals(
      new FunctionType([Primitive::$INT, Primitive::$STRING], Primitive::$STRING),
      $t->getField('test')->getType()
    );
  }
}