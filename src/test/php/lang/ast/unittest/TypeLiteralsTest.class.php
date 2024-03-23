<?php namespace lang\ast\unittest;

use lang\ast\emit\{PHP70, PHP71, PHP72, PHP74, PHP80, PHP81, PHP82};
use lang\ast\types\{IsArray, IsFunction, IsGeneric, IsIntersection, IsLiteral, IsMap, IsNullable, IsUnion, IsValue};
use test\{Assert, Test, Values};

class TypeLiteralsTest {

  /** @return iterable */
  private function base() {
    yield [new IsGeneric('Set', [new IsLiteral('string')]), null];
    yield [new IsLiteral('int'), 'int'];
    yield [new IsValue('Test'), 'Test'];
    yield [new IsFunction([], new IsLiteral('int')), 'callable'];
    yield [new IsArray(new IsLiteral('int')), 'array'];
    yield [new IsMap(new IsLiteral('string'), new IsLiteral('int')), 'array'];
  }

  /**
   * PHP 7.4 is the same as PHP 7.2
   *
   * @return iterable
   */
  private function php74() {
    yield from $this->base();
    yield [new IsLiteral('object'), 'object'];
    yield [new IsLiteral('void'), 'void'];
    yield [new IsLiteral('never'), 'void'];
    yield [new IsLiteral('iterable'), 'iterable'];
    yield [new IsLiteral('mixed'), null];
    yield [new IsLiteral('null'), null];
    yield [new IsLiteral('false'), 'bool'];
    yield [new IsLiteral('true'), 'bool'];
    yield [new IsNullable(new IsLiteral('string')), '?string'];
    yield [new IsNullable(new IsLiteral('object')), '?object'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('int')]), null];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('false')]), null];
    yield [new IsIntersection([new IsValue('Test'), new IsValue('Iterator')]), null];
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
    yield [new IsLiteral('never'), 'void'];
    yield [new IsLiteral('iterable'), 'iterable'];
    yield [new IsLiteral('mixed'), 'mixed'];
    yield [new IsLiteral('null'), null];
    yield [new IsLiteral('false'), 'bool'];
    yield [new IsLiteral('true'), 'bool'];
    yield [new IsNullable(new IsLiteral('string')), '?string'];
    yield [new IsNullable(new IsLiteral('object')), '?object'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('int')]), 'string|int'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('false')]), 'string|bool'];
    yield [new IsIntersection([new IsValue('Test'), new IsValue('Iterator')]), null];
  }

  /**
   * PHP 8.1 added `never` and intersections
   *
   * @return iterable
   */
  private function php81() {
    yield from $this->base();
    yield [new IsLiteral('object'), 'object'];
    yield [new IsLiteral('void'), 'void'];
    yield [new IsLiteral('never'), 'never'];
    yield [new IsLiteral('iterable'), 'iterable'];
    yield [new IsLiteral('mixed'), 'mixed'];
    yield [new IsLiteral('null'), null];
    yield [new IsLiteral('false'), 'bool'];
    yield [new IsLiteral('true'), 'bool'];
    yield [new IsNullable(new IsLiteral('string')), '?string'];
    yield [new IsNullable(new IsLiteral('object')), '?object'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('int')]), 'string|int'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('false')]), 'string|bool'];
    yield [new IsIntersection([new IsValue('Test'), new IsValue('Iterator')]), 'Test&Iterator'];
  }

  /**
   * PHP 8.2 added `null`, `false` and `true`
   *
   * @return iterable
   */
  private function php82() {
    yield from $this->base();
    yield [new IsLiteral('object'), 'object'];
    yield [new IsLiteral('void'), 'void'];
    yield [new IsLiteral('never'), 'never'];
    yield [new IsLiteral('iterable'), 'iterable'];
    yield [new IsLiteral('mixed'), 'mixed'];
    yield [new IsLiteral('null'), 'null'];
    yield [new IsLiteral('false'), 'false'];
    yield [new IsLiteral('true'), 'true'];
    yield [new IsNullable(new IsLiteral('string')), '?string'];
    yield [new IsNullable(new IsLiteral('object')), '?object'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('int')]), 'string|int'];
    yield [new IsUnion([new IsLiteral('string'), new IsLiteral('false')]), 'string|false'];
    yield [new IsIntersection([new IsValue('Test'), new IsValue('Iterator')]), 'Test&Iterator'];
  }

  #[Test, Values(from: 'php74')]
  public function php74_literals($type, $literal) {
    Assert::equals($literal, (new PHP74())->literal($type));
  }

  #[Test, Values(from: 'php80')]
  public function php80_literals($type, $literal) {
    Assert::equals($literal, (new PHP80())->literal($type));
  }

  #[Test, Values(from: 'php81')]
  public function php81_literals($type, $literal) {
    Assert::equals($literal, (new PHP81())->literal($type));
  }

  #[Test, Values(from: 'php82')]
  public function php82_literals($type, $literal) {
    Assert::equals($literal, (new PHP82())->literal($type));
  }
}