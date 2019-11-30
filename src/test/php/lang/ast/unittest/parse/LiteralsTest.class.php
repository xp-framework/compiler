<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\{ArrayLiteral, Literal};
use unittest\Assert;

class LiteralsTest extends ParseTest {

  #[@test, @values(['0', '1'])]
  public function integer($input) {
    $this->assertParsed([new Literal($input, self::LINE)], $input.';');
  }

  #[@test, @values(['0x00', '0x01', '0xFF', '0xff'])]
  public function hexadecimal($input) {
    $this->assertParsed([new Literal($input, self::LINE)], $input.';');
  }

  #[@test, @values(['00', '01', '010', '0777'])]
  public function octal($input) {
    $this->assertParsed([new Literal($input, self::LINE)], $input.';');
  }

  #[@test, @values(['1.0', '1.5'])]
  public function decimal($input) {
    $this->assertParsed([new Literal($input, self::LINE)], $input.';');
  }

  #[@test]
  public function bool_true() {
    $this->assertParsed([new Literal('true', self::LINE)], 'true;');
  }

  #[@test]
  public function bool_false() {
    $this->assertParsed([new Literal('false', self::LINE)], 'false;');
  }

  #[@test]
  public function null() {
    $this->assertParsed([new Literal('null', self::LINE)], 'null;');
  }

  #[@test]
  public function empty_string() {
    $this->assertParsed([new Literal('""', self::LINE)], '"";');
  }

  #[@test]
  public function non_empty_string() {
    $this->assertParsed([new Literal('"Test"', self::LINE)], '"Test";');
  }

  #[@test]
  public function empty_array() {
    $this->assertParsed([new ArrayLiteral([], self::LINE)], '[];');
  }

  #[@test]
  public function int_array() {
    $pairs= [
      [null, new Literal('1', self::LINE)],
      [null, new Literal('2', self::LINE)]
    ];
    $this->assertParsed([new ArrayLiteral($pairs, self::LINE)], '[1, 2];');
  }

  #[@test]
  public function key_value_map() {
    $pair= [new Literal('"key"', self::LINE), new Literal('"value"', self::LINE)];
    $this->assertParsed([new ArrayLiteral([$pair], self::LINE)], '["key" => "value"];');
  }

  #[@test]
  public function dangling_comma_in_array() {
    $pair= [null, new Literal('1', self::LINE)];
    $this->assertParsed([new ArrayLiteral([$pair], self::LINE)], '[1, ];');
  }

  #[@test]
  public function dangling_comma_in_key_value_map() {
    $pair= [new Literal('"key"', self::LINE), new Literal('"value"', self::LINE)];
    $this->assertParsed([new ArrayLiteral([$pair], self::LINE)], '["key" => "value", ];');
  }
}