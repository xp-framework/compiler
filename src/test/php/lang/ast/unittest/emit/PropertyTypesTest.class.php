<?php namespace lang\ast\unittest\emit;

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

    $this->assertEquals('int', $t->getField('test')->getTypeName());
  }

  #[@test]
  public function self_type() {
    $t= $this->type('class <T> {
      private static self $instance;
    }');

    $this->assertEquals('self', $t->getField('instance')->getTypeName());
  }

  #[@test]
  public function interface_type() {
    $t= $this->type('class <T> {
      private \\lang\\Value $value;
    }');

    $this->assertEquals('lang.Value', $t->getField('value')->getTypeName());
  }
}