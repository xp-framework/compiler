<?php namespace lang\ast\unittest\emit;

class ScalarsTest extends EmittingTest {

  #[@test, @values([
  #  ['0', 0],
  #  ['1', 1],
  #  ['-1', -1],
  #  ['0xff', 255],
  #  ['0755', 493],
  #  ['1.5', 1.5],
  #  ['-1.5', -1.5],
  #])]
  public function numbers($literal, $result) {
    $this->assertEquals($result, $this->run('class <T> { public function run() { return '.$literal.'; } }'));
  }

  #[@test, @values([
  #  ['135_99', 13599],
  #  ['107_925_284.88', 107925284.88],
  #  ['0xCAFE_F00D', 3405705229],
  #  ['0b0101_1111', 95],
  #  ['0137_041', 48673],
  #])]
  public function numeric_literal_separator($literal, $result) {
    $this->assertEquals($result, $this->run('class <T> { public function run() { return '.$literal.'; } }'));
  }

  #[@test, @values([
  #  ['""', ''],
  #  ['"Test"', 'Test'],
  #])]
  public function strings($literal, $result) {
    $this->assertEquals($result, $this->run('class <T> { public function run() { return '.$literal.'; } }'));
  }

  #[@test, @values([
  #  ['true', true],
  #  ['false', false],
  #  ['null', null],
  #])]
  public function constants($literal, $result) {
    $this->assertEquals($result, $this->run('class <T> { public function run() { return '.$literal.'; } }'));
  }
}