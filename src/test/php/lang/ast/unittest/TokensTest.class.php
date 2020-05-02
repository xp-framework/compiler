<?php namespace lang\ast\unittest;

use lang\FormatException;
use lang\ast\Tokens;
use unittest\Assert;

class TokensTest {

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
      $actual[]= [$type => $value[0]];
    }
    Assert::equals($expected, $actual);
  }

  #[@test]
  public function can_create() {
    new Tokens('test');
  }

  #[@test, @values([
  #  '""',
  #  "''",
  #  "'\\\\'",
  #  '"Test"',
  #  "'Test'",
  #  "'Test\''",
  #  "'\\\\\\''",
  #])]
  public function string_literals($input) {
    $this->assertTokens([['string' => $input]], new Tokens($input));
  }

  #[@test, @expect(['class' => FormatException::class, 'withMessage' => '/Unclosed string literal/']), @values([
  #  '"',
  #  "'",
  #  '"Test',
  #  "'Test"
  #])]
  public function unclosed_string_literals($input) {
    $t= (new Tokens($input))->getIterator(); 
    $t->current();
  }

  #[@test, @values(['0', '1', '1_000_000_000'])]
  public function integer_literal($input) {
    $this->assertTokens([['integer' => str_replace('_', '', $input)]], new Tokens($input));
  }

  #[@test, @values(['0.0', '6.1', '.5', '107_925_284.88'])]
  public function float_literal($input) {
    $this->assertTokens([['decimal' => str_replace('_', '', $input)]], new Tokens($input));
  }

  #[@test, @values([
  #  '$a',
  #  '$_',
  #  '$input'
  #])]
  public function variables($input) {
    $this->assertTokens([['variable' => substr($input, 1)]], new Tokens($input));
  }

  #[@test, @values([
  #  '+', '-', '*', '/', '**',
  #  '==', '!=',
  #  '<=', '>=', '<=>',
  #  '===', '!==',
  #  '=>',
  #  '->',
  #])]
  public function operators($input) {
    $this->assertTokens([['operator' => $input]], new Tokens($input));
  }

  #[@test]
  public function annotation() {
    $this->assertTokens(
      [['operator' => '<<'], ['name' => 'test'], ['operator' => '>>']],
      new Tokens('<<test>>')
    );
  }

  #[@test]
  public function regular_comment() {
    $this->assertTokens([], new Tokens('// Comment'));
  }

  #[@test]
  public function apidoc_comment() {
    $this->assertTokens([['comment' => 'Test']], new Tokens('/** Test */'));
  }
}