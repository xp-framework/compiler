<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\{ArrayLiteral, BinaryExpression, FunctionDeclaration, Literal, Parameter, ReturnStatement, Signature, YieldExpression, YieldFromExpression};
use lang\ast\{FunctionType, Type};
use unittest\Assert;

class FunctionsTest extends ParseTest {

  #[@test]
  public function empty_function_without_parameters() {
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature([], null), [], self::LINE)],
      'function a() { }'
    );
  }

  #[@test]
  public function two_functions() {
    $this->assertParsed(
      [
        new FunctionDeclaration('a', new Signature([], null), [], self::LINE),
        new FunctionDeclaration('b', new Signature([], null), [], self::LINE)
      ],
      'function a() { } function b() { }'
    );
  }

  #[@test, @values(['param', 'protected'])]
  public function with_parameter($name) {
    $params= [new Parameter($name, null, null, false, false, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a($'.$name.') { }'
    );
  }

  #[@test]
  public function with_reference_parameter() {
    $params= [new Parameter('param', null, null, true, false, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a(&$param) { }'
    );
  }

  #[@test]
  public function dangling_comma_in_parameter_lists() {
    $params= [new Parameter('param', null, null, false, false, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a($param, ) { }'
    );
  }

  #[@test]
  public function with_typed_parameter() {
    $params= [new Parameter('param', new Type('string'), null, false, false, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a(string $param) { }'
    );
  }

  #[@test]
  public function with_nullable_typed_parameter() {
    $params= [new Parameter('param', new Type('?string'), null, false, false, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a(?string $param) { }'
    );
  }

  #[@test]
  public function with_variadic_parameter() {
    $params= [new Parameter('param', null, null, false, true, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a(... $param) { }'
    );
  }

  #[@test]
  public function with_optional_parameter() {
    $params= [new Parameter('param', null, new Literal('null', self::LINE), false, false, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a($param= null) { }'
    );
  }

  #[@test]
  public function with_parameter_named_function() {
    $params= [new Parameter('function', null, null, false, false, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a($function, ) { }'
    );
  }

  #[@test]
  public function with_typed_parameter_named_function() {
    $params= [new Parameter('function', new FunctionType([], new Type('void')), null, false, false, null, [])];
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature($params, null), [], self::LINE)],
      'function a((function(): void) $function) { }'
    );
  }

  #[@test]
  public function with_return_type() {
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature([], new Type('void')), [], self::LINE)],
      'function a(): void { }'
    );
  }

  #[@test]
  public function with_nullable_return() {
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature([], new Type('?string')), [], self::LINE)],
      'function a(): ?string { }'
    );
  }

  #[@test]
  public function generator() {
    $yield= new YieldExpression(null, null, self::LINE);
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature([], null), [$yield], self::LINE)],
      'function a() { yield; }'
    );
  }

  #[@test]
  public function generator_with_value() {
    $yield= new YieldExpression(null, new Literal('1', self::LINE), self::LINE);
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature([], null), [$yield], self::LINE)],
      'function a() { yield 1; }'
    );
  }

  #[@test]
  public function generator_with_key_and_value() {
    $yield= new YieldExpression(new Literal('"number"', self::LINE), new Literal('1', self::LINE), self::LINE);
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature([], null), [$yield], self::LINE)],
      'function a() { yield "number" => 1; }'
    );
  }

  #[@test]
  public function generator_delegation() {
    $yield= new YieldFromExpression(new ArrayLiteral([], self::LINE), self::LINE);
    $this->assertParsed(
      [new FunctionDeclaration('a', new Signature([], null), [$yield], self::LINE)],
      'function a() { yield from []; }'
    );
  }
}