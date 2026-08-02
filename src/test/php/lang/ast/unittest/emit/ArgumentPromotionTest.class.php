<?php namespace lang\ast\unittest\emit;

use lang\Primitive;
use lang\ast\Errors;
use lang\reflection\InvocationFailed;
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

  #[Test]
  public function promoted_property_hook() {
    $t= $this->declare('class %T {
      public function __construct(
        public private(set) float $celsius= 100 {
          set {
            if ($value < -273.15) {
              throw new \\lang\\IllegalArgumentException($value." is below absolute zero");
            }
            $this->celsius= $value;
          }
        }
      ) { }
    }');

    Assert::equals(0.0, $t->newInstance(0.0)->celsius);
    Assert::throws(InvocationFailed::class, fn() => $t->newInstance(-300.0));
  }

  #[Test]
  public function primary_constructors() {
    $t= $this->declare('class %T(public int $x, public int $y) {
      public function coordinates() {
        return [$this->x, $this->y];
      }
    }');

    Assert::equals([14, 12], $t->newInstance(14, 12)->coordinates());
  }

  #[Test]
  public function primary_constructor_with_parent() {
    $b= $this->declare('class %T {
      public $invoked= 0;
      public function __construct($invoked= 1) { $this->invoked+= $invoked; }
    }');
    $i= $this->declare('class %T(public string $name) extends '.$b->literal().' { }')->newInstance('admin');

    Assert::equals(0, $i->invoked);
    Assert::equals('admin', $i->name);
  }

  #[Test]
  public function primary_constructor_invoking_parent() {
    $b= $this->declare('class %T {
      public $invoked= 0;
      public function __construct($invoked= 1) { $this->invoked+= $invoked; }
    }');
    $i= $this->declare('class %T(public string $name) extends '.$b->literal().'() { }')->newInstance('admin');

    Assert::equals(1, $i->invoked);
    Assert::equals('admin', $i->name);
  }

  #[Test]
  public function primary_constructor_passing_value_to_parent() {
    $b= $this->declare('class %T {
      public $invoked= 0;
      public function __construct($invoked= 1) { $this->invoked+= $invoked; }
    }');
    $i= $this->declare('class %T(public string $name) extends '.$b->literal().'(2) { }')->newInstance('admin');

    Assert::equals(2, $i->invoked);
    Assert::equals('admin', $i->name);
  }

  #[Test]
  public function primary_constructor_passing_param_to_parent() {
    $b= $this->declare('class %T {
      public $invoked= 0;
      public function __construct($invoked= 1) { $this->invoked+= $invoked; }
    }');
    $i= $this->declare('class %T(public string $name, $i= 2) extends '.$b->literal().'($i) { }')->newInstance('admin');

    Assert::equals(2, $i->invoked);
    Assert::equals('admin', $i->name);
  }

  #[Test]
  public function primary_constructor_with_hooks() {
    $t= $this->declare('class %T(
      public private(set) float $celsius {
        set {
          if ($value < -273.15) {
            throw new \lang\IllegalArgumentException("below absolute zero");
          }
          $this->celsius = $value;
        }
      }
    ) { }');

    Assert::equals(0.0, $t->newInstance(0.0)->celsius);
    Assert::throws(InvocationFailed::class, fn() => $t->newInstance(-300.0));
  }

  #[Test]
  public function primary_constructor_doc_comment() {
    $t= $this->declare('/** Test */ class %T(public string $name) { }');

    Assert::equals('Test', $t->comment());
    Assert::equals('Test', $t->constructor()->comment());
  }

  #[Test]
  public function primary_constructor_param_annotations() {
    $t= $this->declare('class %T(#[Inject] public string $name) { }');

    Assert::true($t->constructor()->parameter('name')->annotations()->provides('Inject'));
  }
}