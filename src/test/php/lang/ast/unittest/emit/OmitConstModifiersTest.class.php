<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\{OmitConstModifiers, PHP};
use lang\ast\nodes\{Constant, Literal};
use lang\ast\types\IsLiteral;
use test\{Assert, Test};

class OmitConstModifiersTest extends EmitterTraitTest {

  /** @return lang.ast.Emitter */
  protected function fixture() {
    return new class() extends PHP {
      use OmitConstModifiers;
    };
  }

  #[Test]
  public function omits_type() {
    Assert::equals(
      'const TEST="test";',
      $this->emit(new Constant([], 'TEST', new IsLiteral('string'), new Literal('"test"')))
    );
  }

  #[Test]
  public function omits_modifier() {
    Assert::equals(
      'const TEST="test";',
      $this->emit(new Constant(['private'], 'TEST', new IsLiteral('string'), new Literal('"test"')))
    );
  }
}