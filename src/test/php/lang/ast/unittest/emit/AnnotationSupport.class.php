<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;
use unittest\{Assert, Expect, Test, Values};

/**
 * Annotations support. There are two tests extending this base class:
 *
 * - AnnotationsTest - emits XP Meta information
 * - AttributesTest - emits PHP 8 attributes
 */
abstract class AnnotationSupport extends EmittingTest {

  #[Test]
  public function without_value() {
    $t= $this->type('#[Test] class <T> { }');
    Assert::equals(['test' => null], $t->getAnnotations());
  }

  #[Test]
  public function within_namespace() {
    $t= $this->type('namespace tests; #[Test] class <T> { }');
    Assert::equals(['test' => null], $t->getAnnotations());
  }

  #[Test]
  public function resolved_against_import() {
    $t= $this->type('use unittest\Test; #[Test] class <T> { }');
    Assert::equals(['test' => null], $t->getAnnotations());
  }

  #[Test]
  public function primitive_value() {
    $t= $this->type('#[Author("Timm")] class <T> { }');
    Assert::equals(['author' => 'Timm'], $t->getAnnotations());
  }

  #[Test]
  public function array_value() {
    $t= $this->type('#[Authors(["Timm", "Alex"])] class <T> { }');
    Assert::equals(['authors' => ['Timm', 'Alex']], $t->getAnnotations());
  }

  #[Test]
  public function map_value() {
    $t= $this->type('#[Expect(["class" => \lang\IllegalArgumentException::class])] class <T> { }');
    Assert::equals(['expect' => ['class' => IllegalArgumentException::class]], $t->getAnnotations());
  }

  #[Test]
  public function named_argument() {
    $t= $this->type('#[Expect(class: \lang\IllegalArgumentException::class)] class <T> { }');
    Assert::equals(['expect' => ['class' => IllegalArgumentException::class]], $t->getAnnotations());
  }

  #[Test]
  public function closure_value() {
    $t= $this->type('#[Verify(function($arg) { return $arg; })] class <T> { }');
    $f= $t->getAnnotation('verify');
    Assert::equals('test', $f('test'));
  }

  #[Test]
  public function arrow_function_value() {
    $t= $this->type('#[Verify(fn($arg) => $arg)] class <T> { }');
    $f= $t->getAnnotation('verify');
    Assert::equals('test', $f('test'));
  }

  #[Test]
  public function array_of_arrow_function_value() {
    $t= $this->type('#[Verify([fn($arg) => $arg])] class <T> { }');
    $f= $t->getAnnotation('verify');
    Assert::equals('test', $f[0]('test'));
  }

  #[Test]
  public function single_quoted_string_inside_non_constant_expression() {
    $t= $this->type('#[Verify(fn($arg) => \'php\\\\\'.$arg)] class <T> { }');
    $f= $t->getAnnotation('verify');
    Assert::equals('php\\test', $f('test'));
  }

  #[Test]
  public function has_access_to_class() {
    $t= $this->type('#[Expect(self::SUCCESS)] class <T> { const SUCCESS = true; }');
    Assert::equals(['expect' => true], $t->getAnnotations());
  }

  #[Test]
  public function method() {
    $t= $this->type('class <T> { #[Test] public function fixture() { } }');
    Assert::equals(['test' => null], $t->getMethod('fixture')->getAnnotations());
  }

  #[Test]
  public function field() {
    $t= $this->type('class <T> { #[Test] public $fixture; }');
    Assert::equals(['test' => null], $t->getField('fixture')->getAnnotations());
  }

  #[Test]
  public function param() {
    $t= $this->type('class <T> { public function fixture(#[Test] $param) { } }');
    Assert::equals(['test' => null], $t->getMethod('fixture')->getParameter(0)->getAnnotations());
  }

  #[Test]
  public function params() {
    $t= $this->type('class <T> { public function fixture(#[Inject(["name" => "a"])] $a, #[Inject] $b) { } }');
    $m= $t->getMethod('fixture');
    Assert::equals(
      [['inject' => ['name' => 'a']], ['inject' => null]],
      [$m->getParameter(0)->getAnnotations(), $m->getParameter(1)->getAnnotations()]
    );
  }

  #[Test]
  public function multiple_class_annotations() {
    $t= $this->type('#[Resource("/"), Authenticated] class <T> { }');
    Assert::equals(['resource' => '/', 'authenticated' => null], $t->getAnnotations());
  }

  #[Test]
  public function multiple_member_annotations() {
    $t= $this->type('class <T> { #[Test, Values([1, 2, 3])] public function fixture() { } }');
    Assert::equals(['test' => null, 'values' => [1, 2, 3]], $t->getMethod('fixture')->getAnnotations());
  }
}