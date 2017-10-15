<?php namespace lang\ast\unittest\emit;

/**
 * Lambdas (a.k.a. arrow functions) support
 *
 * NOTE: Like in Hack, all captured variables are captured by value;
 * capturing by reference is not supported. If you need to capture by
 * reference, use a full PHP closure.
 *
 * @see  https://docs.hhvm.com/hack/operators/lambda
 * @see  https://wiki.php.net/rfc/arrow_functions (Under Discussion)
 */
class LambdasTest extends EmittingTest {

  #[@test]
  public function inc() {
    $r= $this->run('class <T> {
      public function run() {
        return ($a) ==> $a + 1;
      }
    }');

    $this->assertEquals(2, $r(1));
  }

  #[@test]
  public function add() {
    $r= $this->run('class <T> {
      public function run() {
        return ($a, $b) ==> $a + $b;
      }
    }');

    $this->assertEquals(3, $r(1, 2));
  }

  #[@test]
  public function captures_this() {
    $r= $this->run('class <T> {
      private $addend= 2;

      public function run() {
        return ($a) ==> $a + $this->addend;
      }
    }');

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function captures_local() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        return ($a) ==> $a + $addend;
      }
    }');

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function captures_braced_local() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        return ($a) ==> $a + ($addend);
      }
    }');

    $this->assertEquals(3, $r(1));
  }
}