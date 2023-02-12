<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\Type;
use test\{Assert, Test};

class PHP82Test extends EmittingTest {

  /** @return string */
  protected function runtime() { return 'php:8.2.0'; }

  #[Test]
  public function callable_function_syntax() {
    Assert::equals('strlen(...);', $this->emit('strlen(...);'));
  }

  #[Test]
  public function callable_static_method_syntax() {
    Assert::equals('\lang\Type::forName(...);', $this->emit('\lang\Type::forName(...);'));
  }

  #[Test]
  public function callable_instance_method_syntax() {
    Assert::equals('$this->method(...);', $this->emit('$this->method(...);'));
  }
}