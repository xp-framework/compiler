<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\Value;
use lang\ast\{Language, Node, Parse, Tokens};
use text\StringTokenizer;
use unittest\Assert;
use unittest\TestCase;

abstract class ParseTest {
  const LINE = 1;

  /**
   * Parse code, returning nodes on at a time
   *
   * @param  string $code
   * @return iterable
   */
  protected function parse($code) {
    return (new Parse(Language::named('PHP'), new Tokens(new StringTokenizer($code)), static::class))->execute();
  }

  /**
   * Assertion helper
   *
   * @param  [:var][] $expected
   * @param  iterable $nodes
   * @throws unittest.AssertionFailedError
   * @return void
   */
  protected function assertParsed($expected, $code) {
    $actual= [];
    foreach ($this->parse($code) as $node) {
      $actual[]= $node;
    }
    Assert::equals($expected, $actual);
  }
}