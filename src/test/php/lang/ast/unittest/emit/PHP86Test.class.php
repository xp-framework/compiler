<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\Type;
use test\{Assert, Test};

class PHP86Test extends EmittingTest {

  /** @return string */
  protected function runtime() { return 'php:8.6.0'; }

  #[Test]
  public function partial_function_application_argument() {
    Assert::equals(
      'str_replace("test","ok",?);',
      $this->emit('str_replace("test", "ok", ?)')
    );
  }

  #[Test]
  public function partial_function_application_variadic() {
    Assert::equals(
      'str_replace("test","ok",...);',
      $this->emit('str_replace("test", "ok", ...)')
    );
  }
}