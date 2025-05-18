<?php namespace lang\ast\unittest\emit;

use io\Path;
use test\{Assert, Test, Values};

class TernaryTest extends EmittingTest {

  #[Test, Values([[true, 'OK'], [false, 'Fail']])]
  public function ternary($value, $result) {
    Assert::equals($result, $this->run(
      'class %T {
        public function run($value) {
          return $value ? "OK" : "Fail";
        }
      }',
      $value
    ));
  }

  #[Test, Values([[true, MODIFIER_PUBLIC], [false, MODIFIER_PRIVATE]])]
  public function ternary_constants_goto_label_ambiguity($value, $result) {
    Assert::equals($result, $this->run(
      'class %T {
        public function run($value) {
          return $value ?  MODIFIER_PUBLIC : MODIFIER_PRIVATE;
        }
      }',
      $value
    ));
  }

  #[Test, Values([['OK', 'OK'], [null, 'Fail']])]
  public function short_ternary($value, $result) {
    Assert::equals($result, $this->run(
      'class %T {
        public function run($value) {
          return $value ?: "Fail";
        }
      }',
      $value
    ));
  }

  #[Test, Values([[['OK']], [[]]])]
  public function null_coalesce($value) {
    Assert::equals('OK', $this->run(
      'class %T {
        public function run($value) {
          return $value[0] ?? "OK";
        }
      }',
      $value
    ));
  }

  #[Test, Values(eval: '[["."], [new Path(".")]]')]
  public function with_instanceof($value) {
    Assert::equals(new Path('.'), $this->run(
      'use io\\Path; class %T {
        public function run($value) {
          return $value instanceof Path ? $value : new Path($value);
        }
      }',
      $value
    ));
  }

  #[Test, Values(['"te" . "st"', '1 + 2', '1 || 0', '2 ?? 1', '1 | 2', '4 === strlen("Test")'])]
  public function precedence($lhs) {
    Assert::equals('OK', $this->run(
      'class %T {
        public function run() {
          return '.$lhs.'? "OK" : "Error";
        }
      }'
    ));
  }

  #[Test]
  public function assignment_precedence() {
    Assert::equals(['OK', 'OK'], $this->run(
      'class %T {
        public function run() {
          return [$a= 1 ? "OK" : "Error", $a];
        }
      }'
    ));
  }

  #[Test]
  public function yield_precedence() {
    Assert::equals(['OK', null], iterator_to_array($this->run(
      'class %T {
        public function run() {
          yield (yield 1 ? "OK" : "Error");
        }
      }'
    )));
  }

  /** @see https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.ternary */
  #[Test]
  public function chaining_short_ternaries() {
    Assert::equals([1, 2, 3], $this->run(
      'class %T {
        public function run() {
          return [
            0 ?: 1 ?: 2 ?: 3,
            0 ?: 0 ?: 2 ?: 3,
            0 ?: 0 ?: 0 ?: 3,
          ];
        }
      }'
    ));
  }
}