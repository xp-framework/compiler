<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\{Literal, OffsetExpression, StaticLocals, Variable};
use unittest\Assert;

class VariablesTest extends ParseTest {

  #[@test, @values(['v', 'key', 'this', 'class', 'protected'])]
  public function variable($name) {
    $this->assertParsed(
      [new Variable($name, self::LINE)],
      '$'.$name.';'
    );
  }

  #[@test]
  public function static_variable() {
    $this->assertParsed(
      [new StaticLocals(['v' => null], self::LINE)],
      'static $v;'
    );
  }

  #[@test]
  public function static_variable_with_initialization() {
    $this->assertParsed(
      [new StaticLocals(['id' => new Literal('0', self::LINE)], self::LINE)],
      'static $id= 0;'
    );
  }

  #[@test]
  public function array_offset() {
    $this->assertParsed(
      [new OffsetExpression(new Variable('a', self::LINE), new Literal('0', self::LINE), self::LINE)],
      '$a[0];'
    );
  }

  /** @deprecated */
  #[@test]
  public function string_offset() {
    $this->assertParsed(
      [new OffsetExpression(new Variable('a', self::LINE), new Literal('0', self::LINE), self::LINE)],
      '$a{0};'
    );
    \xp::gc();
  }
}