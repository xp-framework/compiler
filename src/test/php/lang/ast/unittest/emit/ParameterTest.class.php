<?php namespace lang\ast\unittest\emit;

use lang\{ArrayType, MapType, Primitive, Type, Value, XPClass};
use test\verify\Runtime;
use test\{Action, Assert, Test, Values};

class ParameterTest extends EmittingTest {
  use NullableSupport;

  /**
   * Helper to declare a type and return a parameter reflection object
   *
   * @param  string $declaration
   * @return lang.reflect.Parameter
   */
  private function param($declaration) {
    return $this->type('use lang\Value; class <T> { public function fixture('.$declaration.') { } }')
      ->getMethod('fixture')
      ->getParameter(0)
    ;
  }

  /** @return iterable */
  private function special() {
    yield ['array $param', Type::$ARRAY];
    yield ['callable $param', Type::$CALLABLE];
    yield ['iterable $param', Type::$ITERABLE];
    yield ['object $param', Type::$OBJECT];
  }

  #[Test]
  public function name() {
    Assert::equals('param', $this->param('$param')->getName());
  }

  #[Test]
  public function without_type() {
    Assert::equals(Type::$VAR, $this->param('$param')->getType());
  }

  #[Test, Values(from: 'special')]
  public function with_special_type($declaration, $type) {
    Assert::equals($type, $this->param($declaration)->getType());
  }

  #[Test]
  public function value_typed() {
    Assert::equals(new XPClass(Value::class), $this->param('Value $param')->getType());
  }

  #[Test]
  public function value_type_with_null() {
    Assert::equals($this->nullable(new XPClass(Value::class)), $this->param('Value $param= null')->getType());
  }

  #[Test]
  public function nullable_value_type() {
    Assert::equals($this->nullable(new XPClass(Value::class)), $this->param('?Value $param')->getType());
  }

  #[Test]
  public function string_typed() {
    Assert::equals(Primitive::$STRING, $this->param('string $param')->getType());
  }

  #[Test]
  public function string_typed_with_null() {
    Assert::equals($this->nullable(Primitive::$STRING), $this->param('string $param= null')->getType());
  }

  #[Test]
  public function nullable_string_type() {
    Assert::equals($this->nullable(Primitive::$STRING), $this->param('?string $param')->getType());
  }

  #[Test, Runtime(php: '>=7.1')]
  public function nullable_string_type_restriction() {
    Assert::equals($this->nullable(Primitive::$STRING), $this->param('?string $param')->getTypeRestriction());
  }

  #[Test]
  public function array_typed() {
    Assert::equals(new ArrayType(Primitive::$INT), $this->param('array<int> $param')->getType());
  }

  #[Test]
  public function array_typed_restriction() {
    Assert::equals(Type::$ARRAY, $this->param('array<int> $param')->getTypeRestriction());
  }

  #[Test]
  public function map_typed() {
    Assert::equals(new MapType(Primitive::$INT), $this->param('array<string, int> $param')->getType());
  }

  #[Test]
  public function map_typed_restriction() {
    Assert::equals(Type::$ARRAY, $this->param('array<string, int> $param')->getTypeRestriction());
  }

  #[Test]
  public function simple_annotation() {
    Assert::equals(['inject' => null], $this->param('#[Inject] $param')->getAnnotations());
  }

  #[Test]
  public function annotation_with_value() {
    Assert::equals(['inject' => 'dsn'], $this->param('#[Inject("dsn")] $param')->getAnnotations());
  }

  #[Test]
  public function multiple_annotations() {
    Assert::equals(
      ['inject' => null, 'name' => 'dsn'],
      $this->param('#[Inject, Name("dsn")] $param')->getAnnotations()
    );
  }

  #[Test]
  public function required_parameter() {
    Assert::equals(false, $this->param('$param')->isOptional());
  }

  #[Test]
  public function optional_parameter() {
    Assert::equals(true, $this->param('$param= true')->isOptional());
  }

  #[Test]
  public function optional_parameters_default_value() {
    Assert::equals(true, $this->param('$param= true')->getDefaultValue());
  }

  #[Test]
  public function trailing_comma_allowed() {
    $p= $this->type('class <T> { public function fixture($param, ) { } }')
      ->getMethod('fixture')
      ->getParameters()
    ;
    Assert::equals(1, sizeof($p), 'number of parameters');
  }
}