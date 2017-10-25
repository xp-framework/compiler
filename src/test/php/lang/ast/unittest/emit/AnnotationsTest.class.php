<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;

/**
 * Annotations support
 *
 * @see  https://github.com/xp-framework/rfc/issues/16
 * @see  https://github.com/xp-framework/rfc/issues/218
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
  public function closure_value() {
    $t= $this->type('<<verify(function($arg) { return $arg; })>> class <T> { }');
    $f= $t->getAnnotation('verify');
    $this->assertEquals('test', $f('test'));
  }

  #[@test]
  public function has_access_to_class() {
    $t= $this->type('<<expect(self::SUCCESS)>> class <T> { const SUCCESS = true; }');
    $this->assertEquals(['expect' => true], $t->getAnnotations());
  }

  #[@test]
  public function method() {
    $t= $this->type('class <T> { <<test>> public function fixture() { } }');
    $this->assertEquals(['test' => null], $t->getMethod('fixture')->getAnnotations());
  }

  #[@test]
  public function field() {
    $t= $this->type('class <T> { <<test>> public $fixture; }');
    $this->assertEquals(['test' => null], $t->getField('fixture')->getAnnotations());
  }

  #[@test]
  public function param() {
    $t= $this->type('class <T> { public function fixture(<<test>> $param) { } }');
    $this->assertEquals(['test' => null], $t->getMethod('fixture')->getParameter(0)->getAnnotations());
  }

  #[@test]
  public function params() {
    $t= $this->type('class <T> { public function fixture(<<inject(["name" => "a"])>> $a, <<inject>> $b) { } }');
    $m=$t->getMethod('fixture');
    $this->assertEquals(
      [['inject' => ['name' => 'a']], ['inject' => null]],
      [$m->getParameter(0)->getAnnotations(), $m->getParameter(1)->getAnnotations()]
    );
  }
}