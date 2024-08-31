<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\Type;
use test\{Assert, Test};

class PHP84Test extends EmittingTest {

  /** @return string */
  protected function runtime() { return 'php:8.4.0'; }

  #[Test]
  public function property_hooks() {
    Assert::matches(
      '/\$test{get=>"Test";}/',
      $this->emit('class %T { public $test { get => "Test"; } }')
    );
  }

  #[Test]
  public function asymmetric_visibility() {
    Assert::matches(
      '/public private\(set\) string \$test/',
      $this->emit('class %T { public private(set) string $test; }')
    );
  }
}