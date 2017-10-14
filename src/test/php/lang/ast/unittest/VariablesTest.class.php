<?php namespace lang\ast\unittest;

class VariablesTest extends ParseTest {

  #[@test, @values(['v', 'key', 'this', 'class', 'protected'])]
  public function variable($name) {
    $this->assertNodes(
      [['(variable)' => $name]],
      $this->parse('$'.$name.';')
    );
  }

  #[@test]
  public function static_variable() {
    $this->assertNodes(
      [['static' => ['v' => null]]],
      $this->parse('static $v;')
    );
  }

  #[@test]
  public function static_variable_with_initialization() {
    $this->assertNodes(
      [['static' => ['id' => ['(literal)' => 0]]]],
      $this->parse('static $id= 0;')
    );
  }

  #[@test]
  public function array_offset() {
    $this->assertNodes(
      [['[' => [['(variable)' => 'a'], ['(literal)' => 0]]]],
      $this->parse('$a[0];')
    );
  }

  #[@test]
  public function string_offset() {
    $this->assertNodes(
      [['{' => [['(variable)' => 'a'], ['(literal)' => 0]]]],
      $this->parse('$a{0};')
    );
  }
}