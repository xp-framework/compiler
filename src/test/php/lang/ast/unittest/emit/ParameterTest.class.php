<?php namespace lang\ast\unittest\emit;

use lang\{ArrayType, MapType, Primitive, Type, Value, XPClass};
use unittest\Assert;

class ParameterTest extends EmittingTest {

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

  #[@test]
  public function name() {
    Assert::equals('param', $this->param('$param')->getName());
  }

  #[@test]
  public function without_type() {
    Assert::equals(Type::$VAR, $this->param('$param')->getType());
  }

  #[@test, @values([
  #  ['array $param', Type::$ARRAY],
  #  ['callable $param', Type::$CALLABLE],
  #  ['iterable $param', Type::$ITERABLE],
  #  ['object $param', Type::$OBJECT],
  #])]
  public function with_special_type($declaration, $type) {
    Assert::equals($type, $this->param($declaration)->getType());
  }

  #[@test]
  public function value_typed() {
    Assert::equals(new XPClass(Value::class), $this->param('Value $param')->getType());
  }

  #[@test]
  public function value_type_with_null() {
    Assert::equals(new XPClass(Value::class), $this->param('Value $param= null')->getType());
  }

  #[@test]
  public function nullable_value_type() {
    Assert::equals(new XPClass(Value::class), $this->param('?Value $param')->getType());
  }

  #[@test]
  public function string_typed() {
    Assert::equals(Primitive::$STRING, $this->param('string $param')->getType());
  }

  #[@test]
  public function string_typed_with_null() {
    Assert::equals(Primitive::$STRING, $this->param('string $param= null')->getType());
  }

  #[@test]
  public function nullable_string_type() {
    Assert::equals(Primitive::$STRING, $this->param('?string $param')->getType());
  }

  #[@test]
  public function array_typed() {
    Assert::equals(new ArrayType(Primitive::$INT), $this->param('array<int> $param')->getType());
  }

  #[@test]
  public function map_typed() {
    Assert::equals(new MapType(Primitive::$INT), $this->param('array<string, int> $param')->getType());
  }

  #[@test]
  public function simple_annotation() {
    Assert::equals(['inject' => null], $this->param('#[Inject] $param')->getAnnotations());
  }

  #[@test]
  public function annotation_with_value() {
    Assert::equals(['inject' => 'dsn'], $this->param('#[Inject("dsn")] $param')->getAnnotations());
  }

  #[@test]
  public function multiple_annotations() {
    Assert::equals(
      ['inject' => null, 'name' => 'dsn'],
      $this->param('#[Inject, Name("dsn")] $param')->getAnnotations()
    );
  }

  #[@test]
  public function required_parameter() {
    Assert::equals(false, $this->param('$param')->isOptional());
  }

  #[@test]
  public function optional_parameter() {
    Assert::equals(true, $this->param('$param= true')->isOptional());
  }

  #[@test]
  public function optional_parameters_default_value() {
    Assert::equals(true, $this->param('$param= true')->getDefaultValue());
  }

  #[@test]
  public function trailing_comma_allowed() {
    $p= $this->type('class <T> { public function fixture($param, ) { } }')
      ->getMethod('fixture')
      ->getParameters()
    ;
    Assert::equals(1, sizeof($p), 'number of parameters');
  }
}