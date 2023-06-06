<?php namespace lang\ast\unittest\emit;

use lang\{Nullable, Primitive, Type, TypeUnion};
use test\verify\Runtime;
use test\{Action, Assert, Test};

/**
 * Union types
 *
 * @see  https://wiki.php.net/rfc/union_types_v2
 */
class UnionTypesTest extends EmittingTest {

  #[Test]
  public function field_type() {
    $t= $this->declare('class %T {
      private int|string $test;
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->property('test')->constraint()->type()
    );
  }

  #[Test]
  public function parameter_type() {
    $t= $this->declare('class %T {
      public function test(int|string $arg) { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->method('test')->parameter(0)->constraint()->type()
    );
  }

  #[Test]
  public function return_type() {
    $t= $this->declare('class %T {
      public function test(): int|string { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->method('test')->returns()->type()
    );
  }

  #[Test]
  public function nullable_union_type() {
    $t= $this->declare('class %T {
      public function test(): int|string|null { }
    }');

    Assert::equals(
      new Nullable(new TypeUnion([Primitive::$INT, Primitive::$STRING])),
      $t->method('test')->returns()->type()
    );
  }

  #[Test]
  public function nullable_union_type_alternative_syntax() {
    $t= $this->declare('class %T {
      public function test(): ?(int|string) { }
    }');

    Assert::equals(
      new Nullable(new TypeUnion([Primitive::$INT, Primitive::$STRING])),
      $t->method('test')->returns()->type()
    );
  }

  #[Test, Runtime(php: '>=8.0.0-dev')]
  public function nullable_union_type_restriction() {
    $t= $this->declare('class %T {
      public function test(): int|string|null { }
    }');

    Assert::equals(
      new Nullable(new TypeUnion([Primitive::$INT, Primitive::$STRING])),
      $t->method('test')->returns()->type()
    );
  }

  #[Test, Runtime(php: '>=8.0.0-dev')]
  public function parameter_type_restriction_with_php8() {
    $t= $this->declare('class %T {
      public function test(int|string|array<string> $arg) { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING, Type::$ARRAY]),
      $t->method('test')->parameter(0)->constraint()->type()
    );
  }

  #[Test, Runtime(php: '>=8.0.0-dev')]
  public function parameter_function_type_restriction_with_php8() {
    $t= $this->declare('class %T {
      public function test(): string|(function(): string) { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$STRING, Type::$CALLABLE]),
      $t->method('test')->returns()->type()
    );
  }

  #[Test, Runtime(php: '>=8.0.0-dev')]
  public function return_type_restriction_with_php8() {
    $t= $this->declare('class %T {
      public function test(): int|string|array<string> { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING, Type::$ARRAY]),
      $t->method('test')->returns()->type()
    );
  }
}