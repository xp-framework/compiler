<?php namespace lang\ast\unittest\checks;

use lang\ast\checks\MethodOverriding;
use test\{Assert, Test};

class MethodOverridingTest extends CheckTest {

  /** @return lang.ast.checks.Check */
  protected function check() { return new MethodOverriding(); }

  #[Test]
  public function without_annotations() {
    Assert::null($this->verify('class %T { public function fixture() { } }'));
  }

  #[Test]
  public function correctly_overwriting_parent_method() {
    Assert::null($this->verify('class %T extends \lang\Throwable {
      #[Override]
      public function compoundMessage() { }
    }'));
  }

  #[Test]
  public function correctly_implementing_interface_method() {
    Assert::null($this->verify('class %T implements \lang\Runnable {
      #[Override]
      public function run() { }
    }'));
  }

  #[Test]
  public function without_parent() {
    Assert::equals(
      '%T::fixture() has #[\Override] attribute, but no matching parent method exists [line 2 of %T]',
      $this->verify('class %T {
        #[Override]
        public function fixture() { }
      }'
    ));
  }
}