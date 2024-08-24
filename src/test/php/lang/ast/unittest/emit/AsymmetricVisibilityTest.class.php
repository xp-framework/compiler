<?php namespace lang\ast\unittest\emit;

use lang\Error;
use test\{Assert, Expect, Test};

/**
 * Asymmetric visibility tests
 *
 * @see  https://wiki.php.net/rfc/asymmetric-visibility-v2
 */
class AsymmetricVisibilityTest extends EmittingTest {

  #[Test]
  public function reading() {
    $t= $this->declare('class %T {
      public private(set) $fixture= "Test";
    }');
    Assert::equals('Test', $t->newInstance()->fixture);
  }

  #[Test, Expect(class: Error::class, message: '/Cannot access private property T.+::fixture/')]
  public function writing() {
    $t= $this->declare('class %T {
      public private(set) $fixture= "Test";
    }');
    $t->newInstance()->fixture= 'Changed';
  }
}