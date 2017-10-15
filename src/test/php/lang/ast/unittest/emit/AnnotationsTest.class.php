<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;

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

  #[@test]
  public function array_value() {
    $t= $this->type('<<authors(["Timm", "Alex"])>> class <T> { }');
    $this->assertEquals(['authors' => ['Timm', 'Alex']], $t->getAnnotations());
  }

  #[@test]
  public function map_value() {
    $t= $this->type('<<expect(["class" => \lang\IllegalArgumentException::class])>> class <T> { }');
    $this->assertEquals(['expect' => ['class' => IllegalArgumentException::class]], $t->getAnnotations());
  }

  #[@test]
  public function has_access_to_class() {
    $t= $this->type('<<expect(self::SUCCESS)>> class <T> { const SUCCESS = true; }');
    $this->assertEquals(['expect' => true], $t->getAnnotations());
  }
}