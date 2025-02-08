<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\Type;
use test\{Assert, Test};

class PHP83Test extends EmittingTest {

  /** @return string */
  protected function runtime() { return 'php:8.3.0'; }

  #[Test]
  public function readonly_classes() {
    Assert::matches(
      '/readonly class [A-Z0-9]+{/',
      $this->emit('readonly class %T { }')
    );
  }
}