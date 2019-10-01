<?php namespace lang\ast\unittest\emit;

use lang\Runnable;
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
    $this->assertEquals('test', $r->id());
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
    $this->assertInstanceOf(AbstractDeferredInvokationHandler::class, $r);
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
    $this->assertInstanceOf(Runnable::class, $r);
  }

  #[@test]
  public function method_annotations() {
    $r= $this->run('class <T> {
      public function run() {
        return new class() {

          <<inside>>
          public function fixture() { }
        };
      }

      public function fixture() { }
    }');

    $this->assertEquals(['inside' => null], typeof($r)->getMethod('fixture')->getAnnotations());
  }

  #[@test]
  public function method_annotations_with_constructor() {
    $r= $this->run('class <T> {
      public function run() {
        return new class("test") {
          public $name;

          public function __construct($name) {
            $this->name= $name;
          }

          <<test>>
          public function fixture() { }
        };
      }
    }');
    $this->assertEquals(['test' => null], typeof($r)->getMethod('fixture')->getAnnotations());
    $this->assertEquals('test', $r->name);
  }

  #[@test]
  public function method_annotations_with_inherited_constructor() {
    $r= $this->run('class <T> {
      public $name;

      public function __construct($name= null) {
        $this->name= $name;
      }

      public function run() {
        return new class("test") extends <T> {

          <<test>>
          public function fixture() { }
        };
      }
    }');
    $this->assertEquals(['test' => null], typeof($r)->getMethod('fixture')->getAnnotations());
    $this->assertEquals('test', $r->name);
  }
}