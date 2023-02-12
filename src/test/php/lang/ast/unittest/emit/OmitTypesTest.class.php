<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\{OmitPropertyTypes, OmitReturnTypes};
use lang\ast\types\IsLiteral;
use test\{Assert, Test};

class OmitTypesTest {
  use OmitPropertyTypes, OmitReturnTypes;

  #[Test]
  public function property_type() {
    Assert::equals('', $this->propertyType(new IsLiteral('int')));
  }

  #[Test]
  public function return_type() {
    Assert::equals('', $this->returnType(new IsLiteral('int')));
  }
}