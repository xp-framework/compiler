<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\ClassDeclaration;
use lang\ast\nodes\FunctionDeclaration;
use lang\ast\nodes\Literal;
use lang\ast\nodes\Method;
use lang\ast\nodes\ReturnStatement;
use lang\ast\nodes\Signature;

class CompactFunctionsTest extends ParseTest {
  private $return;

  /** @return void */
  public function setUp() {
    $this->return= new ReturnStatement(new Literal('null', self::LINE), self::LINE);
  }

  #[@test]
  public function compact_function() {
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature([], null), [$this->return], self::LINE)],
      'function a() ==> null;'
    );
    \xp::gc();
  }

  #[@test]
  public function compact_method() {
    $method= new Method(['public'], 'a', new Signature([], null), [$this->return], [], null, self::LINE);

    $this->assertParsed(
      [new ClassDeclaration([], '\\A', null, [], [$method->lookup() => $method], [], null, self::LINE)],
      'class A { public function a() ==> null; }'
    );
    \xp::gc();
  }
}