<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\{OmitConstModifiers, PHP};
use lang\ast\nodes\{Constant, ClassDeclaration, Literal};
use lang\ast\types\{IsLiteral, IsValue};
use test\{Assert, Before, Test};

class OmitConstModifiersTest extends EmitterTraitTest {
  private $type;

  /** @return lang.ast.Emitter */
  protected function fixture() {
    return new class() extends PHP {
      use OmitConstModifiers;
    };
  }

  #[Before]
  public function type() {
    $this->type= new ClassDeclaration([], new IsValue('\\T'), null, [], [], null, null, 1);
  }

  #[Test]
  public function omits_type() {
    $const= new Constant([], 'TEST', new IsLiteral('string'), new Literal('"test"'));
    Assert::equals('const TEST="test";', $this->emit($const, [$this->type]));
  }

  #[Test]
  public function omits_modifier() {
    $const= new Constant(['private'], 'TEST', new IsLiteral('string'), new Literal('"test"'));
    Assert::equals('const TEST="test";', $this->emit($const, [$this->type]));
  }
}