<?php namespace lang\ast\unittest\emit;

use lang\Runnable;
use util\AbstractDeferredInvokationHandler;

/**
 * Annotations support
 *
 * @see  https://github.com/xp-framework/rfc/issues/16
 * @see  https://docs.hhvm.com/hack/attributes/introduction
 * @see  https://wiki.php.net/rfc/simple-annotations (Draft)
 * @see  https://wiki.php.net/rfc/attributes (Declined)
 */
class AnnotationsTest extends EmittingTest {

  #[@test]
  public function without_value() {
    $t= $this->type('<<test>> class <T> { }');
    $this->assertEquals(['test' => null], $t->getAnnotations());
  }

  #[@test]
  public function primitive_value() {
    $t= $this->type('<<author("Timm")>> class <T> { }');
    $this->assertEquals(['author' => 'Timm'], $t->getAnnotations());
  }
}