<?php namespace lang\ast\unittest\emit;

/**
 * Using statement and disposables
 *
 * @see  https://docs.hhvm.com/hack/disposables/introduction
 */
class UsingTest extends EmittingTest {

  #[@test]
  public function dispose_called() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run() {
        Handle::$called= [];

        using ($x= new Handle()) {
          $x->read();
        }

        return Handle::$called;
      }
    }');
    $this->assertEquals(['read', '__dispose'], $r);
  }

  #[@test]
  public function dispose_called_even_when_exceptions_occur() {
    $r= $this->run('use lang\{IllegalArgumentException, IllegalStateException}; use lang\ast\unittest\emit\Handle; class <T> {
      public function run() {
        Handle::$called= [];

        try {
          using ($x= new Handle()) {
            $x->read(-1);
          }
        } catch (IllegalArgumentException $expected) {
          return Handle::$called;  
        }

        throw new IllegalStateException("No exception caught");
      }
    }');
    $this->assertEquals(['read', '__dispose'], $r);
  }
}