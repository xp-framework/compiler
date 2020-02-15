<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\{
  ArrayLiteral,
  Assignment,
  BinaryExpression,
  Braced,
  ClassDeclaration,
  InstanceExpression,
  InstanceOfExpression,
  InvokeExpression,
  Literal,
  NewClassExpression,
  NewExpression,
  OffsetExpression,
  ScopeExpression,
  TernaryExpression,
  UnaryExpression,
  Variable
};
use unittest\Assert;

class OperatorTest extends ParseTest {

  #[@test, @values([
  #  '+', '-', '*', '/', '.', '%', '|', '&', '**',
  #  '??', '?:',
  #  '&&', '||',
  #  '>>', '<<'
  #])]
  public function binary($operator) {
    $this->assertParsed(
      [new BinaryExpression(new Variable('a', self::LINE), $operator, new Variable('b', self::LINE), self::LINE)],
      '$a '.$operator.' $b;'
    );
  }

  #[@test]
  public function ternary() {
    $this->assertParsed(
      [new TernaryExpression(new Variable('a', self::LINE), new Literal('1', self::LINE), new Literal('2', self::LINE), self::LINE)],
      '$a ? 1 : 2;'
    );
  }

  #[@test, @values([
  #  '==', '!=',
  #  '===', '!==',
  #  '>', '>=', '<=', '<', '<=>'
  #])]
  public function comparison($operator) {
    $this->assertParsed(
      [new BinaryExpression(new Variable('a', self::LINE), $operator, new Variable('b', self::LINE), self::LINE)],
      '$a '.$operator.' $b;'
    );
  }

  #[@test, @values(['++', '--'])]
  public function suffix($operator) {
    $this->assertParsed(
      [new UnaryExpression('suffix', new Variable('a', self::LINE), $operator, self::LINE)],
      '$a'.$operator.';'
    );
  }

  #[@test, @values(['!', '~', '-', '+', '++', '--', '@', '&'])]
  public function prefix($operator) {
    $this->assertParsed(
      [new UnaryExpression('prefix', new Variable('a', self::LINE), $operator, self::LINE)],
      $operator.'$a;'
    );
  }

  #[@test, @values([
  #  '=',
  #  '+=', '-=', '*=', '/=', '.=', '**=',
  #  '&=', '|=', '^=',
  #  '>>=', '<<='
  #])]
  public function assignment($operator) {
    $this->assertParsed(
      [new Assignment(new Variable('a', self::LINE), $operator, new Variable('b', self::LINE), self::LINE)],
      '$a '.$operator.' $b;'
    );
  }

  #[@test]
  public function assignment_to_offset() {
    $target= new OffsetExpression(new Variable('a', self::LINE), new Literal('0', self::LINE), self::LINE);
    $this->assertParsed(
      [new Assignment($target, '=', new Variable('b', self::LINE), self::LINE)],
      '$a[0]= $b;'
    );
  }

  #[@test]
  public function destructuring_assignment() {
    $target= new ArrayLiteral([[null, new Variable('a', self::LINE)], [null, new Variable('b', self::LINE)]], self::LINE);
    $this->assertParsed(
      [new Assignment($target, '=', new Variable('c', self::LINE), self::LINE)],
      '[$a, $b]= $c;'
    );
  }

  #[@test]
  public function comparison_to_assignment() {
    $this->assertParsed(
      [new BinaryExpression(
        new Literal('1', self::LINE), '===', new Braced(
          new Assignment(new Variable('a', self::LINE), '=', new Literal('1', self::LINE), self::LINE),
          self::LINE
        ),
        self::LINE
      )],
      '1 === ($a= 1);'
    );
  }

  #[@test]
  public function append_array() {
    $target= new OffsetExpression(new Variable('a', self::LINE), null, self::LINE);
    $this->assertParsed(
      [new Assignment($target, '=', new Variable('b', self::LINE), self::LINE)],
      '$a[]= $b;'
    );
  }

  #[@test]
  public function clone_expression() {
    $this->assertParsed(
      [new UnaryExpression('prefix', new Variable('a', self::LINE), 'clone', self::LINE)],
      'clone $a;'
    );
  }

  #[@test]
  public function error_suppression() {
    $this->assertParsed(
      [new UnaryExpression('prefix', new Variable('a', self::LINE), '@', self::LINE)],
      '@$a;'
    );
  }

