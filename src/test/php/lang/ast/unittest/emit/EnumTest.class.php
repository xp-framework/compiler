<?php namespace lang\ast\unittest\emit;

use unittest\actions\VerifyThat;
use unittest\{Assert, Action, Test};

#[Action(eval: 'new VerifyThat(fn() => function_exists("enum_exists"))')]
class EnumTest extends EmittingTest {

  #[Test]
  public function name_property() {
    $t= $this->type('enum <T> {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;

      public static function run() {
        return self::Hearts->name;
      }
    }');

    Assert::equals('Hearts', $t->getMethod('run')->invoke(null));
  }

  #[Test]
  public function cases_method() {
    $t= $this->type('enum <T> {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;
    }');

    Assert::equals(
      ['Hearts', 'Diamonds', 'Clubs', 'Spades'],
      array_map(function($suit) { return $suit->name; }, $t->getMethod('cases')->invoke(null))
    );
  }

  #[Test]
  public function used_as_parameter_default() {
    $t= $this->type('enum <T> {
      case ASC;
      case DESC;

      public static function run($order= self::ASC) {
        return $order->name;
      }
    }');

    Assert::equals('ASC', $t->getMethod('run')->invoke(null));
  }

  #[Test]
  public function value_property_of_backed_enum() {
    $t= $this->type('enum <T>: string {
      case ASC  = "asc";
      case DESC = "desc";

      public static function run() {
        return self::DESC->value;
      }
    }');

    Assert::equals('desc', $t->getMethod('run')->invoke(null));
  }

  #[Test, Values([['asc', 'ASC'], ['desc', 'DESC']])]
  public function backed_enum_from($arg, $expected) {
    $t= $this->type('enum <T>: string {
      case ASC  = "asc";
      case DESC = "desc";

      public static function run($arg) {
        return self::from($arg)->name;
      }
    }');

    Assert::equals($expected, $t->getMethod('run')->invoke(null, [$arg]));
  }

  #[Test]
  public function backed_enum_from_nonexistant() {
    $t= $this->type('enum <T>: string {
      case ASC  = "asc";
      case DESC = "desc";

      public static function run() {
        try {
          self::from("illegal");
          throw new \lang\IllegalStateException("No exception raised");
        } catch (\ValueError $expected) {
          return $expected->getMessage();
        }
      }
    }');

    Assert::equals('Not an enum value: "illegal"', $t->getMethod('run')->invoke(null));
  }

  #[Test, Values([['asc', 'ASC'], ['desc', 'DESC'], ['illegal', null]])]
  public function backed_enum_tryFrom($arg, $expected) {
    $t= $this->type('enum <T>: string {
      case ASC  = "asc";
      case DESC = "desc";

      public static function run($arg) {
        return self::tryFrom($arg)?->name;
      }
    }');

    Assert::equals($expected, $t->getMethod('run')->invoke(null, [$arg]));
  }

  #[Test]
  public function enum_values() {
    $t= $this->type('use lang\Enum; enum <T> {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;

      public static function run() {
        return Enum::valuesOf(self::class);
      }
    }');

    Assert::equals(
      ['Hearts', 'Diamonds', 'Clubs', 'Spades'],
      array_map(function($suit) { return $suit->name; }, $t->getMethod('run')->invoke(null))
    );
  }

  #[Test]
  public function enum_value() {
    $t= $this->type('use lang\Enum; enum <T> {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;

      public static function run() {
        return Enum::valueOf(self::class, "Hearts")->name;
      }
    }');

    Assert::equals('Hearts', $t->getMethod('run')->invoke(null));
  }
}