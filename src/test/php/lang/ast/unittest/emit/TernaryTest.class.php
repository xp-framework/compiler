<?php namespace lang\ast\unittest\emit;

use io\Path;
use unittest\{Assert, Test, Values};

class TernaryTest extends EmittingTest {

  #[Test, Values([[true, 'OK'], [false, 'Fail']])]
  public function ternary($value, $result) {
    Assert::equals($result, $this->run(
      'class <T> {
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
      'class <T> {
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
      'class <T> {
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
      'class <T> {
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
      'class <T> {
        public function run($value) {
          return $value instanceof \\io\\Path ? $value : new \\io\\Path($value);
        }
      }',
      $value
    ));
  }
}