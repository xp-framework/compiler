<?php namespace lang\ast\unittest\emit;

use lang\Runtime;
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

    // PHP >= 8.3: PHP will raise errors for us
    if (PHP_VERSION_ID >= 80300) {
      $rt= Runtime::getInstance()->newInstance(null, 'class', 'xp.runtime.Evaluate', [str_replace('%T', 'T', $code)]);
      $rt->in->close();
      $err= $rt->err->readLine();
      $rt->close();

      return false === $err ? null : preg_replace('/Uncaught error: Compile error \((.+)\)/', '$1', $err);
    }

    // PHP < 8.3: XP compiler will raise errors for us
    try {
      $t= $this->declare($code);
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
  public function overriding_non_existant_method() {
    Assert::equals(
      'T::nonExistant() has #[\Override] attribute, but no matching parent method exists',
      $this->verify('class %T extends \lang\Throwable {
        #[Override]
        public function nonExistant() { }
      }'
    ));
  }
}