<?php namespace lang\ast\unittest\emit;

use lang\Primitive;
use lang\ast\Errors;
use test\{Assert, Expect, Test};

/**
 * Argument promotion
 *
 * @see  https://github.com/xp-framework/rfc/issues/240
 * @see  https://docs.hhvm.com/hack/other-features/constructor-parameter-promotion
 * @see  https://wiki.php.net/rfc/constructor_promotion (PHP 8.0)
 * @see  https://wiki.php.net/rfc/final_prompotion
 * @see  https://wiki.php.net/rfc/automatic_property_initialization (Declined)
 */
class ArgumentPromotionTest extends EmittingTest {

  #[Test]
  public function in_constructor() {
    $r= $this->run('class %T {
      public function __construct(private $id= "test") {
        // Empty
      }

      public function run() {
        return $this->id;
      }
    }');
    Assert::equals('test', $r);
  }

  #[Test]
  public function can_be_used_in_constructor() {
    $r= $this->run('class %T {
      public function __construct(private $id= "test") {
        $this->id.= "ed";
      }

      public function run() {
        return $this->id;
      }
    }');
    Assert::equals('tested', $r);
  }

  #[Test]
  public function parameter_accessible() {
    $r= $this->run('class %T {
      public function __construct(private $id= "test") {
        if (null === $id) {
          throw new \\lang\\IllegalArgumentException("ID not set");
        }
      }

      public function run() {
        return $this->id;
      }
    }');
    Assert::equals('test', $r);
  }

  #[Test]
  public function in_method() {
    $r= $this->run('class %T {
      public function withId(private $id) {
        return $this;
      }

      public function run() {
        return $this->withId("test")->id;
      }
    }');
    Assert::equals('test', $r);
  }

  #[Test]
  public function type_information() {
    $t= $this->declare('class %T {
      public function __construct(private int $id, private string $name) { }
    }');
    Assert::equals(
      [Primitive::$INT, Primitive::$STRING],
      [$t->property('id')->constraint()->type(), $t->property('name')->constraint()->type()]
    );
  }

  #[Test, Expect(class: Errors::class, message: '/Variadic parameters cannot be promoted/')]
  public function variadic_parameters_cannot_be_promoted() {
    $this->declare('class %T {
      public function __construct(private string... $in) { }
    }');
  }

  #[Test]
  public function can_be_mixed_with_normal_arguments() {
    $t= $this->declare('class %T {
      public function __construct(public string $name, ?string $initial= null) {
        if (null !== $initial) $this->name.= " ".$initial.".";
      }
    }');

    $names= [];
    foreach ($t->properties() as $property) {
      $names[]= $property->name();
    }

    Assert::equals(['name'], $names);
    Assert::equals('Timm J.', $t->newInstance('Timm', 'J')->name);
  }

  #[Test]
  public function promoted_by_reference_argument() {
    $t= $this->declare('class %T {
      public function __construct(public array &$list) { }

      public static function test() {
        $list= [1, 2, 3];
        $self= new self($list);
        $list[]= 4;
        return $self->list;
      }
    }');

    Assert::equals([1, 2, 3, 4], $t->method('test')->invoke(null, []));
  }

  #[Test]
  public function allows_trailing_comma() {
    $this->declare('class %T {
      public function __construct(
        public float $x = 0.0,
        public float $y = 0.0,
        public float $z = 0.0, // <-- Allow this comma.
      ) { }
    }');
  }

  #[Test]
  public function initializations_have_access() {
    $t= $this->declare('class %T {
      public $first= $this->list[0] ?? null;
      public function __construct(private array $list) { }
    }');
    Assert::equals('Test', $t->newInstance(['Test'])->first);
  }

  #[Test]
  public function promoted_final() {
    $t= $this->declare('class %T {
      public function __construct(public final string $name) { }
    }');

    Assert::equals(MODIFIER_PUBLIC | MODIFIER_FINAL, $t->property('name')->modifiers()->bits());
  }
}