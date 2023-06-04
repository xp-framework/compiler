<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test};

/**
 * Array types
 *
 * @see  https://docs.hhvm.com/hack/types/summary-table
 */
class ArrayTypesTest extends EmittingTest {

  #[Test]
  public function int_array_type() {
    $t= $this->declare('class %T {
      private array<int> $test;
    }');

    Assert::equals('int[]', $t->property('test')->constraint()->type()->getName());
  }

  #[Test]
  public function int_map_type() {
    $t= $this->declare('class %T {
      private array<string, int> $test;
    }');

    Assert::equals('[:int]', $t->property('test')->constraint()->type()->getName());
  }

  #[Test]
  public function nested_map_type() {
    $t= $this->declare('class %T {
      private array<string, array<int>> $test;
    }');

    Assert::equals('[:int[]]', $t->property('test')->constraint()->type()->getName());
  }

  #[Test]
  public function var_map_type() {
    $t= $this->declare('class %T {
      private array<string, mixed> $test;
    }');

    Assert::equals('[:var]', $t->property('test')->constraint()->type()->getName());
  }
}