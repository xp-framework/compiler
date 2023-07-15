<?php namespace lang\ast\unittest\emit;

use lang\ast\Error;
use test\{Assert, Test};

class MethodOverridingTest extends EmittingTest {

  /**
   * Returns emitters to use. Defaults to XpMeta
   *
   * @return string[]
   */
  protected function emitters() { return []; }

  /**
   * Verifies code, yielding any errors
   *
   * @param  string $code
   * @return ?string
   */
  private function verify($code) {
    try {
      $this->declare($code);
      return null;
    } catch (Error $e) {
      return preg_replace('/T[0-9]+/', 'T', $e->getMessage());
    }
  }

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
  public function correctly_overwriting_interface_method() {
    Assert::null($this->verify('interface %T extends \lang\Runnable {
      #[Override]
      public function run();
    }'));
  }

  #[Test]
  public function without_parent() {
    Assert::equals(
      'T::fixture() has #[\Override] attribute, but no matching parent method exists',
      $this->verify('class %T {
        #[Override]
        public function fixture() { }
      }'
    ));
  }

  #[Test]
  public function without_parent_in_anonymous_class() {
    Assert::equals(
      'class@anonymous::fixture() has #[\Override] attribute, but no matching parent method exists',
      $this->verify('new class() {
        #[Override]
        public function fixture() { }
      };'
    ));
  }

  #[Test]
  public function overriding_non_existant_method() {
    Assert::equals(
      'T::nonExistant() has #[\Override] attribute, but no matching parent method exists',
      $this->verify('class %T extends \lang\Throwable {
        #[Override]
        public function nonExistant() { }
      }'
    ));
  }

  #[Test]
  public function override_inside_traits() {
    Assert::null($this->verify('trait %T {
      #[Override]
      public function run() { }
    }'));
  }

  #[Test]
  public function without_parent_with_method_from_trait() {
    $trait= $this->declare('trait %T {
      #[Override]
      public function run() { }
    }');

    Assert::equals(
      'T::run() has #[\Override] attribute, but no matching parent method exists',
      $this->verify('class %T { use '.$trait->literal().'; }')
    );
  }
}