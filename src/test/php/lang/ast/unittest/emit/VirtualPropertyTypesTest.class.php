<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\php\{XpMeta, VirtualPropertyTypes};
use lang\{Error, Primitive};
use unittest\{Assert, Expect, Test, Values};

class VirtualPropertyTypesTest extends EmittingTest {

  /** @return string[] */
  protected function emitters() { return [XpMeta::class, VirtualPropertyTypes::class]; }

  #[Test]
  public function type_available_via_reflection() {
    $t= $this->type('class <T> {
      private int $value;
    }');

    Assert::equals(Primitive::$INT, $t->getField('value')->getType());
  }

  #[Test]
  public function modifiers_available_via_reflection() {
    $t= $this->type('class <T> {
      private int $value;
    }');

    Assert::equals(MODIFIER_PRIVATE, $t->getField('value')->getModifiers());
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot access private property .+::\\$value/')]
  public function cannot_read_private_field() {
    $t= $this->type('class <T> {
      private int $value;
    }');

    $t->newInstance()->value;
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot access private property .+::\\$value/')]
  public function cannot_write_private_field() {
    $t= $this->type('class <T> {
      private int $value;
    }');

    $t->newInstance()->value= 6100;
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot access protected property .+::\\$value/')]
  public function cannot_read_protected_field() {
    $t= $this->type('class <T> {
      protected int $value;
    }');

    $t->newInstance()->value;
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot access protected property .+::\\$value/')]
  public function cannot_write_protected_field() {
    $t= $this->type('class <T> {
      protected int $value;
    }');

    $t->newInstance()->value= 6100;
  }

  #[Test]
  public function can_access_protected_field_from_subclass() {
    $t= $this->type('class <T> {
      protected int $value;
    }');
    $i= newinstance($t->getName(), [], [
      'run' => function() {
        $this->value= 6100;
        return $this->value;
      }
    ]);

    Assert::equals(6100, $i->run());
  }

  #[Test]
  public function initial_value_available_via_reflection() {
    $t= $this->type('class <T> {
      private int $value = 6100;
    }');

    Assert::equals(6100, $t->getField('value')->setAccessible(true)->get($t->newInstance()));
  }

  #[Test, Values([[null], ['Test'], [[]]]), Expect(class: Error::class, withMessage: '/property .+::\$value of type int/')]
  public function type_checked_at_runtime($in) {
    $this->run('class <T> {
      private int $value;

      public function run($arg) {
        $this->value= $arg;
      }
    }', $in);
  }

  #[Test]
  public function value_type_test() {
    $handle= new Handle(0);
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      private Handle $value;

      public function run($arg) {
        $this->value= $arg;
        return $this->value;
      }
    }', $handle);

    Assert::equals($handle, $r);
  }

  #[Test, Values(['', 'Test', 1, 1.5, true, false])]
  public function string_type_coercion($in) {
    $r= $this->run('class <T> {
      private string $value;

      public function run($arg) {
        $this->value= $arg;
        return $this->value;
      }
    }', $in);

    Assert::equals((string)$in, $r);
  }

  #[Test, Values(['', 'Test', 1, 1.5, true, false])]
  public function bool_type_coercion($in) {
    $r= $this->run('class <T> {
      private bool $value;

      public function run($arg) {
        $this->value= $arg;
        return $this->value;
      }
    }', $in);

    Assert::equals((bool)$in, $r);
  }

  #[Test, Values(['1', '1.5', 1, 1.5, true, false])]
  public function int_type_coercion($in) {
    $r= $this->run('class <T> {
      private int $value;

      public function run($arg) {
        $this->value= $arg;
        return $this->value;
      }
    }', $in);

    Assert::equals((int)$in, $r);
  }

  #[Test, Values(['1', '1.5', 1, 1.5, true, false])]
  public function float_type_coercion($in) {
    $r= $this->run('class <T> {
      private float $value;

      public function run($arg) {
        $this->value= $arg;
        return $this->value;
      }
    }', $in);

    Assert::equals((float)$in, $r);
  }
}