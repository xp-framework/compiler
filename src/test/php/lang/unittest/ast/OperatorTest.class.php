<?php namespace lang\unittest\ast;

class OperatorTest extends ParseTest {

  #[@test, @values([
  #  '+', '-', '*', '/', '.', '|', '&', '**',
  #  '??', '?:',
  #  '&&', '||',
  #  '>>', '<<'
  #])]
  public function binary($operator) {
    $this->assertNodes(
      [[$operator => [['(variable)' => 'a'], ['(variable)' => 'b']]]],
      $this->parse('$a '.$operator.' $b;')
    );
  }

  #[@test]
  public function ternary() {
    $this->assertNodes(
      [['?' => [['(variable)' => 'a'], ['(literal)' => 1], ['(literal)' => 2]]]],
      $this->parse('$a ? 1 : 2;')
    );
  }

  #[@test, @values([
  #  '==', '!=',
  #  '===', '!==',
  #  '>', '>=', '<=', '<', '<=>'
  #])]
  public function comparison($operator) {
    $this->assertNodes(
      [[$operator => [['(variable)' => 'a'], ['(variable)' => 'b']]]],
      $this->parse('$a '.$operator.' $b;')
    );
  }

  #[@test, @values(['++', '--'])]
  public function suffix($operator) {
    $this->assertNodes(
      [[$operator => ['(variable)' => 'a']]],
      $this->parse('$a'.$operator.';')
    );
  }

  #[@test, @values(['!', '~', '++', '--'])]
  public function prefix($operator) {
    $this->assertNodes(
      [[$operator => ['(variable)' => 'a']]],
      $this->parse(''.$operator.'$a;')
    );
  }

  #[@test, @values([
  #  '=',
  #  '+=', '-=', '*=', '/=', '.=', '&=', '|=', '**=',
  #  '>>=', '<<='
  #])]
  public function assignment($operator) {
    $this->assertNodes(
      [[$operator => [['(variable)' => 'a'], ['(variable)' => 'b']]]],
      $this->parse('$a '.$operator.' $b;')
    );
  }

  #[@test]
  public function new_type() {
    $this->assertNodes(
      [['new' => ['T', []]]],
      $this->parse('new T();')
    );
  }

  #[@test]
  public function new_type_with_args() {
    $this->assertNodes(
      [['new' => ['T', [['(variable)' => 'a'], ['(variable)' => 'b']]]]],
      $this->parse('new T($a, $b);')
    );
  }

  #[@test]
  public function new_anonymous_extends() {
    $this->assertNodes(
      [['new' => [null, [], [null, 'T', [], []]]]],
      $this->parse('new class() extends T { };')
    );
  }

  #[@test]
  public function new_anonymous_implements() {
    $this->assertNodes(
      [['new' => [null, [], [null, null, ['A', 'B'], []]]]],
      $this->parse('new class() implements A, B { };')
    );
  }
}