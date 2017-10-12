<?php namespace lang\unittest\ast;

class LiteralsTest extends ParseTest {

  #[@test, @values([0, 1])]
  public function integer($i) {
    $this->assertNodes([['(literal)' => $i]], $this->parse($i.';'));
  }

  #[@test]
  public function hexadecimal() {
    $this->assertNodes([['(literal)' => 0x01]], $this->parse('0x01;'));
  }

  #[@test]
  public function octal() {
    $this->assertNodes([['(literal)' => 0777]], $this->parse('0777;'));
  }

  #[@test]
  public function decimal() {
    $this->assertNodes([['(literal)' => 1.5]], $this->parse('1.5;'));
  }

  #[@test]
  public function bool_true() {
    $this->assertNodes([['true' => true]], $this->parse('true;'));
  }

  #[@test]
  public function bool_false() {
    $this->assertNodes([['false' => false]], $this->parse('false;'));
  }

  #[@test]
  public function null() {
    $this->assertNodes([['null' => null]], $this->parse('null;'));
  }

  #[@test]
  public function empty_string() {
    $this->assertNodes([['(literal)' => '']], $this->parse('"";'));
  }

  #[@test]
  public function non_empty_string() {
    $this->assertNodes([['(literal)' => 'Test']], $this->parse('"Test";'));
  }

  #[@test]
  public function empty_array() {
    $this->assertNodes([['[' => []]], $this->parse('[];'));
  }

  #[@test]
  public function int_array() {
    $this->assertNodes([['[' => [['(literal)' => 1], ['(literal)' => 2]]]], $this->parse('[1, 2];'));
  }

  #[@test]
  public function key_value_map() {
    $this->assertNodes([['[' => ['key' => ['(literal)' => 'value']]]], $this->parse('["key" => "value"];'));
  }

  #[@test]
  public function dangling_comma_in_array() {
    $this->assertNodes([['[' => [['(literal)' => 1]]]], $this->parse('[1, ];'));
  }

  #[@test]
  public function dangling_comma_in_key_value_map() {
    $this->assertNodes([['[' => ['key' => ['(literal)' => 'value']]]], $this->parse('["key" => "value", ];'));
  }
}