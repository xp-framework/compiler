<?php namespace lang\ast\unittest\emit;

use lang\Error;
use test\verify\Runtime;
use test\{Assert, Expect, Test, Values};

/** @see https://wiki.php.net/rfc/pipe-operator-v3 */
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
  public function pipe_to_variable() {
    $r= $this->run('class %T {
      public function run() {
        $f= strtoupper(...);
        return "test" |> $f;
      }
    }');

    Assert::equals('TEST', $r);
  }

  #[Test]
  public function pipe_to_callable_string() {
    $r= $this->run('class %T {
      public function run() {
        return "test" |> "strtoupper";
      }
    }');

    Assert::equals('TEST', $r);
  }

  #[Test]
  public function pipe_to_callable_array() {
    $r= $this->run('class %T {
      public function toUpper($x) { return strtoupper($x); }

      public function run() {
        return "test" |> [$this, "toUpper"];
      }
    }');

    Assert::equals('TEST', $r);
  }

  #[Test]
  public function pipe_to_callable_without_all_args() {
    $r= $this->run('class %T {
      public function run() {
        return "A&B" |> htmlspecialchars(...);
      }
    }');

    Assert::equals('A&amp;B', $r);
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

  #[Test, Expect(Error::class)]
  public function pipe_to_throw() {
    $this->run('use lang\Error; class %T {
      public function run() {
        return "test" |> throw new Error("Test");
      }
    }');
  }

  #[Test, Expect(Error::class)]
  public function pipe_to_missing() {
    $this->run('class %T {
      public function run() {
        return "test" |> "__missing";
      }
    }');
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

  #[Test, Values([[['test'], 'TEST'], [[''], ''], [[], null]])]
  public function nullsafe_pipe($input, $expected) {
    $r= $this->run('class %T {
      public function run($arg) {
        return array_shift($arg) ?|> strtoupper(...);
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[null, null], ['', ''], ['test', 'TEST'], [' test ', 'TEST']])]
  public function nullsafe_chain($input, $expected) {
    $r= $this->run('class %T {
      public function run($arg) {
        return $arg ?|> trim(...) ?|> strtoupper(...);
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test]
  public function concat_precedence() {
    $r= $this->run('class %T {
      public function run() {
        return "te" . "st" |> strtoupper(...);
      }
    }');

    Assert::equals('TEST', $r);
  }

  #[Test]
  public function addition_precedence() {
    $r= $this->run('class %T {
      public function run() {
        return 5 + 2 |> fn($i) => $i * 2;
      }
    }');

    Assert::equals(14, $r);
  }

  #[Test]
  public function comparison_precedence() {
    $r= $this->run('class %T {
      public function run() {
        return 5 |> fn($i) => $i * 2 === 10;
      }
    }');

    Assert::true($r);
  }

  #[Test, Values([[0, 'even'], [1, 'odd'], [2, 'even']])]
  public function ternary_precedence($arg, $expected) {
    $r= $this->run('class %T {

      private function odd($n) { return $n % 2; }

      public function run($arg) {
        return $arg |> $this->odd(...) ? "odd" : "even";
      }
    }', $arg);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[0, '(empty)'], [1, 'one element'], [2, '2 elements']])]
  public function short_ternary_precedence($arg, $expected) {
    $r= $this->run('class %T {

      private function number($n) {
        return match ($n) {
          0 => null,
          1 => "one element",
          default => "{$n} elements"
        };
      }

      public function run($arg) {
        return $arg |> $this->number(...) ?: "(empty)";
      }
    }', $arg);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[0, 'root'], [1001, 'test'], [1002, '#unknown']])]
  public function coalesce_precedence($arg, $expected) {
    $r= $this->run('class %T {
      private $users= [0 => "root", 1001 => "test"];

      private function user($id) { return $this->users[$id] ?? null; }

      public function run($arg) {
        return $arg |> $this->user(...) ?? "#unknown";
      }
    }', $arg);

    Assert::equals($expected, $r);
  }

  #[Test]
  public function rfc_example() {
    $r= $this->run('class %T {
      public function run() {
        return "Hello World"
          |> "htmlentities"
          |> str_split(...)
          |> fn($x) => array_map(strtoupper(...), $x)
          |> fn($x) => array_filter($x, fn($v) => $v != "O")
        ;
      }
    }');
    Assert::equals(['H', 'E', 'L', 'L', ' ', 'W', 'R', 'L', 'D'], array_values($r));
  }

  #[Test, Expect(Error::class), Runtime(php: '>=8.5.0')]
  public function rejects_by_reference_functions() {
    $this->run('class %T {
      private function modify(&$arg) { $arg++; }

      public function run() {
        $val= 1;
        return $val |> $this->modify(...);
      }
    }');
  }

  #[Test]
  public function accepts_prefer_by_reference_functions() {
    $r= $this->run('class %T {
      public function run() {
        return ["hello", "world"] |> array_multisort(...);
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function execution_order() {
    $r= $this->run('class %T {
      public function run() {
        $invoked= [];

        $first= function() use(&$invoked) { $invoked[]= "first"; return 1; };
        $second= function() use(&$invoked) { $invoked[]= "second"; return false; };
        $skipped= function() use(&$invoked) { $invoked[]= "skipped"; return $in; };
        $third= function($in) use(&$invoked) { $invoked[]= "third"; return $in; };
        $capture= function($result) use(&$invoked) { $invoked[]= $result; };

        $first() |> ($second() ? $skipped : $third) |> $capture;
        return $invoked;
      }
    }');

    Assert::equals(['first', 'second', 'third', 1], $r);
  }

  #[Test]
  public function interrupted_by_exception() {
    $r= $this->run('use lang\Error; class %T {
      public function run() {
        $invoked= [];

        $provide= function() use(&$invoked) { $invoked[]= "provide"; return 1; };
        $transform= function($in) use(&$invoked) { $invoked[]= "transform"; return $in * 2; };
        $throw= function() use(&$invoked) { $invoked[]= "throw"; throw new Error("Break"); };

        try {
          $provide() |> $transform |> $throw |> throw new Error("Unreachable");
        } catch (Error $e) {
          $invoked[]= $e->compoundMessage();
        }
        return $invoked;
      }
    }');

    Assert::equals(['provide', 'transform', 'throw', 'Exception lang.Error (Break)'], $r);
  }

  #[Test]
  public function generators() {
    $r= $this->run('class %T {
      private function range($lo, $hi) {
        for ($i= $lo; $i <= $hi; $i++) {
          yield $i;
        }
      }

      private function map($fn) {
        return function($it) use($fn) {
          foreach ($it as $element) {
            yield $fn($element);
          }
        };
      }

      public function run() {
        return $this->range(1, 3) |> $this->map(fn($e) => $e + 1) |> iterator_to_array(...);
      }
    }');

    Assert::equals([2, 3, 4], $r);
  }
}