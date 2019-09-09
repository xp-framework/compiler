<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\BinaryExpression;
use lang\ast\nodes\InvokeExpression;
use lang\ast\nodes\LambdaExpression;
use lang\ast\nodes\Literal;
use lang\ast\nodes\Parameter;
use lang\ast\nodes\ReturnStatement;
use lang\ast\nodes\Signature;
use lang\ast\nodes\Variable;

class LambdasTest extends ParseTest {
  private $expression;

  /** @return void */
  public function setUp() {
    $this->expression= new BinaryExpression(new Variable('a', self::LINE), '+', new Literal('1', self::LINE), self::LINE);
  }

  #[@test]
  public function short_closure() {
    $this->assertParsed(
      [new LambdaExpression(new Signature([new Parameter('a', null)], null), $this->expression, self::LINE)],
      '($a) ==> $a + 1;'
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
      'execute(($a) ==> $a + 1);'
    );
    \xp::gc();
  }

  #[@test]
  public function short_closure_with_braces() {
    $this->assertParsed(
      [new LambdaExpression(new Signature([new Parameter('a', null)], null), [new ReturnStatement($this->expression, self::LINE)], self::LINE)],
      '($a) ==> { return $a + 1; };'
    );
    \xp::gc();
  }
}