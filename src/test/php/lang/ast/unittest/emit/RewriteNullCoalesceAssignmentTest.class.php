<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\{PHP, RewriteNullCoalesceAssignment};
use lang\ast\nodes\{Assignment, Variable, Literal};
use unittest\{Assert, Test};

class RewriteNullCoalesceAssignmentTest extends EmitterTraitTest {

  /** @return lang.ast.Emitter */
  protected function fixture() {
    return new class() extends PHP {
      use RewriteNullCoalesceAssignment;
    };
  }

  #[Test]
  public function rewrites_null_coalesce_equal_operator() {
    Assert::equals(
      '$v??$v=1',
      $this->emit(new Assignment(new Variable('v'), '??=', new Literal('1')))
    );
  }

  #[Test]
  public function does_not_rewrite_plus() {
    Assert::equals(
      '$v+=1',
      $this->emit(new Assignment(new Variable('v'), '+=', new Literal('1')))
    );
  }
}