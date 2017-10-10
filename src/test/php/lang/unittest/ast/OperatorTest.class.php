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
}