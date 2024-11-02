<?php namespace lang\ast\unittest\emit;

use lang\{ArrayType, MapType, Primitive, Type, Value, XPClass};
use test\verify\Runtime;
use test\{Action, Assert, Test, Values};
use util\Binford;

class ParameterTest extends EmittingTest {
  use AnnotationsOf, NullableSupport;

  /**
   * Helper to declare a type and return a parameter reflection object
   *
   * @param  string $declaration
   * @return lang.reflection.Parameter
   */
  private function param($declaration) {
    return $this->declare('use lang\Value; use util\Binford; class %T { public function fixture('.$declaration.') { } }')
      ->method('fixture')
      ->parameter(0)
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
    Assert::equals('param', $this->param('$param')->name());
  }

  #[Test]
  public function without_type() {
    Assert::equals(Type::$VAR, $this->param('$param')->constraint()->type());
  }

  #[Test, Values(from: 'special')]
  public function with_special_type($declaration, $type) {
    Assert::equals($type, $this->param($declaration)->constraint()->type());
  }

  #[Test]
  public function value_typed() {
    Assert::equals(new XPClass(Value::class), $this->param('Value $param')->constraint()->type());
  }

  #[Test]
  public function value_type_with_null() {
    Assert::equals($this->nullable(new XPClass(Value::class)), $this->param('?Value $param= null')->constraint()->type());
  }

  #[Test]
  public function nullable_value_type() {
    Assert::equals($this->nullable(new XPClass(Value::class)), $this->param('?Value $param')->constraint()->type());
  }

  #[Test]
  public function string_typed() {
    Assert::equals(Primitive::$STRING, $this->param('string $param')->constraint()->type());
  }

  #[Test]
  public function string_typed_with_null() {
    Assert::equals($this->nullable(Primitive::$STRING), $this->param('?string $param= null')->constraint()->type());
  }

  #[Test]
  public function nullable_string_type() {
    Assert::equals($this->nullable(Primitive::$STRING), $this->param('?string $param')->constraint()->type());
  }

  #[Test]
  public function array_typed() {
    Assert::equals(
      new ArrayType(Primitive::$INT),
      $this->param('array<int> $param')->constraint()->type()
    );
  }

  #[Test]
  public function map_typed() {
    Assert::equals(
      new MapType(Primitive::$INT),
      $this->param('array<string, int> $param')->constraint()->type()
    );
  }

  #[Test]
  public function simple_annotation() {
    Assert::equals(['Inject' => []], $this->annotations($this->param('#[Inject] $param')));
  }

  #[Test]
  public function annotation_with_value() {
    Assert::equals(['Inject' => ['dsn']], $this->annotations($this->param('#[Inject("dsn")] $param')));
  }

  #[Test]
  public function multiple_annotations() {
    Assert::equals(
      ['Inject' => [], 'Name' => ['dsn']],
      $this->annotations($this->param('#[Inject, Name("dsn")] $param'))
    );
  }

  #[Test]
  public function required_parameter() {
    Assert::equals(false, $this->param('$param')->optional());
  }

  #[Test]
  public function optional_parameter() {
    Assert::equals(true, $this->param('$param= true')->optional());
  }

  #[Test]
  public function optional_parameters_default_value() {
    Assert::equals(true, $this->param('$param= true')->default());
  }

  #[Test]
  public function new_as_default() {
    $power= $this->param('$power= new Binford(6100)')->default();
    Assert::equals(new Binford(6100), $power);
  }

  #[Test]
  public function closure_as_default() {
    $function= $this->param('$op= fn($in) => $in * 2')->default();
    Assert::equals(2, $function(1));
  }

  #[Test]
  public function trailing_comma_allowed() {
    $p= $this->declare('class %T { public function fixture($param, ) { } }')
      ->method('fixture')
      ->parameters()
    ;
    Assert::equals(1, $p->size(), 'number of parameters');
  }
}