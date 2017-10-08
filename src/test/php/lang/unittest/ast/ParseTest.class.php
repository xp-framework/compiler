<?php namespace lang\unittest\ast;

use text\StringTokenizer;
use lang\ast\Parse;
use lang\ast\Node;
use lang\ast\Tokens;

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
    return (new Parse(new Tokens(new StringTokenizer($code))))->execute();
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