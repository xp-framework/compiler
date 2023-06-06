<?php namespace lang\ast\unittest\emit;

use lang\{XPClass, Primitive};
use test\{Assert, Test};

/**
 * Property types
 *
 * @see  https://docs.hhvm.com/hack/types/type-system
 * @see  https://wiki.php.net/rfc/typed_properties_v2
 */
class PropertyTypesTest extends EmittingTest {

  #[Test]
  public function int_type() {
    $t= $this->declare('class %T {
      private int $test;
    }');

    Assert::equals(Primitive::$INT, $t->property('test')->constraint()->type());
  }

  #[Test]
  public function self_type() {
    $t= $this->declare('class %T {
      private static self $instance;
    }');

    Assert::equals($t->class(), $t->property('instance')->constraint()->type());
  }

  #[Test]
  public function interface_type() {
    $t= $this->declare('class %T {
      private \\lang\\Value $value;
    }');

    Assert::equals(XPClass::forName('lang.Value'), $t->property('value')->constraint()->type());
  }
}