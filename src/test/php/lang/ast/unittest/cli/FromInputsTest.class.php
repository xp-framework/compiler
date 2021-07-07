<?php namespace lang\ast\unittest\cli;

use io\streams\{FileInputStream, ConsoleInputStream};
use unittest\{Assert, Test, Values};
use xp\compiler\FromInputs;

class FromInputsTest {

  /** @return iterable */
  private function inputs() {
    yield [[], []];
    yield [['-'], ['-' => ConsoleInputStream::class]];
    yield [[__FILE__, '-'], [basename(__FILE__) => FileInputStream::class, '-' => ConsoleInputStream::class]];
  }

  #[Test]
  public function can_create() {
    new FromInputs([]);
  }

  #[Test, Values('inputs')]
  public function iteration($inputs, $expected) {
    $results= [];
    foreach (new FromInputs($inputs) as $path => $stream) {
      $results[$path->toString('/')]= get_class($stream);
    }

    Assert::equals($expected, $results);
  }
}