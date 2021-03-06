<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;
use unittest\{Assert, Expect, Test, Values};

/**
 * New in initializers
 *
 * @see  https://wiki.php.net/rfc/new_in_initializers
 */
class InitializeWithNewTest extends EmittingTest {

  #[Test]
  public function property() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      private $h= new Handle(0);

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function property_initialization_accessible_inside_constructor() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      private $h= new Handle(0);

      public function __construct() {
        $this->h->redirect(1);
      }

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(1), $r);
  }

  #[Test]
  public function promoted_property() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function __construct(private $h= new Handle(0)) { }

      public function run() {
        return $this->h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function parameter_default_when_omitted() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run($h= new Handle(0)) {
        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }

  #[Test]
  public function parameter_default_when_passed() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run($h= new Handle(0)) {
        return $h;
      }
    }', new Handle(1));
    Assert::equals(new Handle(1), $r);
  }

  #[Test]
  public function property_reference_as_parameter_default() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      private static $h= new Handle(0);

      public function run($h= self::$h) {
        return $h;
      }
    }');
    Assert::equals(new Handle(0), $r);
  }
}