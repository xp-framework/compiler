<?php namespace lang\ast\unittest\emit;

use lang\{Runnable, Reflection};
use test\{Assert, Test};
use util\Binford;

/**
 * Anonymous class support
 *
 * @see  https://github.com/xp-framework/rfc/issues/80
 * @see  https://wiki.php.net/rfc/anonymous_classes
 */
class AnonymousClassTest extends EmittingTest {

  #[Test]
  public function parentless() {
    $r= $this->run('class %T {
      public function run() {
        return new class() {
          public function id() { return "test"; }
        };
      }
    }');
    Assert::equals('test', $r->id());
  }

  #[Test]
  public function extending_base_class() {
    $r= $this->run('class %T {
      public function run() {
        return new class() extends \\util\\Binford {
          public function more(int $factor): self {
            $this->poweredBy*= $factor;
            return $this;
          }
        };
      }
    }');
    Assert::instance(Binford::class, $r);
  }

  #[Test]
  public function implementing_interface() {
    $r= $this->run('class %T {
      public function run() {
        return new class() implements \\lang\\Runnable {
          public function run() {
            // NOOP
          }
        };
      }
    }');
    Assert::instance(Runnable::class, $r);
  }

  #[Test]
  public function method_annotations() {
    $r= $this->run('class %T {
      public function run() {
        return new class() {

          #[Inside]
          public function fixture() { }
        };
      }
    }');

    Assert::equals([], Reflection::type($r)->method('fixture')->annotation('Inside')->arguments());
  }

  #[Test]
  public function extending_enclosing_class() {
    $t= $this->declare('class %T {
      public static function run() {
        return new class() extends self { };
      }
    }');
    Assert::instance($t->class(), $t->method('run')->invoke(null));
  }

  #[Test]
  public function referencing_enclosing_class() {
    $r= $this->run('class %T {
      const ID= 6100;

      public static function run() {
        return new class() extends self {
          public static $id= %T::ID;
        };
      }
    }');
    Assert::equals(6100, Reflection::type($r)->property('id')->get(null));
  }
}