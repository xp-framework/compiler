<?php namespace lang\ast\unittest\emit;

use lang\{ArrayType, MapType, Primitive, Type, Value, XPClass};

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
    $this->assertEquals('param', $this->param('$param')->getName());
  }

  #[@test]
  public function without_type() {
    $this->assertEquals(Type::$VAR, $this->param('$param')->getType());
  }

  #[@test, @values([
  #  ['array $param', Type::$ARRAY],
  #  ['callable $param', Type::$CALLABLE],
  #  ['iterable $param', Type::$ITERABLE],
  #  ['object $param', Type::$OBJECT],
  #])]
  public function with_special_type($declaration, $type) {
    $this->assertEquals($type, $this->param($declaration)->getType());
  }

  #[@test]
  public function value_typed() {
    $this->assertEquals(new XPClass(Value::class), $this->param('Value $param')->getType());
  }

  #[@test]
  public function value_type_with_null() {
    $this->assertEquals(new XPClass(Value::class), $this->param('Value $param= null')->getType());
  }

  #[@test]
  public function nullable_value_type() {
    $this->assertEquals(new XPClass(Value::class), $this->param('?Value $param')->getType());
  }

  #[@test]
  public function string_typed() {
    $this->assertEquals(Primitive::$STRING, $this->param('string $param')->getType());
  }

  #[@test]
  public function string_typed_with_null() {
    $this->assertEquals(Primitive::$STRING, $this->param('string $param= null')->getType());
  }

  #[@test]
  public function nullable_string_type() {
    $this->assertEquals(Primitive::$STRING, $this->param('?string $param')->getType());
  }

  #[@test]
  public function array_typed() {
    $this->assertEquals(new ArrayType(Primitive::$INT), $this->param('array<int> $param')->getType());
  }

  #[@test]
  public function map_typed() {
    $this->assertEquals(new MapType(Primitive::$INT), $this->param('array<string, int> $param')->getType());
  }

  #[@test]
  public function simple_annotation() {
    $this->assertEquals(['inject' => null], $this->param('<<inject>> $param')->getAnnotations());
  }

  #[@test]
  public function annotation_with_value() {
    $this->assertEquals(['inject' => 'dsn'], $this->param('<<inject("dsn")>> $param')->getAnnotations());
  }

  #[@test]
  public function multiple_annotations() {
    $this->assertEquals(
      ['inject' => null, 'name' => 'dsn'],
      $this->param('<<inject, name("dsn")>> $param')->getAnnotations()
    );
  }

  #[@test]
  public function required_parameter() {
    $this->assertEquals(false, $this->param('$param')->isOptional());
  }

  #[@test]
  public function optional_parameter() {
    $this->assertEquals(true, $this->param('$param= true')->isOptional());
  }

  #[@test]
  public function optional_parameters_default_value() {
    $this->assertEquals(true, $this->param('$param= true')->getDefaultValue());
  }
}