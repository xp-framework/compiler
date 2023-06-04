<?php namespace lang\ast\unittest\emit;

use lang\reflection\{Kind, InvocationFailed};
use lang\{Enum, Error};
use test\verify\Condition;
use test\{Action, Assert, Expect, Ignore, Test, Values};

#[Condition(assert: 'function_exists("enum_exists")')]
class EnumTest extends EmittingTest {

  /**
   * Returns annotations present in the given type
   *
   * @param  lang.reflection.Annotated $annotated
   * @return [:var[]]
   */
  private function annotations($annotated) {
    $r= [];
    foreach ($annotated->annotations() as $name => $annotation) {
      $r[$name]= $annotation->arguments();
    }
    return $r;
  }

  #[Test]
  public function enum_type() {
    Assert::equals(Kind::$ENUM, $this->declare('enum %T { }')->kind());
  }

  #[Test]
  public function name_property() {
    $t= $this->declare('enum %T {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;

      public static function run() {
        return self::Hearts->name;
      }
    }');

    Assert::equals('Hearts', $t->method('run')->invoke(null));
  }

  #[Test]
  public function cases_method_for_unit_enums() {
    $t= $this->declare('enum %T {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;
    }');

    Assert::equals(
      ['Hearts', 'Diamonds', 'Clubs', 'Spades'],
      array_map(function($suit) { return $suit->name; }, $t->method('cases')->invoke(null))
    );
  }

  #[Test]
  public function cases_method_for_backed_enums() {
    $t= $this->declare('enum %T: string {
      case Hearts = "♥";
      case Diamonds = "♦";
      case Clubs = "♣";
      case Spades = "♠";
    }');

    Assert::equals(
      ['Hearts', 'Diamonds', 'Clubs', 'Spades'],
      array_map(function($suit) { return $suit->name; }, $t->method('cases')->invoke(null))
    );
  }

  #[Test]
  public function cases_method_does_not_yield_constants() {
    $t= $this->declare('enum %T {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;

      const COLORS = ["red", "black"];
    }');

    Assert::equals(
      ['Hearts', 'Diamonds', 'Clubs', 'Spades'],
      array_map(function($suit) { return $suit->name; }, $t->method('cases')->invoke(null))
    );
  }

  #[Test]
  public function used_as_parameter_default() {
    $t= $this->declare('enum %T {
      case ASC;
      case DESC;

      public static function run($order= self::ASC) {
        return $order->name;
      }
    }');

    Assert::equals('ASC', $t->method('run')->invoke(null));
  }

  #[Test]
  public function overwritten_parameter_default_value() {
    $t= $this->declare('enum %T {
      case ASC;
      case DESC;

      public static function run($order= self::ASC) {
        return $order->name;
      }
    }');

    Assert::equals('DESC', $t->method('run')->invoke(null, [Enum::valueOf($t, 'DESC')]));
  }

  #[Test]
  public function value_property_of_backed_enum() {
    $t= $this->declare('enum %T: string {
      case ASC  = "asc";
      case DESC = "desc";

      public static function run() {
        return self::DESC->value;
      }
    }');

    Assert::equals('desc', $t->method('run')->invoke(null));
  }

  #[Test, Values([[0, 'NO'], [1, 'YES']])]
  public function backed_enum_from_int($arg, $expected) {
    $t= $this->declare('enum %T: int {
      case NO  = 0;
      case YES = 1;
    }');

    Assert::equals($expected, $t->method('from')->invoke(null, [$arg])->name);
  }

  #[Test, Values([['asc', 'ASC'], ['desc', 'DESC']])]
  public function backed_enum_from_string($arg, $expected) {
    $t= $this->declare('enum %T: string {
      case ASC  = "asc";
      case DESC = "desc";
    }');

    Assert::equals($expected, $t->method('from')->invoke(null, [$arg])->name);
  }

  #[Test, Expect(class: Error::class, message: '/"illegal" is not a valid backing value for enum .+/')]
  public function backed_enum_from_nonexistant() {
    $t= $this->declare('enum %T: string {
      case ASC  = "asc";
      case DESC = "desc";
    }');
    try {
      $t->method('from')->invoke(null, ['illegal']);
    } catch (InvocationFailed $e) {
      throw $e->getCause();
    }
  }

  #[Test, Values([['asc', 'ASC'], ['desc', 'DESC'], ['illegal', null]])]
  public function backed_enum_tryFrom($arg, $expected) {
    $t= $this->declare('enum %T: string {
      case ASC  = "asc";
      case DESC = "desc";

      public static function run($arg) {
        return self::tryFrom($arg)?->name;
      }
    }');

    Assert::equals($expected, $t->method('run')->invoke(null, [$arg]));
  }

  #[Test]
  public function declare_method_on_enum() {
    $t= $this->declare('enum %T {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;

      public function color() {
        return match ($this) {
          self::Hearts, self::Diamonds => "red",
          self::Clubs, self::Spaces    => "black",
        };
      }

      public static function run() {
        return self::Hearts->color();
      }
    }');

    Assert::equals('red', $t->method('run')->invoke(null));
  }

  #[Test]
  public function enum_implementing_interface() {
    $t= $this->declare('use lang\Closeable; enum %T implements Closeable {
      case File;
      case Stream;

      public function close() { }
    }');

    Assert::true($t->is('lang.Closeable'));
  }

  #[Test]
  public function enum_annotations() {
    $t= $this->declare('#[Test] enum %T { }');
    Assert::equals(['Test' => []], $this->annotations($t));
  }

  #[Test, Ignore('XP reflection does not support constant annotations')]
  public function enum_member_annotations() {
    $t= $this->declare('enum %T { #[Test] case ONE; }');
    Assert::equals(['Test' => []], $this->annotations($t->constant('ONE')));
  }

  #[Test]
  public function cannot_be_cloned() {
    $t= $this->declare('use lang\IllegalStateException; enum %T {
      case ONE;

      public static function run() {
        try {
          return clone self::ONE;
          throw new IllegalStateException("No exception raised");
        } catch (\Error $expected) {
          return $expected->getMessage();
        }
      }
    }');

    Assert::equals(
      'Trying to clone an uncloneable object of class '.$t->literal(),
      $t->method('run')->invoke(null)
    );
  }

  #[Test]
  public function enum_values() {
    $t= $this->declare('enum %T {
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
    $t= $this->declare('enum %T {
      case Hearts;
      case Diamonds;
      case Clubs;
      case Spades;
    }');

    Assert::equals('Hearts', Enum::valueOf($t, 'Hearts')->name);
  }
}