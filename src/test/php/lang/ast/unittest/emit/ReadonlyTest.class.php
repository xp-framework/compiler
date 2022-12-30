<?php namespace lang\ast\unittest\emit;

use lang\Error;
use unittest\{Assert, Expect, Ignore, Values, Test};

/**
 * Readonly classes and properties
 *
 * @see  https://wiki.php.net/rfc/readonly_properties_v2
 * @see  https://wiki.php.net/rfc/readonly_classes
 */
class ReadonlyTest extends EmittingTest {

  /** @return iterable */
  private function modifiers() {
    return [
      ['public'],
      ['protected'],
      ['private'],
    ];
  }

  #[Test]
  public function class_declaration() {
    $t= $this->type('readonly class <T> {
      public int $fixture;
    }');

    Assert::equals(
      sprintf('public readonly int %s::$fixture', $t->getName()),
      $t->getField('fixture')->toString()
    );
  }

  #[Test]
  public function property_declaration() {
    $t= $this->type('class <T> {
      public readonly int $fixture;
    }');

    Assert::equals(
      sprintf('public readonly int %s::$fixture', $t->getName()),
      $t->getField('fixture')->toString()
    );
  }

  #[Test]
  public function class_with_constructor_argument_promotion() {
    $t= $this->type('readonly class <T> {
      public function __construct(public string $fixture) { }
    }');

    Assert::equals(
      sprintf('public readonly string %s::$fixture', $t->getName()),
      $t->getField('fixture')->toString()
    );
    Assert::equals('Test', $t->newInstance('Test')->fixture);
  }

  #[Test]
  public function property_defined_with_constructor_argument_promotion() {
    $t= $this->type('class <T> {
      public function __construct(public readonly string $fixture) { }
    }');

    Assert::equals(
      sprintf('public readonly string %s::$fixture', $t->getName()),
      $t->getField('fixture')->toString()
    );
    Assert::equals('Test', $t->newInstance('Test')->fixture);
  }

  #[Test, Values('modifiers')]
  public function reading_from_class($modifiers) {
    $t= $this->type('class <T> {
      public function __construct('.$modifiers.' readonly string $fixture) { }

      public function run() { return $this->fixture; }
    }');
    Assert::equals('Test', $t->newInstance('Test')->run());
  }

  #[Test]
  public function reading_public_from_outside() {
    $t= $this->type('class <T> {
      public function __construct(public readonly string $fixture) { }
    }');
    Assert::equals('Test', $t->newInstance('Test')->fixture);
  }

  #[Test]
  public function reading_protected_from_subclass() {
    $t= $this->type('class <T> {
      public function __construct(protected readonly string $fixture) { }
    }');
    $i= newinstance($t->getName(), ['Test'], [
      'run' => function() { return $this->fixture; }
    ]);
    Assert::equals('Test', $i->run());
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot access protected property .+fixture/')]
  public function cannot_read_protected() {
    $t= $this->type('class <T> {
      public function __construct(protected readonly string $fixture) { }
    }');
    $t->newInstance('Test')->fixture;
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot access protected property .+fixture/')]
  public function cannot_write_protected() {
    $t= $this->type('class <T> {
      public function __construct(protected readonly string $fixture) { }
    }');
    $t->newInstance('Test')->fixture= 'Modified';
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot access private property .+fixture/')]
  public function cannot_read_private() {
    $t= $this->type('class <T> {
      public function __construct(private readonly string $fixture) { }
    }');
    $t->newInstance('Test')->fixture;
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot access private property .+fixture/')]
  public function cannot_write_private() {
    $t= $this->type('class <T> {
      public function __construct(private readonly string $fixture) { }
    }');
    $t->newInstance('Test')->fixture= 'Modified';
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

  #[Test, Expect(class: Error::class, withMessage: '/Cannot initialize readonly property .+fixture/')]
  public function cannot_initialize_from_outside() {
    $t= $this->type('class <T> {
      public readonly string $fixture;
    }');
    $t->newInstance()->fixture= 'Test';
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot modify readonly property .+fixture/')]
  public function cannot_be_set_after_initialization() {
    $t= $this->type('class <T> {
      public function __construct(public readonly string $fixture) { }
    }');
    $t->newInstance('Test')->fixture= 'Modified';
  }

  #[Test, Ignore('Until proper error handling facilities exist')]
  public function cannot_have_an_initial_value() {
    $this->type('class <T> {
      public readonly string $fixture= "Test";
    }');
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot create dynamic property .+fixture/')]
  public function cannot_read_dynamic_members_from_readonly_classes() {
    $t= $this->type('readonly class <T> { }');
    $t->newInstance()->fixture;
  }

  #[Test, Expect(class: Error::class, withMessage: '/Cannot create dynamic property .+fixture/')]
  public function cannot_write_dynamic_members_from_readonly_classes() {
    $t= $this->type('readonly class <T> { }');
    $t->newInstance()->fixture= true;
  }

  #[Test, Ignore('Until proper error handling facilities exist')]
  public function readonly_classes_cannot_have_static_members() {
    $this->type('readonly class <T> {
      public static $test;
    }');
  }
}