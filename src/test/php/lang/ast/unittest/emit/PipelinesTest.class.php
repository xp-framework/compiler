<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test, Values};

class PipelinesTest extends EmittingTest {

  #[Test]
  public function pipe_to_callable() {
    $r= $this->run('class %T {
      public function run() {
        return "test" |> strtoupper(...);
      }
    }');

    Assert::equals('TEST', $r);
  }

  #[Test]
  public function pipe_to_callable_new() {
    $r= $this->run('class %T {
      public function run() {
        return "2024-03-27" |> new \util\Date(...);
      }
    }');

    Assert::equals('2024-03-27', $r->toString('Y-m-d'));
  }

  #[Test]
  public function pipe_to_callable_anonymous_new() {
    $r= $this->run('class %T {
      public function run() {
        return "2024-03-27" |> new class(...) {
          public function __construct(public string $value) { }
        };
      }
    }');

    Assert::equals('2024-03-27', $r->value);
  }

  #[Test]
  public function pipe_to_closure() {
    $r= $this->run('class %T {
      public function run() {
        return "test" |> fn($x) => $x.": OK";
      }
    }');

    Assert::equals('test: OK', $r);
  }

  #[Test]
  public function pipe_chain() {
    $r= $this->run('class %T {
      public function run() {
        return " test " |> trim(...) |> strtoupper(...);
      }
    }');

    Assert::equals('TEST', $r);
  }

  #[Test, Values([[null, null], ['test', 'TEST'], [' test ', 'TEST']])]
  public function nullsafe_pipe($input, $expected) {
    $r= $this->run('class %T {
      public function run($arg) {
        return $arg ?|> trim(...) ?|> strtoupper(...);
      }
    }', $input);

    Assert::equals($expected, $r);
  }
}