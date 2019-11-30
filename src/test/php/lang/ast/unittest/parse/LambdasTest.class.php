<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\{BinaryExpression, InvokeExpression, LambdaExpression, Literal, Parameter, ReturnStatement, Signature, Variable};
use unittest\Assert;

class LambdasTest extends ParseTest {
  private $expression;

  /** @return void */
  #[@before]
  public function setUp() {
    $this->expression= new BinaryExpression(new Variable('a', self::LINE), '+', new Literal('1', self::LINE), self::LINE);
  }

  #[@test]
  public function short_closure() {
    $this->assertParsed(
      [new LambdaExpression(new Signature([new Parameter('a', null)], null), $this->expression, self::LINE)],
      'fn($a) => $a + 1;'
    );
    \xp::gc();
  }

  #[@test]
  public function short_closure_as_arg() {
    $this->assertParsed(
      [new InvokeExpression(
        new Literal('execute', self::LINE),
        [new LambdaExpression(new Signature([new Parameter('a', null)], null), $this->expression, self::LINE)],
        self::LINE
      )],
      'execute(fn($a) => $a + 1);'
    );
    \xp::gc();
  }

  #[@test]
  public function short_closure_with_braces() {
    $this->assertParsed(
      [new LambdaExpression(new Signature([new Parameter('a', null)], null), [new ReturnStatement($this->expression, self::LINE)], self::LINE)],
      'fn($a) => { return $a + 1; };'
    );
    \xp::gc();
  }
}