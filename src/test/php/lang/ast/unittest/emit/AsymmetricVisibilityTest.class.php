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

  #[Test, Expect(class: Error::class, message: '/Cannot modify private\(set\) property T.+::\$fixture/')]
  public function writing_private() {
    $t= $this->declare('class %T {
      public private(set) $fixture= "Test";
    }');
    $t->newInstance()->fixture= 'Changed';
  }

  #[Test, Expect(class: Error::class, message: '/Cannot modify protected\(set\) property T.+::\$fixture/')]
  public function writing_protected() {
    $t= $this->declare('class %T {
      public protected(set) $fixture= "Test";
    }');
    $t->newInstance()->fixture= 'Changed';
  }
}