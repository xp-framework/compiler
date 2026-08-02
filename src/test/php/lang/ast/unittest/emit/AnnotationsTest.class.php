<?php namespace lang\ast\unittest\emit;

use ReflectionClass;
use lang\ast\emit\php\XpMeta;
use test\{Assert, Test, Values};

/**
 * Annotations via XP Meta information
 *
 * @see  https://github.com/xp-framework/rfc/issues/16
 * @see  https://github.com/xp-framework/rfc/issues/218
 */
class AnnotationsTest extends AnnotationSupport {

  /** @return string[] */
  protected function emitters() { return [XpMeta::class]; }

  /** @return iterable */
  private function declarations() {
    yield ['#[Test]', ['Test' => []]];
    yield ['#[Test("a")]', ['Test' => ['a']]];
    yield ['#[Test(1, 2, 3)]', ['Test' => [1, 2, 3]]];
    yield ['#[Test(value: "a")]', ['Test' => ['value' => 'a']]];
  }

  #[Test, Values(from: 'declarations')]
  public function also_emits_php_attributes($declaration, $expected) {
    $type= $this->declare($declaration);
    $declared= [];
    foreach ((new ReflectionClass($type->literal()))->getAttributes() as $attribute) {
      $declared[$attribute->getName()]= $attribute->getArguments();
    }

    Assert::equals($expected, $declared);
  }
}