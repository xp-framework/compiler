<?php namespace lang\ast\unittest\emit;

use unittest\actions\RuntimeVersion;
use unittest\{Assert, Action, Test, Values};

class PropertiesTest extends EmittingTest {

  /**
   * Helper to create a class with a property restricted to a given type
   *
   * @param  string $property
   * @return lang.XPClass
   */
  private function fixtureWith($property) {
    return $this->type('use lang\ast\unittest\emit\Handle; class <T> {
      '.$property.'
      public function __construct($input) { $this->property= $input; }
    }');
  }

  #[Test, Values([null, 'Test', 1, 1.5, true, false, [[1, 2, 3]]])]
  public function without_type($arg) {
    Assert::equals($arg, $this->fixtureWith('public $property;')->newInstance($arg)->property);
  }

  #[Test, Values(['', 'Test'])]
  public function use_string($arg) {
    Assert::equals($arg, $this->fixtureWith('public string $property;')->newInstance($arg)->property);
  }

  #[Test, Values([[null], [[1, 2, 3]]])]
  public function cannot_assing_string_from($arg) {
    Assert::throws(\TypeError::class, function() use($arg) {
      $this->fixtureWith('public string $property;')->newInstance($arg);
    });
  }

  #[Test, Values([1, 1.5, true, false])]
  public function string_type_coerces_primitive($arg) {
    Assert::equals((string)$arg, $this->fixtureWith('public string $property;')->newInstance($arg)->property);
  }

  #[Test]
  public function initial_value() {
    $t= $this->type('class <T> {
      public string $property= "Test";
    }');

    Assert::equals('Test', $t->newInstance()->property);
  }

  #[Test]
  public function initial_expression() {
    $t= $this->type('use lang\ast\unittest\emit\Handle; class <T> {
      public Handle $property= new Handle(1);
    }');

    Assert::equals(new Handle(1), $t->newInstance()->property);
  }

  #[Test, Values(eval: '[null, new Handle(1)]')]
  public function use_nullable($arg) {
    Assert::equals($arg, $this->fixtureWith('public ?Handle $property;')->newInstance($arg)->property);
  }

  #[Test, Values([1, 'Test', false])]
  public function cannot_assign_handle_from($arg) {
    Assert::throws(\TypeError::class, function() use($arg) {
      $this->fixtureWith('public ?Handle $property;')->newInstance($arg);
    });
  }

  #[Test, Action(eval: 'new RuntimeVersion("<=7.4")')]
  public function static_properties_left_untyped() {
    $t= $this->type('class <T> {
      public static string $member;
      public static function violate() { self::$member= ["Test"]; }
    }');

    // Since PHP does not have __get and __set for static members, we degrade
    // to untyped properties. See https://bugs.php.net/bug.php?id=52225
    $t->getMethod('violate')->invoke(null, []);
    Assert::equals(['Test'], $t->getField('member')->get(null));
  }

  #[Test]
  public function reading_from_undefined_members_raises_error() {
    set_error_handler(function($error, $message, $file, $line) use(&$caught) { $caught= $message; });
    try {
      $t= $this->type('class <T> {
        private string $name= "Test";
        public function run() { return [$this->id, $this->name]; }
      }');
      $result= $t->getMethod('run')->invoke($t->newInstance());

      Assert::equals([null, 'Test'], $result);
      Assert::equals('Undefined property: '.$t->getName().'::$id', $caught);
    } finally {
      restore_error_handler();
    }
  }
}