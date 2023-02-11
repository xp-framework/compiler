<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\Reflection;
use lang\{ClassLoader, ClassNotFoundException, Enum, Value};
use test\verify\Runtime;
use test\{Action, Assert, Expect, Test};

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
  public function rewrites_xp_enums() {
    $spec= ['kind' => 'class', 'extends' => [Enum::class], 'implements' => [], 'use' => []];
    $t= ClassLoader::defineType('ReflectionTestXPEnum', $spec, '{
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

  #[Test, Runtime(php: '<8.1')]
  public function rewrites_simulated_unit_enums() {
    $spec= ['kind' => 'class', 'extends' => null, 'implements' => [\UnitEnum::class], 'use' => []];
    $t= ClassLoader::defineType('ReflectionTestSimulatedEnum', $spec, '{
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

  #[Test, Runtime(php: '>=8.1')]
  public function does_not_rewrite_native_enums() {
    $spec= ['kind' => 'enum', 'extends' => null, 'implements' => [], 'use' => []];
    $t= ClassLoader::defineType('ReflectionTestNativeEnum', $spec, '{
      case ONE;

      const EMPTY= null;
    }');

    $reflect= new Reflection($t->literal());
    Assert::false($reflect->rewriteEnumCase('ONE'));
    Assert::false($reflect->rewriteEnumCase('EMPTY'));
  }
}