<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test};
use util\Date;

class InstantiationTest extends EmittingTest {

  #[Test]
  public function new_type() {
    $r= $this->run('class <T> {
      public function run() {
        return new \\util\\Date();
      }
    }');
    Assert::instance(Date::class, $r);
  }

  #[Test]
  public function new_var() {
    $r= $this->run('class <T> {
      public function run() {
        $class= \\util\\Date::class;
        return new $class();
      }
    }');
    Assert::instance(Date::class, $r);
  }

  #[Test]
  public function new_expr() {
    $r= $this->run('class <T> {
      private function factory() { return \\util\\Date::class; }

      public function run() {
        return new ($this->factory())();
      }
    }');
    Assert::instance(Date::class, $r);
  }

  #[Test]
  public function passing_argument() {
    $r= $this->run('class <T> {
      public $value;

      public function __construct($value= null) { $this->value= $value; }

      public function run() {
        return new self("Test");
      }
    }');
    Assert::equals('Test', $r->value);
  }
}