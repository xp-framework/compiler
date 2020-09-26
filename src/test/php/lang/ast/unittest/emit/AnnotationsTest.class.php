<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;
use unittest\Assert;

/**
 * Annotations support
 *
 * @see  https://github.com/xp-framework/rfc/issues/16
 * @see  https://github.com/xp-framework/rfc/issues/218
 * @see  https://wiki.php.net/rfc/shorter_attribute_syntax_change
 */
class AnnotationsTest extends EmittingTest {

  #[@test]
  public function without_value() {
    $t= $this->type('#[Test] class <T> { }');
    Assert::equals(['test' => null], $t->getAnnotations());
  }

  #[@test]
  public function within_namespace() {
    $t= $this->type('namespace tests; #[Test] class <T> { }');
    Assert::equals(['test' => null], $t->getAnnotations());
  }

  #[@test]
  public function resolved_against_import() {
    $t= $this->type('use unittest\Test; #[Test] class <T> { }');
    Assert::equals(['test' => null], $t->getAnnotations());
  }

  #[@test]
  public function primitive_value() {
    $t= $this->type('#[Author("Timm")] class <T> { }');
    Assert::equals(['author' => 'Timm'], $t->getAnnotations());
  }

  #[@test]
  public function array_value() {
    $t= $this->type('#[Authors(["Timm", "Alex"])] class <T> { }');
    Assert::equals(['authors' => ['Timm', 'Alex']], $t->getAnnotations());
  }

  #[@test]
  public function map_value() {
    $t= $this->type('#[Expect(["class" => \lang\IllegalArgumentException::class])] class <T> { }');
    Assert::equals(['expect' => ['class' => IllegalArgumentException::class]], $t->getAnnotations());
  }

  #[@test]
  public function named_argument() {
    $t= $this->type('#[Expect(class: \lang\IllegalArgumentException::class)] class <T> { }');
    Assert::equals(['expect' => ['class' => IllegalArgumentException::class]], $t->getAnnotations());
  }

  #[@test]
  public function closure_value() {
    $t= $this->type('#[Verify(function($arg) { return $arg; })] class <T> { }');
    $f= $t->getAnnotation('verify');
    Assert::equals('test', $f('test'));
  }

  #[@test]
  public function arrow_function_value() {
    $t= $this->type('#[Verify(fn($arg) => $arg)] class <T> { }');
    $f= $t->getAnnotation('verify');
    Assert::equals('test', $f('test'));
  }

  #[@test]
  public function has_access_to_class() {
    $t= $this->type('#[Expect(self::SUCCESS)] class <T> { const SUCCESS = true; }');
    Assert::equals(['expect' => true], $t->getAnnotations());
  }

  #[@test]
  public function method() {
    $t= $this->type('class <T> { #[Test] public function fixture() { } }');
    Assert::equals(['test' => null], $t->getMethod('fixture')->getAnnotations());
  }

  #[@test]
  public function field() {
    $t= $this->type('class <T> { #[Test] public $fixture; }');
    Assert::equals(['test' => null], $t->getField('fixture')->getAnnotations());
  }

  #[@test]
  public function param() {
    $t= $this->type('class <T> { public function fixture(#[Test] $param) { } }');
    Assert::equals(['test' => null], $t->getMethod('fixture')->getParameter(0)->getAnnotations());
  }

  #[@test]
  public function params() {
    $t= $this->type('class <T> { public function fixture(#[inject(["name" => "a"])] $a, #[inject] $b) { } }');
    $m=$t->getMethod('fixture');
    Assert::equals(
      [['inject' => ['name' => 'a']], ['inject' => null]],
      [$m->getParameter(0)->getAnnotations(), $m->getParameter(1)->getAnnotations()]
    );
  }

  #[@test]
  public function multiple_class_annotations() {
    $t= $this->type('#[Resource("/"), authenticated] class <T> { }');
    Assert::equals(['resource' => '/', 'authenticated' => null], $t->getAnnotations());
  }

  #[@test]
  public function multiple_member_annotations() {
    $t= $this->type('class <T> { #[Test, Values([1, 2, 3])] public function fixture() { } }');
    Assert::equals(['test' => null, 'values' => [1, 2, 3]], $t->getMethod('fixture')->getAnnotations());
  }

  #[@test]
  public function xp_type_annotation() {
    $t= $this->type('
      #[@test]
      class <T> { }'
    );
    Assert::equals(['test' => null], $t->getAnnotations());
  }

  /** @deprecated */
  #[@test]
  public function xp_type_annotation_with_named_pairs() {
    $t= $this->type('
      #[@resource(path= "/", authenticated= true)]
      class <T> { }'
    );
    Assert::equals(['resource' => ['path' => '/', 'authenticated' => true]], $t->getAnnotations());
    \xp::gc();
  }

  #[@test]
  public function xp_type_annotations() {
    $t= $this->type('
      #[@resource("/"), @authenticated]
      class <T> { }'
    );
    Assert::equals(['resource' => '/', 'authenticated' => null], $t->getAnnotations());
  }

  #[@test]
  public function xp_type_multiline() {
    $t= $this->type('
      #[@verify(function($arg) {
      #  return $arg;
      #})]
      class <T> { }'
    );
    $f= $t->getAnnotation('verify');
    Assert::equals('test', $f('test'));
  }

  #[@test]
  public function xp_method_annotations() {
    $t= $this->type('
      class <T> {

        #[@test]
        public function succeeds() { }

        #[@test, @expect(\lang\IllegalArgumentException::class)]
        public function fails() { }

        #[@test, @values([
        #  [1, 2, 3],
        #])]
        public function cases() { }
      }'
    );
    Assert::equals(['test' => null], $t->getMethod('succeeds')->getAnnotations());
    Assert::equals(['test' => null, 'expect' => IllegalArgumentException::class], $t->getMethod('fails')->getAnnotations());
    Assert::equals(['test' => null, 'values' => [[1, 2, 3]]], $t->getMethod('cases')->getAnnotations());
  }

  #[@test]
  public function xp_param_annotation() {
    $t= $this->type('
      class <T> {

        #[@test, @$arg: inject("conn")]
        public function fixture($arg) { }
      }'
    );
    Assert::equals(['test' => null], $t->getMethod('fixture')->getAnnotations());
    Assert::equals(['inject' => 'conn'], $t->getMethod('fixture')->getParameter(0)->getAnnotations());
  }
}