<?php namespace lang\ast\unittest\emit;

use lang\Error;
use unittest\{Assert, Test};

/**
 * Readonly properties
 *
 * @see  https://wiki.php.net/rfc/readonly_properties_v2
 */
class ReadonlyPropertiesTest extends EmittingTest {

  #[Test]
  public function declaration() {
    $t= $this->type('class <T> {
      public readonly int $fixture;
    }');

    Assert::equals(
      sprintf('public readonly int %s::$fixture', $t->getName()),
      $t->getField('fixture')->toString()
    );
  }

  #[Test]
  public function with_constructor_argument_promotion() {
    $t= $this->type('class <T> {
      public function __construct(public readonly string $fixture) { }
    }');

    Assert::equals('Test', $t->newInstance('Test')->fixture);
  }

  #[Test]
  public function reading() {
    $t= $this->type('class <T> {
      public function __construct(public readonly string $fixture) { }
    }');
    Assert::equals('Test', $t->newInstance('Test')->fixture);
  }

  #[Test]
  public function assigning_inside_constructor() {
    $t= $this->type('class <T> {
      public readonly string $fixture;
      public function __construct($fixture) { $this->fixture= $fixture; }
    }');
    Assert::equals('Test', $t->newInstance('Test')->fixture);
  }

  #[Test]
  public function can_be_assigned_via_reflection() {
    $t= $this->type('class <T> {
      public readonly string $fixture;
    }');
    $i= $t->newInstance();
    $t->getField('fixture')->setAccessible(true)->set($i, 'Test');

    Assert::equals('Test', $i->fixture);
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot modify readonly property .+fixture/')]
  public function cannot_be_set_after_initialization() {
    $t= $this->type('class <T> {
      public function __construct(public readonly string $fixture) { }
    }');
    $t->newInstance('Test')->fixture= 'Modified';
  }
}