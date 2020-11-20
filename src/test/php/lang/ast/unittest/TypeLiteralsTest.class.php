<?php namespace lang\ast\unittest;

use lang\ast\emit\{PHP70, PHP71, PHP72, PHP74, PHP80};
use lang\ast\types\{IsLiteral, IsArray, IsFunction, IsMap, IsValue, IsNullable, IsUnion};
use unittest\{Assert, Test};

class TypeLiteralsTest {

  /** @return iterable */
  private function base() {
    yield [new IsLiteral('int'), 'int'];
    yield [new IsValue('Test'), 'Test'];
    yield [new IsFunction([], new IsLiteral('int')), 'callable'];
    yield [new IsArray(new IsLiteral('int')), 'array'];
    yield [new IsMap(new IsLiteral('string'), new IsLiteral('int')), 'array'];
  }

  /**
   * PHP 7.0 - the base case
   *
   * @return iterable
   */
  private function php70() {
    yield from $this->base();
    yield [new IsLiteral('object'), null];
    yield [new IsLiteral('void'), null];
    yield [new IsLiteral('iterable'), null];
    yield [new IsLiteral('mixed'), null];
    yield [new IsNullable(new IsLiteral('string')), null];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('int')]), null];
  }

  /**
   * PHP 7.1 added `void` and `iterable` as well as support for nullable types
   *
   * @return iterable
   */
  private function php71() {
    yield from $this->base();
    yield [new IsLiteral('object'), null];
    yield [new IsLiteral('void'), 'void'];
    yield [new IsLiteral('iterable'), 'iterable'];
    yield [new IsLiteral('mixed'), null];
    yield [new IsNullable(new IsLiteral('string')), '?string'];
    yield [new IsNullable(new IsLiteral('object')), null];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('int')]), null];
  }

  /**
   * PHP 7.2 added `object`
   *
   * @return iterable
   */
  private function php72() {
    yield from $this->base();
    yield [new IsLiteral('object'), 'object'];
    yield [new IsLiteral('void'), 'void'];
    yield [new IsLiteral('iterable'), 'iterable'];
    yield [new IsLiteral('mixed'), null];
    yield [new IsNullable(new IsLiteral('string')), '?string'];
    yield [new IsNullable(new IsLiteral('object')), '?object'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('int')]), null];
  }

  /**
   * PHP 7.4 is the same as PHP 7.2
   *
   * @return iterable
   */
  private function php74() {
    yield from $this->php72();
  }

  /**
   * PHP 8.0 added `mixed` and union types
   *
   * @return iterable
   */
  private function php80() {
    yield from $this->base();
    yield [new IsLiteral('object'), 'object'];
    yield [new IsLiteral('void'), 'void'];
    yield [new IsLiteral('iterable'), 'iterable'];
    yield [new IsLiteral('mixed'), 'mixed'];
    yield [new IsNullable(new IsLiteral('string')), '?string'];
    yield [new IsNullable(new IsLiteral('object')), '?object'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('int')]), 'string|int'];
  }

  #[Test, Values('php70')]
  public function php70_literals($type, $literal) {
    Assert::equals($literal, (new PHP70())->literal($type));
  }

  #[Test, Values('php71')]
  public function php71_literals($type, $literal) {
    Assert::equals($literal, (new PHP71())->literal($type));
  }

  #[Test, Values('php72')]
  public function php72_literals($type, $literal) {
    Assert::equals($literal, (new PHP72())->literal($type));
  }

  #[Test, Values('php74')]
  public function php74_literals($type, $literal) {
    Assert::equals($literal, (new PHP74())->literal($type));
  }

  #[Test, Values('php80')]
  public function php80_literals($type, $literal) {
    Assert::equals($literal, (new PHP80())->literal($type));
  }
}