<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\{ClassDeclaration, Literal, Method, ReturnStatement, Signature};
use unittest\Assert;

class CompactFunctionsTest extends ParseTest {
  private $return;

  /** @return void */
  #[@before]
  public function setUp() {
    $this->return= new ReturnStatement(new Literal('null', self::LINE), self::LINE);
  }

  #[@test]
  public function compact_method() {
    $method= new Method(['public'], 'a', new Signature([], null), [$this->return], [], null, self::LINE);

    $this->assertParsed(
      [new ClassDeclaration([], '\\A', null, [], [$method->lookup() => $method], [], null, self::LINE)],
      'class A { public fn a() => null; }'
    );
  }
}