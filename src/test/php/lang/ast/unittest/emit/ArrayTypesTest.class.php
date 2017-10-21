<?php namespace lang\ast\unittest\emit;

/**
 * Array types
 *
 * @see  https://docs.hhvm.com/hack/types/summary-table
 */
class ArrayTypesTest extends EmittingTest {

  #[@test]
  public function int_array_type() {
    $t= $this->type('class <T> {
      private array<int> $test;
    }');

    $this->assertEquals('int[]', $t->getField('test')->getType()->getName());
  }

  #[@test]
  public function int_map_type() {
    $t= $this->type('class <T> {
      private array<string, int> $test;
    }');

    $this->assertEquals('[:int]', $t->getField('test')->getType()->getName());
  }

  #[@test]
  public function nested_map_type() {
    $t= $this->type('class <T> {
      private array<string, array<int>> $test;
    }');

    $this->assertEquals('[:int[]]', $t->getField('test')->getType()->getName());
  }
}