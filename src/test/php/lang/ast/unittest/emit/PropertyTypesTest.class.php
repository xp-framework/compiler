<?php namespace lang\ast\unittest\emit;

use unittest\Assert;
/**
 * Property types
 *
 * @see  https://docs.hhvm.com/hack/types/type-system
 * @see  https://wiki.php.net/rfc/property_type_hints (Draft)
 */
class PropertyTypesTest extends EmittingTest {

  #[@test]
  public function int_type() {
    $t= $this->type('class <T> {
      private int $test;
    }');

    Assert::equals('int', $t->getField('test')->getTypeName());
  }

  #[@test]
  public function self_type() {
    $t= $this->type('class <T> {
      private static self $instance;
    }');

    Assert::equals('self', $t->getField('instance')->getTypeName());
  }

  #[@test]
  public function interface_type() {
    $t= $this->type('class <T> {
      private \\lang\\Value $value;
    }');

    Assert::equals('lang.Value', $t->getField('value')->getTypeName());
  }
}