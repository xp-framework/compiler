<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test, Values};

class BracesTest extends EmittingTest {

  #[Test]
  public function inc() {
    $r= $this->run('class <T> {
      private $id= 0;

      public function run() {
        return "test".(++$this->id);
      }
    }');

    Assert::equals('test1', $r);
  }

  #[Test]
  public function braces_around_new() {
    $r= $this->run('class <T> {
      public function run() {
        return (new \\util\\Date(250905600))->getTime();
      }
    }');

    Assert::equals(250905600, $r);
  }

  #[Test]
  public function no_braces_necessary_around_new() {
    $r= $this->run('class <T> {
      public function run() {
        return new \\util\\Date(250905600)->getTime();
      }
    }');

    Assert::equals(250905600, $r);
  }

  #[Test]
  public function property_vs_method_ambiguity() {
    $r= $this->run('class <T> {
      private $f;

      public function __construct() {
        $this->f= function($arg) { return $arg; };
      }

      public function run() {
        return ($this->f)("test");
      }
    }');

    Assert::equals('test', $r);
  }

  #[Test]
  public function nested_braces() {
    $r= $this->run('class <T> {
      private function test() { return "test"; }

      public function run() {
        return (($this->test()));
      }
    }');

    Assert::equals('test', $r);
  }

  #[Test]
  public function braced_expression_not_confused_with_cast() {
    $r= $this->run('class <T> {
      const WIDTH = 640;

      public function run() {
        return (self::WIDTH / 2);
      }
    }');

    Assert::equals(320, $r);
  }

  #[Test, Values(['map' => ['(__LINE__)."test"' => '3test', '(__LINE__) + 1'    => 4, '(__LINE__) - 1'    => 2,]])]
  public function global_constant_in_braces_not_confused_with_cast($input, $expected) {
    $r= $this->run('class <T> {
      public function run() {
        return '.$input.';
      }
    }');

    Assert::equals($expected, $r);
  }

  #[Test]
  public function function_call_in_braces() {
    $r= $this->run('class <T> {
      public function run() {
        $e= STDOUT;
        return false !== (fstat(STDOUT));
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function invoke_on_braced_null_coalesce() {
    $r= $this->run('class <T> {
      public function __invoke() { return "OK"; }
      public function fail() { return function() { return "FAIL"; }; }

      public function run() {
        return ($this ?? $this->fail())();
      }
    }');

    Assert::equals('OK', $r);
  }
}