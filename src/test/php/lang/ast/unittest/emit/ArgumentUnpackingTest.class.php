<?php namespace lang\ast\unittest\emit;

use lang\Primitive;
use unittest\Assert;

/**
 * Argument unpacking
 *
 * @see  https://wiki.php.net/rfc/argument_unpacking
 * @see  https://wiki.php.net/rfc/additional-splat-usage (Under Discussion)
 */
class ArgumentUnpackingTest extends EmittingTest {

  #[@test]
  public function invoking_method() {
    $r= $this->run('class <T> {
      public function fixture(... $args) { return $args; }

      public function run() {
        $args= [1, 2, 3];
        return $this->fixture(...$args);
      }
    }');
    Assert::equals([1, 2, 3], $r);
  }

  #[@test]
  public function in_array_initialization_with_variable() {
    $r= $this->run('class <T> {
      public function run() {
        $args= [3, 4];
        return [1, 2, ...$args];
      }
    }');
    Assert::equals([1, 2, 3, 4], $r);
  }

  #[@test]
  public function in_array_initialization_with_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return [1, 2, ...[3, 4]];
      }
    }');
    Assert::equals([1, 2, 3, 4], $r);
  }

  #[@test]
  public function in_map_initialization() {
    $r= $this->run('class <T> {
      public function run() {
        $args= ["type" => "car"];
        return ["color" => "red", ...$args, "year" => 2002];
      }
    }');
    Assert::equals(['color' => 'red', 'type' => 'car', 'year' => 2002], $r);
  }

  #[@test]
  public function from_generator() {
    $r= $this->run('class <T> {
      private function items() {
        yield 1;
        yield 2;
      }

      public function run() {
        return [...$this->items()];
      }
    }');
    Assert::equals([1, 2], $r);
  }

  #[@test]
  public function from_iterator() {
    $r= $this->run('class <T> {
      private function items() {
        return new \ArrayIterator([1, 2]);
      }

      public function run() {
        return [...$this->items()];
      }
    }');
    Assert::equals([1, 2], $r);
  }
}