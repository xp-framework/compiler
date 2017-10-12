<?php namespace lang\ast\unittest;

class LiteralsTest extends ParseTest {

  #[@test, @values(map= [
  #  '0' => 0,
  #  '1' => 1
  #])]
  public function integer($input, $expect) {
    $this->assertNodes([['(literal)' => $expect]], $this->parse($input.';'));
  }

  #[@test, @values(map= [
  #  '0x00' => 0,
  #  '0x01' => 1,
  #  '0xFF' => 255,
  #  '0xff' => 255
  #])]
  public function hexadecimal($input, $expect) {
    $this->assertNodes([['(literal)' => $expect]], $this->parse($input.';'));
  }

  #[@test, @values(map= [
  #  '00' => 0,
  #  '01' => 1,
  #  '010' => 8,
  #  '0777' => 511
  #])]
  public function octal($input, $expect) {
    $this->assertNodes([['(literal)' => $expect]], $this->parse($input.';'));
  }

  #[@test, @values(map= [
  #  '1.0' => 1.0,
  #  '1.5' => 1.5
  #])]
  public function decimal($input, $expect) {
    $this->assertNodes([['(literal)' => $expect]], $this->parse($input.';'));
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