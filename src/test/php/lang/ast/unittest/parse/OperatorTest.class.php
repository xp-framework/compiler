<?php namespace lang\ast\unittest\parse;

class OperatorTest extends ParseTest {

  #[@test, @values([
  #  '+', '-', '*', '/', '.', '%', '|', '&', '**',
  #  '??', '?:',
  #  '&&', '||',
  #  '>>', '<<'
  #])]
  public function binary($operator) {
    $this->assertNodes(
      [[$operator => [['(variable)' => 'a'], $operator, ['(variable)' => 'b']]]],
      $this->parse('$a '.$operator.' $b;')
    );
  }

  #[@test]
  public function ternary() {
    $this->assertNodes(
      [['?' => [['(variable)' => 'a'], ['(literal)' => '1'], ['(literal)' => '2']]]],
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
      [[$operator => [['(variable)' => 'a'], $operator, ['(variable)' => 'b']]]],
      $this->parse('$a '.$operator.' $b;')
    );
  }

  #[@test, @values(['++', '--'])]
  public function suffix($operator) {
    $this->assertNodes(
      [[$operator => [['(variable)' => 'a'], $operator]]],
      $this->parse('$a'.$operator.';')
    );
  }

  #[@test, @values(['!', '~', '-', '+', '++', '--'])]
  public function prefix($operator) {
    $this->assertNodes(
      [[$operator => [['(variable)' => 'a'], $operator]]],
      $this->parse(''.$operator.'$a;')
    );
  }

  #[@test, @values([
  #  '=',
  #  '+=', '-=', '*=', '/=', '.=', '**=',
  #  '&=', '|=', '^=',
  #  '>>=', '<<='
  #])]
  public function assignment($operator) {
    $this->assertNodes(
      [[$operator => [['(variable)' => 'a'], $operator, ['(variable)' => 'b']]]],
      $this->parse('$a '.$operator.' $b;')
    );
  }

  #[@test]
  public function assignment_to_offset() {
    $this->assertNodes(
      [['=' => [['[' => [['(variable)' => 'a'], ['(literal)' => '0']]], '=', ['(literal)' => '1']]]],
      $this->parse('$a[0]= 1;')
    );
  }

  #[@test]
  public function destructuring_assignment() {
    $this->assertNodes(
      [['=' => [['[' => [[null, ['(variable)' => 'a']], [null, ['(variable)' => 'b']]]], '=', ['(' => [['result' => 'result'], []]]]]],
      $this->parse('[$a, $b]= result();')
    );
  }

  #[@test]
  public function comparison_to_assignment() {
    $this->assertNodes(
      [['===' => [['(literal)' => '1'], '===', ['(' => ['=' => [['(variable)' => 'a'], '=', ['(literal)' => '1']]]]]]],
      $this->parse('1 === ($a= 1);')
    );
  }

  #[@test]
  public function append_array() {
    $this->assertNodes(
      [['=' => [['[' => [['(variable)' => 'a'], null]], '=', ['(literal)' => '1']]]],
      $this->parse('$a[]= 1;')
    );
  }

  #[@test]
  public function clone_expression() {
    $this->assertNodes(
      [['clone' => [['(variable)' => 'a'], 'clone']]],
      $this->parse('clone $a;')
    );
  }

  #[@test]
  public function error_suppression() {
    $this->assertNodes(
      [['@' => [['(variable)' => 'a'], '@']]],
      $this->parse('@$a;')
    );
  }

  #[@test]
  public function reference() {
    $this->assertNodes(
      [['&' => [['(variable)' => 'a'], '&']]],
      $this->parse('&$a;')
    );
  }

  #[@test]
  public function new_type() {
    $this->assertNodes(
      [['new' => ['\\T', []]]],
      $this->parse('new T();')
    );
  }

  #[@test]
  public function new_type_with_args() {
    $this->assertNodes(
      [['new' => ['\\T', [['(variable)' => 'a'], ['(variable)' => 'b']]]]],
      $this->parse('new T($a, $b);')
    );
  }

  #[@test]
  public function new_anonymous_extends() {
    $this->assertNodes(
      [['new' => [[null, [], '\\T', [], [], [], null], []]]],
      $this->parse('new class() extends T { };')
    );
  }

  #[@test]
  public function new_anonymous_implements() {
    $this->assertNodes(
      [['new' => [[null, [], null, ['\\A', '\\B'], [], [], null], []]]],
      $this->parse('new class() implements A, B { };')
    );
  }

  #[@test]
  public function precedence_of_object_operator() {
    $this->assertNodes(
      [['.' => [
        ['->' => [['(variable)' => 'this'], ['a' => 'a']]],
        '.',
        ['(literal)' => '"test"']
      ]]],
      $this->parse('$this->a."test";')
    );
  }

  #[@test]
  public function precedence_of_scope_resolution_operator() {
    $this->assertNodes(
      [['.' => [
        ['::' => ['self', ['class' => 'class']]],
        '.',
        ['(literal)' => '"test"']
      ]]],
      $this->parse('self::class."test";')
    );
  }
}