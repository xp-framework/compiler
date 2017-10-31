<?php namespace lang\ast\unittest\parse;

class LiteralsTest extends ParseTest {

  #[@test, @values(['0', '1'])]
  public function integer($input) {
    $this->assertNodes([['(literal)' => $input]], $this->parse($input.';'));
  }

  #[@test, @values(['0x00', '0x01', '0xFF', '0xff'])]
  public function hexadecimal($input) {
    $this->assertNodes([['(literal)' => $input]], $this->parse($input.';'));
  }

  #[@test, @values(['00', '01', '010', '0777'])]
  public function octal($input) {
    $this->assertNodes([['(literal)' => $input]], $this->parse($input.';'));
  }

  #[@test, @values(['1.0', '1.5'])]
  public function decimal($input) {
    $this->assertNodes([['(literal)' => $input]], $this->parse($input.';'));
  }

  #[@test]
  public function bool_true() {
    $this->assertNodes([['true' => 'true']], $this->parse('true;'));
  }

  #[@test]
  public function bool_false() {
    $this->assertNodes([['false' => 'false']], $this->parse('false;'));
  }

  #[@test]
  public function null() {
    $this->assertNodes([['null' => 'null']], $this->parse('null;'));
  }

  #[@test]
  public function empty_string() {
    $this->assertNodes([['(literal)' => '""']], $this->parse('"";'));
  }

  #[@test]
  public function non_empty_string() {
    $this->assertNodes([['(literal)' => '"Test"']], $this->parse('"Test";'));
  }

  #[@test]
  public function empty_array() {
    $this->assertNodes([['[' => []]], $this->parse('[];'));
  }

  #[@test]
  public function int_array() {
    $this->assertNodes(
      [['[' => [[null, ['(literal)' => '1']], [null, ['(literal)' => '2']]]]],
      $this->parse('[1, 2];')
    );
  }

  #[@test]
  public function key_value_map() {
    $this->assertNodes(
      [['[' => [[['(literal)' => '"key"'], ['(literal)' => '"value"']]]]],
      $this->parse('["key" => "value"];')
    );
  }

  #[@test]
  public function dangling_comma_in_array() {
    $this->assertNodes(
      [['[' => [[null, ['(literal)' => '1']]]]],
      $this->parse('[1, ];')
    );
  }

  #[@test]
  public function dangling_comma_in_key_value_map() {
    $this->assertNodes(
      [['[' => [[['(literal)' => '"key"'], ['(literal)' => '"value"']]]]],
      $this->parse('["key" => "value", ];')
    );
  }
}