<?php namespace lang\ast\unittest\emit;

use lang\Runnable;
use unittest\Assert;
use util\AbstractDeferredInvokationHandler;

/**
 * Anonymous class support
 *
 * @see  https://github.com/xp-framework/rfc/issues/80
 * @see  https://wiki.php.net/rfc/anonymous_classes
 */
class AnonymousClassTest extends EmittingTest {

  #[@test]
  public function parentless() {
    $r= $this->run('class <T> {
      public function run() {
        return new class() {
          public function id() { return "test"; }
        };
      }
    }');
    Assert::equals('test', $r->id());
  }

  #[@test]
  public function extending_base_class() {
    $r= $this->run('class <T> {
      public function run() {
        return new class() extends \\util\\AbstractDeferredInvokationHandler {
          public function initialize() {
            // TBI
          }
        };
      }
    }');
    Assert::instance(AbstractDeferredInvokationHandler::class, $r);
  }

  #[@test]
  public function implementing_interface() {
    $r= $this->run('class <T> {
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

  #[@test]
  public function method_annotations() {
    $r= $this->run('class <T> {
      public function run() {
        return new class() {

          #[Inside]
          public function fixture() { }
        };
      }
    }');

    Assert::equals(['inside' => null], typeof($r)->getMethod('fixture')->getAnnotations());
  }
}