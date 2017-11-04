<?php namespace lang\ast\unittest\emit;

use lang\Primitive;

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
    $this->assertEquals([1, 2, 3], $r);
  }

  #[@test]
  public function in_array_initialization_with_variable() {
    $r= $this->run('class <T> {
      public function run() {
        $args= [3, 4];
        return [1, 2, ...$args];
      }
    }');
    $this->assertEquals([1, 2, 3, 4], $r);
  }

  #[@test]
  public function in_array_initialization_with_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return [1, 2, ...[3, 4]];
      }
    }');
    $this->assertEquals([1, 2, 3, 4], $r);
  }

  #[@test]
  public function in_map_initialization() {
    $r= $this->run('class <T> {
      public function run() {
        $args= ["type" => "car"];
        return ["color" => "red", ...$args, "year" => 2002];
      }
    }');
    $this->assertEquals(['color' => 'red', 'type' => 'car', 'year' => 2002], $r);
  }
}