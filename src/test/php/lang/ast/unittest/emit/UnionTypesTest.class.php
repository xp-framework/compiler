<?php namespace lang\ast\unittest\emit;

use lang\{Primitive, Nullable, Type, TypeUnion};
use unittest\actions\RuntimeVersion;
use unittest\{Assert, Test, Action};

/**
 * Union types
 *
 * @see  https://wiki.php.net/rfc/union_types_v2
 */
class UnionTypesTest extends EmittingTest {

  #[Test]
  public function field_type() {
    $t= $this->type('class <T> {
      private int|string $test;
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getField('test')->getType()
    );
  }

  #[Test]
  public function parameter_type() {
    $t= $this->type('class <T> {
      public function test(int|string $arg) { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getMethod('test')->getParameter(0)->getType()
    );
  }

  #[Test]
  public function return_type() {
    $t= $this->type('class <T> {
      public function test(): int|string { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING]),
      $t->getMethod('test')->getReturnType()
    );
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0.0-dev")')]
  public function nullable_union_type_restriction() {
    $t= $this->type('class <T> {
      public function test(): int|string|null { }
    }');

    Assert::equals(
      new Nullable(new TypeUnion([Primitive::$INT, Primitive::$STRING])),
      $t->getMethod('test')->getTypeRestriction()
    );
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0.0-dev")')]
  public function parameter_type_restriction_with_php8() {
    $t= $this->type('class <T> {
      public function test(int|string|array<string> $arg) { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING, Type::$ARRAY]),
      $t->getMethod('test')->getParameter(0)->getTypeRestriction()
    );
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0.0-dev")')]
  public function parameter_function_type_restriction_with_php8() {
    $t= $this->type('class <T> {
      public function test(): string|(function(): string) { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$STRING, Type::$CALLABLE]),
      $t->getMethod('test')->getReturnTypeRestriction()
    );
  }

  #[Test, Action(eval: 'new RuntimeVersion(">=8.0.0-dev")')]
  public function return_type_restriction_with_php8() {
    $t= $this->type('class <T> {
      public function test(): int|string|array<string> { }
    }');

    Assert::equals(
      new TypeUnion([Primitive::$INT, Primitive::$STRING, Type::$ARRAY]),
      $t->getMethod('test')->getReturnTypeRestriction()
    );
  }
}