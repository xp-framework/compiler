<?php namespace lang\ast\unittest\parse;

use lang\ast\Language;
use lang\ast\Node;
use lang\ast\Parse;
use lang\ast\Tokens;
use lang\ast\nodes\Value;
use text\StringTokenizer;

abstract class ParseTest extends \unittest\TestCase {

  /**
   * Transforms nodes for easy comparison
   *
   * @param  var $arg
   * @return var
   */
  private function value($arg) {
    if ($arg instanceof Node) {
      return [$arg->symbol->id => $this->value($arg->value)];
    } else if ($arg instanceof Value) {
      $r= [];
      foreach ((array)$arg as $key => $value) {
        $r[]= $this->value($value);
      }
      return $r;
    } else if (is_array($arg)) {
      $r= [];
      foreach ($arg as $key => $value) {
        $r[$key]= $this->value($value);
      }
      return $r;
    } else {
      return $arg;
    }
  }

  /**
   * Parse code, returning nodes on at a time
   *
   * @param  string $code
   * @return iterable
   */
  protected function parse($code) {
    return (new Parse(Language::named('php'), new Tokens(new StringTokenizer($code)), $this->getName()))->execute();
  }

  /**
   * Assertion helper
   *
   * @param  [:var][] $expected
   * @param  iterable $nodes
   * @throws unittest.AssertionFailedError
   * @return void
   */
  protected function assertNodes($expected, $nodes) {
    $actual= [];
    foreach ($nodes as $node) {
      $actual[]= $this->value($node);
    }
    $this->assertEquals($expected, $actual);
  }
}