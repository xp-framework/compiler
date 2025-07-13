<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test, Values};

class EmulatePipelinesTest extends EmittingTest {

  /** @return string */
  protected function runtime() { return 'php:8.4.0'; }

  #[Test]
  public function lhs_evaluation_order() {
    Assert::equals(
      '[$_0=result(),($expr)($_0)][1];',
      $this->emit('result() |> $expr;')
    );
  }

  #[Test]
  public function to_expression() {
    Assert::equals(
      '($expr)("hi");',
      $this->emit('"hi" |> $expr;')
    );
  }

  #[Test, Values(['"strlen"', "'strlen'"])]
  public function to_string_literal($notation) {
    Assert::equals(
      'strlen("hi");',
      $this->emit('"hi" |> '.$notation.';')
    );
  }

  #[Test]
  public function to_array_literal() {
    Assert::equals(
      '([$this,"func",])("hi");',
      $this->emit('"hi" |> [$this, "func"];')
    );
  }

  #[Test]
  public function to_first_class_callable() {
    Assert::equals(
      'strlen("hi");',
      $this->emit('"hi" |> strlen(...);')
    );
  }

  #[Test]
  public function to_callable_new() {
    Assert::equals(
      'new \\util\\Date("2025-07-12");',
      $this->emit('"2025-07-12" |> new \\util\\Date(...);')
    );
  }
}