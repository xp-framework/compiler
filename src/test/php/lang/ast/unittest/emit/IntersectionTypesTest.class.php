<?php namespace lang\ast\unittest\emit;

use lang\{Primitive, TypeIntersection, XPClass};
use test\verify\Runtime;
use test\{Action, Assert, Test};

/**
 * Intersection types
 *
 * @see  https://wiki.php.net/rfc/pure-intersection-types
 */
class IntersectionTypesTest extends EmittingTest {

  #[Test]
  public function field_type() {
    $t= $this->declare('class %T {
      private Traversable&Countable $test;
    }');

    Assert::equals(
      new TypeIntersection([new XPClass('Traversable'), new XPClass('Countable')]),
      $t->property('test')->constraint()->type()
    );
  }

  #[Test]
  public function parameter_type() {
    $t= $this->declare('class %T {
      public function test(Traversable&Countable $arg) { }
    }');

    Assert::equals(
      new TypeIntersection([new XPClass('Traversable'), new XPClass('Countable')]),
      $t->method('test')->parameter(0)->constraint()->type()
    );
  }

  #[Test]
  public function return_type() {
    $t= $this->declare('class %T {
      public function test(): Traversable&Countable { }
    }');

    Assert::equals(
      new TypeIntersection([new XPClass('Traversable'), new XPClass('Countable')]),
      $t->method('test')->returns()->type()
    );
  }

  #[Test, Runtime(php: '>=8.1.0-dev')]
  public function field_type_restriction_with_php81() {
    $t= $this->declare('class %T {
      private Traversable&Countable $test;
    }');

    Assert::equals(
      new TypeIntersection([new XPClass('Traversable'), new XPClass('Countable')]),
      $t->property('test')->constraint()->type()
    );
  }

  #[Test, Runtime(php: '>=8.1.0')]
  public function parameter_type_restriction_with_php81() {
    $t= $this->declare('class %T {
      public function test(Traversable&Countable $arg) { }
    }');

    Assert::equals(
      new TypeIntersection([new XPClass('Traversable'), new XPClass('Countable')]),
      $t->method('test')->parameter(0)->constraint()->type()
    );
  }

  #[Test, Runtime(php: '>=8.1.0')]
  public function return_type_restriction_with_php81() {
    $t= $this->declare('class %T {
      public function test(): Traversable&Countable { }
    }');

    Assert::equals(
      new TypeIntersection([new XPClass('Traversable'), new XPClass('Countable')]),
      $t->method('test')->returns()->type()
    );
  }
}