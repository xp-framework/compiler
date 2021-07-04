<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\{PHP, RewriteLambdaExpressions};
use lang\ast\nodes\{LambdaExpression, ReturnStatement, Signature, Literal, Block};
use unittest\{Assert, Test};

class RewriteLambdaExpressionsTest extends EmitterTraitTest {

  /** @return lang.ast.Emitter */
  protected function fixture() {
    return new class() extends PHP {
      use RewriteLambdaExpressions;
    };
  }

  #[Test]
  public function rewrites_fn_with_single_expression_to_function() {
    Assert::equals('function(){return true;}', $this->emit(
      new LambdaExpression(new Signature([], null), new Literal('true'))
    ));
  }

  #[Test]
  public function rewrites_fn_with_block_to_function() {
    Assert::equals('function(){return false;}', $this->emit(
      new LambdaExpression(new Signature([], null), new Block([
        new ReturnStatement(new Literal('false'))
      ]))
    ));
  }
}