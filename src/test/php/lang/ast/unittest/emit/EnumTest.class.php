<?php namespace lang\ast\unittest\emit;

use lang\Enum;
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
  public function cases_method_does_not_yield_constants() {
    $t= $this->type('enum <T> {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;

      const COLORS = ["red", "black"];
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
  public function overwritten_parameter_default_value() {
    $t= $this->type('enum <T> {
      case ASC;
      case DESC;

      public static function run($order= self::ASC) {
        return $order->name;
      }
    }');

    Assert::equals('DESC', $t->getMethod('run')->invoke(null, [Enum::valueOf($t, 'DESC')]));
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

  #[Test, Values([[0, 'NO'], [1, 'YES']])]
  public function backed_enum_from_int($arg, $expected) {
    $t= $this->type('enum <T>: int {
      case NO  = 0;
      case YES = 1;
    }');

    Assert::equals($expected, $t->getMethod('from')->invoke(null, [$arg])->name);
  }

  #[Test, Values([['asc', 'ASC'], ['desc', 'DESC']])]
  public function backed_enum_from_string($arg, $expected) {
    $t= $this->type('enum <T>: string {
      case ASC  = "asc";
      case DESC = "desc";
    }');

    Assert::equals($expected, $t->getMethod('from')->invoke(null, [$arg])->name);
  }

  #[Test]
  public function backed_enum_from_nonexistant() {
    $t= $this->type('use lang\IllegalStateException; enum <T>: string {
      case ASC  = "asc";
      case DESC = "desc";

      public static function run() {
        try {
          self::from("illegal");
          throw new IllegalStateException("No exception raised");
        } catch (\Error $expected) {
          return $expected->getMessage();
        }
      }
    }');

    Assert::equals(
      '"illegal" is not a valid backing value for enum "'.$t->literal().'"',
      $t->getMethod('run')->invoke(null)
    );
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
    $t= $this->type('enum <T> {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;
    }');

    Assert::equals(
      ['Hearts', 'Diamonds', 'Clubs', 'Spades'],
      array_map(function($suit) { return $suit->name; }, Enum::valuesOf($t))
    );
  }

  #[Test]
  public function enum_value() {
    $t= $this->type('enum <T> {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;
    }');

    Assert::equals('Hearts', Enum::valueOf($t, 'Hearts')->name);
  }
}