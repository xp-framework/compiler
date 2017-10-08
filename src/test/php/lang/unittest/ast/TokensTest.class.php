<?php namespace lang\unittest\ast;

use text\StringTokenizer;
use lang\ast\Tokens;
use lang\FormatException;

class TokensTest extends \unittest\TestCase {

  /**
   * Assertion helper
   *
   * @param  [:var][] $expected
   * @param  lang.ast.Tokens $tokens
   * @throws unittest.AssertionFailedError
   * @return void
   */
  private function assertTokens($expected, $tokens) {
    $actual= [];
    foreach ($tokens as $type => $value) {
      $actual[]= [$type => $value];
    }
    $this->assertEquals($expected, $actual);
  }

  #[@test]
  public function can_create() {
    new Tokens(new StringTokenizer('test'));
  }

  #[@test, @values([
  #  ['""', ''],
  #  ["''", ''],
  #  ['"Test"', 'Test'],
  #  ["'Test'", 'Test'],
  #])]
  public function string_literals($input, $expected) {
    $this->assertTokens([['string' => $expected]], new Tokens(new StringTokenizer($input)));
  }

  #[@test, @expect(class= FormatException::class, withMessage= '/Unclosed string literal/'), @values([
  #  '"',
  #  "'",
  #  '"Test',
  #  "'Test"
  #])]
  public function unclosed_string_literals($input) {
    $t= (new Tokens(new StringTokenizer($input)))->getIterator(); 
    $t->current();
  }

  #[@test, @values([
  #  ['0', 0],
  #  ['1', 1]
  #])]
  public function integer_literal($input, $expected) {
    $this->assertTokens([['integer' => $expected]], new Tokens(new StringTokenizer($input)));
  }

  #[@test, @values([
  #  ['0.0', 0.0],
  #  ['6.1', 6.1],
  #  ['.5', 0.5]
  #])]
  public function float_literal($input, $expected) {
    $this->assertTokens([['decimal' => $expected]], new Tokens(new StringTokenizer($input)));
  }

  #[@test, @values([
  #  '$a',
  #  '$_',
  #  '$input'
  #])]
  public function variables($input) {
    $this->assertTokens([['variable' => substr($input, 1)]], new Tokens(new StringTokenizer($input)));
  }

  #[@test, @values([
  #  '+', '-', '*', '/', '**',
  #  '==', '!=',
  #  '<=', '>=', '<=>',
  #  '===', '!==',
  #  '=>',
  #  '==>',
  #  '->',
  #])]
  public function operators($input) {
    $this->assertTokens([['operator' => $input]], new Tokens(new StringTokenizer($input)));
  }

  #[@test]
  public function annotation() {
    $this->assertTokens(
      [['operator' => '<<'], ['name' => 'test'], ['operator' => '>>']],
      new Tokens(new StringTokenizer('<<test>>'))
    );
  }
}