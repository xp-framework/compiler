<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\Reflection;
use lang\{Value, ClassNotFoundException, ClassLoader};
use unittest\{Assert, Test, Expect};

class ReflectionTest {

  #[Test]
  public function can_create() {
    new Reflection(Value::class);
  }

  #[Test, Expect(ClassNotFoundException::class)]
  public function raises_exception_when_class_does_not_exist() {
    new Reflection('NotFound');
  }

  #[Test]
  public function name() {
    Assert::equals(Value::class, (new Reflection(Value::class))->name());
  }

  #[Test]
  public function rewrites_unit_enums() {
    $t= ClassLoader::defineClass('ReflectionTestEnum', null, [\UnitEnum::class], '{
      public static $ONE;

      public static $EMPTY= null;

      static function __static() {
        self::$ONE= new self();
      }
    }');

    $reflect= new Reflection($t->literal());
    Assert::true($reflect->rewriteEnumCase('ONE'));
    Assert::false($reflect->rewriteEnumCase('EMPTY'));
  }
}