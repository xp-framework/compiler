<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;
use test\{Assert, Expect, Test, Values};

/**
 * Annotations support. There are two tests extending this base class:
 *
 * - AnnotationsTest - emits XP Meta information
 * - AttributesTest - emits PHP 8 attributes
 */
abstract class AnnotationSupport extends EmittingTest {
  use AnnotationsOf;

  #[Test]
  public function without_value() {
    Assert::equals(
      ['Test' => []],
      $this->annotations($this->declare('#[Test]'))
    );
  }

  #[Test]
  public function within_namespace() {
    Assert::equals(
      ['tests\\Test' => []],
      $this->annotations($this->declare('namespace tests; #[Test]'))
    );
  }

  #[Test]
  public function resolved_against_import() {
    Assert::equals(
      ['unittest\\Test' => []],
      $this->annotations($this->declare('use unittest\Test; #[Test]'))
    );
  }

  #[Test]
  public function primitive_value() {
    Assert::equals(
      ['Author' => ['Timm']],
      $this->annotations($this->declare('#[Author("Timm")]'))
    );
  }

  #[Test]
  public function array_value() {
    Assert::equals(
      ['Authors' => [['Timm', 'Alex']]],
      $this->annotations($this->declare('#[Authors(["Timm", "Alex"])]'))
    );
  }

  #[Test]
  public function map_value() {
    Assert::equals(
      ['Expect' => [['class' => IllegalArgumentException::class]]],
      $this->annotations($this->declare('#[Expect(["class" => \lang\IllegalArgumentException::class])]'))
    );
  }

  #[Test]
  public function named_argument() {
    Assert::equals(
      ['Expect' => ['class' => IllegalArgumentException::class]],
      $this->annotations($this->declare('#[Expect(class: \lang\IllegalArgumentException::class)]'))
    );
  }

  #[Test]
  public function closure_value() {
    $verify= $this->annotations($this->declare('#[Verify(function($arg) { return $arg; })]'))['Verify'];
    Assert::equals('test', $verify[0]('test'));
  }

  #[Test]
  public function arrow_function_value() {
    $verify= $this->annotations($this->declare('#[Verify(fn($arg) => $arg)]'))['Verify'];
    Assert::equals('test', $verify[0]('test'));
  }

  #[Test]
  public function array_of_arrow_function_value() {
    $verify= $this->annotations($this->declare('#[Verify([fn($arg) => $arg])]'))['Verify'];
    Assert::equals('test', $verify[0][0]('test'));
  }

  #[Test]
  public function named_arrow_function_value() {
    $verify= $this->annotations($this->declare('#[Verify(func: fn($arg) => $arg)]'))['Verify'];
    Assert::equals('test', $verify['func']('test'));
  }

  #[Test]
  public function single_quoted_string_inside_non_constant_expression() {
    $verify= $this->annotations($this->declare('#[Verify(fn($arg) => \'php\\\\\'.$arg)]'))['Verify'];
    Assert::equals('php\\test', $verify[0]('test'));
  }

  #[Test]
  public function has_access_to_class() {
    Assert::equals(
      ['Expect' => [true]],
      $this->annotations($this->declare('#[Expect(self::SUCCESS)] class %T { const SUCCESS = true; }'))
    );
  }

  #[Test]
  public function method() {
    $t= $this->declare('class %T { #[Test] public function fixture() { } }');
    Assert::equals(
      ['Test' => []],
      $this->annotations($t->method('fixture'))
    );
  }

  #[Test]
  public function field() {
    $t= $this->declare('class %T { #[Test] public $fixture; }');
    Assert::equals(
      ['Test' => []],
      $this->annotations($t->property('fixture'))
    );
  }

  #[Test]
  public function param() {
    $t= $this->declare('class %T { public function fixture(#[Test] $param) { } }');
    Assert::equals(
      ['Test' => []],
      $this->annotations($t->method('fixture')->parameter(0))
    );
  }

  #[Test]
  public function params() {
    $t= $this->declare('class %T { public function fixture(#[Inject(["name" => "a"])] $a, #[Inject] $b) { } }');
    Assert::equals(
      ['Inject' => [['name' => 'a']]],
      $this->annotations($t->method('fixture')->parameter(0))
    );
    Assert::equals(
      ['Inject' => []],
      $this->annotations($t->method('fixture')->parameter(1))
    );
  }

  #[Test]
  public function multiple_class_annotations() {
    Assert::equals(
      ['Resource' => ['/'], 'Authenticated' => []],
      $this->annotations($this->declare('#[Resource("/"), Authenticated]'))
    );
  }

  #[Test]
  public function multiple_member_annotations() {
    $t= $this->declare('class %T { #[Test, Values([1, 2, 3])] public function fixture() { } }');
    Assert::equals(
      ['Test' => [], 'Values' => [[1, 2, 3]]],
      $this->annotations($t->method('fixture'))
    );
  }

  #[Test]
  public function multiline_annotations() {
    $annotations= $this->annotations($this->declare('
      #[Authors([
        "Timm",
        "Mr. Midori",
      ])]
      class %T { }'
    ));
    Assert::equals(['Authors' => [['Timm', 'Mr. Midori']]], $annotations);
  }
}