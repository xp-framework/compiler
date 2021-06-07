<?php namespace lang\ast\unittest\emit;

use lang\Error;
use unittest\{Assert, AssertionFailedError, Test};

class DeclareTest extends EmittingTest {

  #[Test]
  public function no_strict_types() {
    Assert::equals(1, $this->run('class <T> {
      public static function number(int $n) { return $n; }
      public function run() { return self::number("1"); }
    }'));
  }

  #[Test]
  public function strict_types_off() {
    Assert::equals(1, $this->run('declare(strict_types = 0); class <T> {
      public static function number(int $n) { return $n; }
      public function run() { return self::number("1"); }
    }'));
  }

  #[Test, Expect(class: Error::class, withMessage: '/must be of (the )?type int(eger)?, string given/')]
  public function strict_types_on() {
    $this->run('declare(strict_types = 1); class <T> {
      public static function number(int $n) { return $n; }
      public function run() { return self::number("1"); }
    }');
  }
}
