<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\{PHP, RewriteMultiCatch};
use lang\ast\nodes\{CatchStatement, TryStatement};
use test\{Assert, Test};

class RewriteMultiCatchTest extends EmitterTraitTest {

  /** @return lang.ast.Emitter */
  protected function fixture() {
    return new class() extends PHP {
      use RewriteMultiCatch;
    };
  }

  #[Test]
  public function rewrites_catch_without_types() {
    Assert::equals(
      'try {}catch(\\Throwable $t) {}',
      $this->emit(new TryStatement([], [new CatchStatement([], 't', [])], null))
    );
  }

  #[Test]
  public function rewrites_catch_without_variable() {
    Assert::equals(
      'try {}catch(\\Throwable $_0) {}',
      $this->emit(new TryStatement([], [new CatchStatement([], null, [])], null))
    );
  }

  #[Test]
  public function rewrites_catch_with_multiple_types_using_goto() {
    Assert::equals(
      'try {}catch(\\Exception $t) { goto _0; }catch(\\Error $t) { _0:}',
      $this->emit(new TryStatement([], [new CatchStatement(['\\Exception', '\\Error'], 't', [])], null))
    );
  }
}