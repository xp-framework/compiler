<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\{PHP, ChainScopeOperators};
use lang\ast\nodes\{ClassDeclaration, Literal, ScopeExpression, Variable};
use lang\ast\types\IsValue;
use test\{Assert, Test};

class ChainScopeOperatorsTest extends EmitterTraitTest {

  /** @return lang.ast.Emitter */
  protected function fixture() {
    return new class() extends PHP {
      use ChainScopeOperators;
    };
  }

  #[Test]
  public function rewrites_type_variable() {
    Assert::equals('\\get_class($instance)', $this->emit(
      new ScopeExpression(new Variable('instance'), new Literal('class')))
    );
  }

  #[Test]
  public function does_not_rewrite_type_literal() {
    Assert::equals('self::class', $this->emit(
      new ScopeExpression('self', new Literal('class')),
      [new ClassDeclaration([], new IsValue('\\T'), null, [], [], null, null, 1)]
    ));
  }
}