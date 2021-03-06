<?php namespace lang\ast\unittest\emit;

use lang\ast\emit\Declaration;
use lang\ast\nodes\{ClassDeclaration, Property};
use unittest\{Assert, Test, Expect};

class DeclarationTest {
  private $type;

  #[Before]
  public function type() {
    $this->type= new ClassDeclaration([], '\\T', '\\lang\\Enum', [], [
      '$ONE' => new Property(['public', 'static'], 'ONE', null, null, [], null, 1)
    ]);
  }

  #[Test]
  public function can_create() {
    new Declaration($this->type, null);
  }

  #[Test]
  public function name() {
    Assert::equals('T', (new Declaration($this->type, null))->name());
  }

  #[Test]
  public function rewrites_unit_enums() {
    $declaration= new Declaration($this->type, null);
    Assert::true($declaration->rewriteEnumCase('ONE'));
    Assert::false($declaration->rewriteEnumCase('EMPTY'));
  }
}