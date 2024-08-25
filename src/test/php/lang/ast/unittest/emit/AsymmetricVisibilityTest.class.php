<?php namespace lang\ast\unittest\emit;

use lang\Error;
use test\{Assert, Expect, Test, Values};

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

  #[Test]
  public function writing_from_self_scope() {
    $t= $this->declare('class %T {
      public private(set) $fixture= "Test";

      public function rename($name) {
        $this->fixture= $name;
        return $this;
      }
    }');

    Assert::throws(Error::class, fn() => $t->newInstance()->fixture= 'Changed');
    Assert::equals('Changed', $t->newInstance()->rename('Changed')->fixture);
  }

  #[Test]
  public function writing_from_inherited_scope() {
    $parent= $this->declare('class %T { public protected(set) $fixture= "Test"; }');
    $t= $this->declare('class %T extends '.$parent->literal().' {
      public function rename($name) {
        $this->fixture= $name;
        return $this;
      }
    }');

    Assert::throws(Error::class, fn() => $t->newInstance()->fixture= 'Changed');
    Assert::equals('Changed', $t->newInstance()->rename('Changed')->fixture);
  }

  #[Test]
  public function writing_explicitely_public_set() {
    $t= $this->declare('class %T {
      public public(set) $fixture= "Test";
    }');

    $instance= $t->newInstance();
    $instance->fixture= 'Changed';
    Assert::equals('Changed', $instance->fixture);
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

  #[Test]
  public function promoted_constructor_parameter() {
    $t= $this->declare('class %T {
      public function __construct(public private(set) $fixture) { }
    }');
    Assert::equals('Test', $t->newInstance('Test')->fixture);
  }

  #[Test, Expect(class: Error::class, message: '/Cannot modify readonly property .+fixture/')]
  public function readonly() {
    $t= $this->declare('class %T {

      // public-read, protected-write, write-once property
      public protected(set) readonly string $fixture= "Test";

      public function rename() {
        $this->fixture= "Changed"; // Will always error
      }
    }');
    $t->newInstance()->rename();
  }

  #[Test, Values(['private', 'protected', 'public'])]
  public function reflection($modifier) {
    $t= $this->declare('class %T {
      public '.$modifier.'(set) string $fixture= "Test";
    }');

    Assert::equals(
      'public '.$modifier.'(set) string $fixture',
      $t->property('fixture')->toString()
    );
  }
}