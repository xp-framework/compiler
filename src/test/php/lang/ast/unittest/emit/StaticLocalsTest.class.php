<?php namespace lang\ast\unittest\emit;

use lang\reflection\InvocationFailed;
use test\{Assert, Test};
use util\Date;

/** @see https://wiki.php.net/rfc/arbitrary_static_variable_initializers */
class StaticLocalsTest extends EmittingTest {

  /**
   * Calls the given type's `run()` method
   *
   * @param  lang.Type $type
   * @param  var... $args
   * @return var[]
   */
  private function apply($t, ... $args) {
    return $t->method('run')->invoke($t->newInstance(), $args);
  }

  #[Test]
  public function constant_static() {
    $t= $this->declare('class %T {
      public function run() {
        static $i= 0;

        return $i++;
      }
    }');

    Assert::equals(0, $this->apply($t));
    Assert::equals(1, $this->apply($t));
    Assert::equals(2, $this->apply($t));
  }

  #[Test]
  public function initialization_to_new() {
    $t= $this->declare('use util\\{Date, Dates}; class %T {
      public function run() {
        static $t= new Date(0);

        return $t= Dates::add($t, 86400);
      }
    }');

    Assert::equals(new Date(86400), $this->apply($t));
    Assert::equals(new Date(86400 * 2), $this->apply($t));
  }

  #[Test]
  public function initialization_to_parameter() {
    $t= $this->declare('class %T {
      public function run($initial) {
        static $t= $initial;

        return $t;
      }
    }');

    $instance= $t->newInstance();
    Assert::equals('initial', $this->apply($t, 'initial'));
    Assert::equals('initial', $this->apply($t, 'changed'));
  }

  #[Test]
  public function initialization_when_throwing() {
    $t= $this->declare('use lang\\IllegalArgumentException; class %T {
      public function run($initial) {
        static $t= $initial ?? throw new IllegalArgumentException("May not be null");

        return $t;
      }
    }');

    // This does not initialize the static
    Assert::throws(InvocationFailed::class, function() use($t) {
      $this->apply($t, null);
    });

    // This does
    Assert::equals('initial', $this->apply($t, 'initial'));
  }
}