  #[@test]
  public function reference() {
    $this->assertParsed(
      [new UnaryExpression('prefix', new Variable('a', self::LINE), '&', self::LINE)],
      '&$a;'
    );
  }

  #[@test]
  public function new_type() {
    $this->assertParsed(
      [new NewExpression('\\T', [], self::LINE)],
      'new T();'
    );
  }

  #[@test]
  public function new_var() {
    $this->assertParsed(
      [new NewExpression('$class', [], self::LINE)],
      'new $class();'
    );
  }

  #[@test]
  public function new_expr() {
    $this->assertParsed(
      [new NewExpression(new InvokeExpression(new Literal('factory', self::LINE), [], self::LINE), [], self::LINE)],
      'new (factory())();'
    );
  }

  #[@test]
  public function new_type_with_args() {
    $this->assertParsed(
      [new NewExpression('\\T', [new Variable('a', self::LINE), new Variable('b', self::LINE)], self::LINE)],
      'new T($a, $b);'
    );
  }

  #[@test]
  public function new_anonymous_extends() {
    $declaration= new ClassDeclaration([], null, '\\T', [], [], [], null, self::LINE);
    $this->assertParsed(
      [new NewClassExpression($declaration, [], self::LINE)],
      'new class() extends T { };'
    );
  }

  #[@test]
  public function new_anonymous_implements() {
    $declaration= new ClassDeclaration([], null, null, ['\\A', '\\B'], [], [], null, self::LINE);
    $this->assertParsed(
      [new NewClassExpression($declaration, [], self::LINE)],
      'new class() implements A, B { };'
    );
  }

  #[@test]
  public function scope_resolution_operator() {
    $this->assertParsed(
      [new ScopeExpression('\\Objects', new Literal('ID', self::LINE), self::LINE)],
      'Objects::ID;'
    );
  }

  #[@test]
  public function precedence_of_object_operator_binary() {
    $this->assertParsed(
      [new BinaryExpression(
        new InstanceExpression(new Variable('this', self::LINE), new Literal('a', self::LINE), self::LINE),
        '.',
        new Literal('"test"', self::LINE),
        self::LINE
      )],
      '$this->a."test";'
    );
  }

  #[@test]
  public function precedence_of_object_operator_unary() {
    $this->assertParsed(
      [new UnaryExpression(
        'prefix',
        new InstanceExpression(new Variable('this', self::LINE), new Literal('a', self::LINE), self::LINE),
        '!',
        self::LINE
      )],
      '!$this->a;'
    );
  }

  #[@test]
  public function precedence_of_scope_resolution_operator_binary() {
    $this->assertParsed(
      [new BinaryExpression(
        new ScopeExpression('self', new Literal('class', self::LINE), self::LINE),
        '.',
        new Literal('"test"', self::LINE),
        self::LINE
      )],
      'self::class."test";'
    );
  }

  #[@test]
  public function precedence_of_scope_resolution_operator_unary() {
    $this->assertParsed(
      [new UnaryExpression(
        'prefix',
        new ScopeExpression('\\Objects', new Literal('ID', self::LINE), self::LINE),
        '!',
        self::LINE
      )],
      '!Objects::ID;'
    );
  }

  #[@test]
  public function precedence_of_not_and_instance_of() {
    $this->assertParsed(
      [new UnaryExpression(
        'prefix',
        new InstanceOfExpression(new Variable('this', self::LINE), 'self', self::LINE),
        '!',
        self::LINE
      )],
      '!$this instanceof self;'
    );
  }

  #[@test, @values(['+', '-', '~'])]
  public function precedence_of_prefix($operator) {
    $this->assertParsed(
      [new BinaryExpression(
        new UnaryExpression('prefix', new Literal('2', self::LINE), $operator, self::LINE),
        '===',
        new Variable('value', self::LINE),
        self::LINE
      )],
      $operator.'2 === $value;'
    );
  }

  #[@test]
  public function precedence_of_braces_unary() {
    $this->assertParsed(
      [new UnaryExpression(
        'prefix',
        new InvokeExpression(new Variable('a', self::LINE), [], self::LINE),
        '!',
        self::LINE
      )],
      '!$a();'
    );
  }

  #[@test]
  public function precedence_of_offset_unary() {
    $this->assertParsed(
      [new UnaryExpression(
        'prefix',
        new OffsetExpression(new Variable('a', self::LINE), new Literal('0', self::LINE), self::LINE),
        '!',
        self::LINE
      )],
      '!$a[0];'
    );
  }
}