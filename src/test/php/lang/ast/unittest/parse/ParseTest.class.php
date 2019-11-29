<?php namespace lang\ast\unittest\parse;

use lang\ast\{Language, Node, Parse, Tokens};
use lang\ast\nodes\Value;
use text\StringTokenizer;
use unittest\TestCase;

abstract class ParseTest extends TestCase {
  const LINE = 1;

  /**
   * Parse code, returning nodes on at a time
   *
   * @param  string $code
   * @return iterable
   */
  protected function parse($code) {
    return (new Parse(Language::named('PHP'), new Tokens(new StringTokenizer($code)), $this->getName()))->execute();
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
    $this->assertEquals($expected, $actual);
  }
}